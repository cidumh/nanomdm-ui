<?php
/**
 * 设备配置文件管理指令
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mdm.php';
require_once __DIR__ . '/mdm_client.php';
require_once __DIR__ . '/mdm_device.php';
require_once __DIR__ . '/mdm_device_profile.php';
require_once __DIR__ . '/device_log.php';
require_once __DIR__ . '/policy.php';
require_once __DIR__ . '/policy_dns_profile.php';
require_once __DIR__ . '/policy_global_proxy_profile.php';
require_once __DIR__ . '/policy_func_restriction_profile.php';
require_once __DIR__ . '/profile_install_payload.php';

class DeviceProfileService
{
    public static function listProfiles(string $serial): array
    {
        MdmDeviceProfile::ensureTables();
        return MdmDeviceProfile::listBySerial($serial);
    }

    public static function getInstallDefaults(string $serial): array
    {
        PolicyConfig::ensureTables();
        MdmDeviceProfile::ensureTables();

        $policy = PolicyConfig::getAll();

        return [
            'dns' => [
                'dns_org_name'    => PolicyConfig::orgName($policy['dns_org_name'] ?? ''),
                'dns_identifier'  => MdmDeviceProfile::resolveIdentifier($serial, MdmDeviceProfile::PAYLOAD_TYPE_DNS)
                    ?: MdmDeviceProfile::DEFAULT_ID_DNS,
                'dns_server_url'  => PolicyConfig::dnsServerUrl($policy['dns_server_url'] ?? ''),
                'dns_address_1'   => PolicyConfig::dnsAddress1($policy['dns_address_1'] ?? ''),
                'dns_address_2'   => PolicyConfig::dnsAddress2($policy['dns_address_2'] ?? ''),
            ],
            'global' => [
                'proxy_org_name'   => PolicyConfig::orgName($policy['proxy_org_name'] ?? ''),
                'proxy_identifier' => MdmDeviceProfile::resolveIdentifier($serial, MdmDeviceProfile::PAYLOAD_TYPE_GLOBAL)
                    ?: MdmDeviceProfile::DEFAULT_ID_GLOBAL,
                'proxy_pac_url'    => trim($policy['proxy_pac_url'] ?? ''),
            ],
            'func' => [
                'func_org_name'    => PolicyConfig::orgName($policy['func_org_name'] ?? ''),
                'func_identifier'  => MdmDeviceProfile::resolveIdentifier($serial, MdmDeviceProfile::PAYLOAD_TYPE_FUNC)
                    ?: MdmDeviceProfile::DEFAULT_ID_FUNC,
                'func_restrictions'=> PolicyConfig::getFuncRestrictions(),
            ],
            'func_keys' => PolicyConfig::funcRestrictionKeys(),
        ];
    }

    public static function removeProfile(string $serial, string $identifier, string $operationType = '移除配置文件'): array
    {
        return self::sendRemoveProfile($serial, $identifier, $operationType);
    }

    public static function removeProfileByInput(string $serial, string $identifier): array
    {
        return self::sendRemoveProfile($serial, $identifier, '移除配置描述文件');
    }

    public static function installProfileFile(string $serial, string $profileContent, string $filename = ''): array
    {
        $profileXml = ProfileInstallPayload::normalize($profileContent, $filename);

        $commandContent = PolicyDnsProfile::buildInstallCommand($profileXml);

        return self::enqueueInstall(
            $serial,
            $commandContent,
            '安装配置描述文件',
            'InstallProfile',
            '发送安装配置描述文件成功'
        );
    }

    public static function installDnsProfile(string $serial, array $config): array
    {
        $config['dns_identifier'] = trim((string) ($config['dns_identifier'] ?? ''));
        if ($config['dns_identifier'] === '') {
            $config['dns_identifier'] = MdmDeviceProfile::resolveIdentifier($serial, MdmDeviceProfile::PAYLOAD_TYPE_DNS);
        }

        $profileXml = PolicyDnsProfile::buildProfileFrom($config);
        $commandContent = PolicyDnsProfile::buildInstallCommand($profileXml);

        return self::enqueueInstall(
            $serial,
            $commandContent,
            '安装DNS代理',
            'dnsSettings',
            '发送安装 DNS 代理成功'
        );
    }

    public static function installGlobalProxyProfile(string $serial, array $config): array
    {
        $config['proxy_identifier'] = trim((string) ($config['proxy_identifier'] ?? ''));
        if ($config['proxy_identifier'] === '') {
            $config['proxy_identifier'] = MdmDeviceProfile::resolveIdentifier($serial, MdmDeviceProfile::PAYLOAD_TYPE_GLOBAL);
        }

        $profileXml = PolicyGlobalProxyProfile::buildProfileFrom($config);
        $commandContent = PolicyDnsProfile::buildInstallCommand($profileXml);

        return self::enqueueInstall(
            $serial,
            $commandContent,
            '安装全局代理',
            'HTTPproxy',
            '发送安装全局代理成功'
        );
    }

    public static function installFuncRestrictionProfile(string $serial, array $config): array
    {
        $config['func_identifier'] = trim((string) ($config['func_identifier'] ?? ''));
        if ($config['func_identifier'] === '') {
            $config['func_identifier'] = MdmDeviceProfile::resolveIdentifier($serial, MdmDeviceProfile::PAYLOAD_TYPE_FUNC);
        }

        $profileXml = PolicyFuncRestrictionProfile::buildProfileFrom($config);
        $commandContent = PolicyDnsProfile::buildInstallCommand($profileXml);

        return self::enqueueInstall(
            $serial,
            $commandContent,
            '安装功能限制',
            'applicationaccess',
            '发送安装功能限制成功'
        );
    }

    private static function sendRemoveProfile(string $serial, string $identifier, string $operationType): array
    {
        MdmDevice::ensureTables();
        MdmConfig::ensureTables();

        $serial = trim($serial);
        $identifier = trim($identifier);

        if ($serial === '') {
            throw new InvalidArgumentException('设备序列号不能为空');
        }
        if ($identifier === '') {
            throw new InvalidArgumentException('请输入配置文件标识');
        }

        $device = MdmDevice::findBySerial($serial);
        if (!$device) {
            throw new InvalidArgumentException('设备不存在');
        }

        $udid = trim((string) ($device['udid'] ?? ''));
        $remark = trim((string) ($device['remark'] ?? ''));
        $eventTime = date('Y-m-d H:i:s');
        $ip = clientIp();

        if ($udid === '') {
            $msg = '设备 UDID 为空';
            self::logProfileCommand($serial, $udid, $remark, '', $eventTime, $ip, false, $msg, $operationType, 'RemoveProfile');
            return ['ok' => false, 'msg' => $msg];
        }

        if (!MdmConfig::isConfigured()) {
            $msg = '请先在 MDM 配置中填写 MDM Server URL';
            self::logProfileCommand($serial, $udid, $remark, '', $eventTime, $ip, false, $msg, $operationType, 'RemoveProfile');
            return ['ok' => false, 'msg' => $msg];
        }

        $commandContent = self::buildRemoveProfileCommand($identifier);
        $result = MdmClient::enqueueCommand($udid, $commandContent);

        if (!$result['ok']) {
            self::logProfileCommand($serial, $udid, $remark, '', $eventTime, $ip, false, $result['msg'], $operationType, 'RemoveProfile');
            return ['ok' => false, 'msg' => $result['msg']];
        }

        $commId = $result['command_uuid'] ?? ($result['replaced_command_uuid'] ?? '');
        $pushId = is_scalar($result['push_result'] ?? '')
            ? (string) ($result['push_result'] ?? '')
            : json_encode($result['push_result'] ?? '', JSON_UNESCAPED_UNICODE);

        self::logProfileCommand($serial, $udid, $remark, $commId, $eventTime, $ip, true, '等待响应', $operationType, 'RemoveProfile', $pushId);

        return [
            'ok'           => true,
            'msg'          => '发送移除配置文件成功',
            'command_uuid' => $commId,
            'push_id'      => $pushId,
            'request_type' => $result['request_type'] ?? 'RemoveProfile',
        ];
    }

    private static function enqueueInstall(
        string $serial,
        string $commandContent,
        string $operationType,
        string $commandType,
        string $successMsg
    ): array {
        MdmDevice::ensureTables();
        MdmConfig::ensureTables();

        $serial = trim($serial);
        if ($serial === '') {
            throw new InvalidArgumentException('设备序列号不能为空');
        }

        $device = MdmDevice::findBySerial($serial);
        if (!$device) {
            throw new InvalidArgumentException('设备不存在');
        }

        $udid = trim((string) ($device['udid'] ?? ''));
        $remark = trim((string) ($device['remark'] ?? ''));
        $eventTime = date('Y-m-d H:i:s');
        $ip = clientIp();

        if ($udid === '') {
            $msg = '设备 UDID 为空';
            self::logProfileCommand($serial, $udid, $remark, '', $eventTime, $ip, false, $msg, $operationType, $commandType);
            return ['ok' => false, 'msg' => $msg];
        }

        if (!MdmConfig::isConfigured()) {
            $msg = '请先在 MDM 配置中填写 MDM Server URL';
            self::logProfileCommand($serial, $udid, $remark, '', $eventTime, $ip, false, $msg, $operationType, $commandType);
            return ['ok' => false, 'msg' => $msg];
        }

        $result = MdmClient::enqueueCommand($udid, $commandContent);

        if (!$result['ok']) {
            self::logProfileCommand($serial, $udid, $remark, '', $eventTime, $ip, false, $result['msg'], $operationType, $commandType);
            return ['ok' => false, 'msg' => $result['msg']];
        }

        $commId = $result['command_uuid'] ?? ($result['replaced_command_uuid'] ?? '');
        $pushId = is_scalar($result['push_result'] ?? '')
            ? (string) ($result['push_result'] ?? '')
            : json_encode($result['push_result'] ?? '', JSON_UNESCAPED_UNICODE);

        self::logProfileCommand($serial, $udid, $remark, $commId, $eventTime, $ip, true, '等待响应', $operationType, $commandType, $pushId);

        return [
            'ok'           => true,
            'msg'          => $successMsg,
            'command_uuid' => $commId,
            'push_id'      => $pushId,
            'request_type' => $result['request_type'] ?? 'InstallProfile',
        ];
    }

    private static function buildRemoveProfileCommand(string $identifier): string
    {
        $lines = [];
        $lines[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $lines[] = '<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">';
        $lines[] = '<plist version="1.0">';
        $lines[] = '<dict>';
        $lines[] = "\t<key>Command</key>";
        $lines[] = "\t<dict>";
        $lines[] = "\t\t<key>Identifier</key>";
        $lines[] = "\t\t<string>" . htmlspecialchars($identifier, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</string>';
        $lines[] = "\t\t<key>RequestType</key>";
        $lines[] = "\t\t<string>RemoveProfile</string>";
        $lines[] = "\t</dict>";
        $lines[] = "\t<key>CommandUUID</key>";
        $lines[] = "\t<string>_CommandUUID_</string>";
        $lines[] = '</dict>';
        $lines[] = '</plist>';

        return implode("\n", $lines) . "\n";
    }

    private static function logProfileCommand(
        string $serial,
        string $udid,
        string $remark,
        string $commId,
        string $eventTime,
        string $ip,
        bool $success,
        string $message,
        string $operationType,
        string $commandType,
        string $pushId = ''
    ): void {
        if ($serial === '') {
            return;
        }

        $status = $success ? $message : ('失败:' . $message);

        DeviceLog::logDeviceEvent($serial, [
            'device_udid'    => $udid,
            'device_remark'  => $remark,
            'serial_number'  => $serial,
            'operation_type' => $operationType,
            'comm_id'        => $commId,
            'push_id'        => $pushId,
            'command_type'   => $commandType,
            'status'         => $status,
            'confirmed_at'   => null,
            'created_at'     => $eventTime,
            'ip'             => $ip,
        ]);
    }
}
