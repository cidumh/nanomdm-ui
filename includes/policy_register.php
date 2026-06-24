<?php
/**
 * 设备注册后执行策略配置
 */

require_once __DIR__ . '/policy.php';
require_once __DIR__ . '/dep.php';
require_once __DIR__ . '/dep_profile.php';
require_once __DIR__ . '/dep_activation_lock.php';
require_once __DIR__ . '/mdm_device.php';
require_once __DIR__ . '/device_log.php';
require_once __DIR__ . '/mdm.php';
require_once __DIR__ . '/mdm_client.php';
require_once __DIR__ . '/policy_dns_profile.php';
require_once __DIR__ . '/policy_global_proxy_profile.php';
require_once __DIR__ . '/policy_func_restriction_profile.php';
require_once __DIR__ . '/policy_device_information.php';

class PolicyRegister
{
    public static function applyOnRegister(array $context): array
    {
        PolicyConfig::ensureTables();
        DepConfig::ensureTables();

        $results = [];

        if (PolicyConfig::getBool('activation_lock')) {
            $results['activation_lock'] = self::enableActivationLock($context);
        }

        if (PolicyConfig::getBool('dns_proxy')) {
            $results['dns_proxy'] = self::installDnsProxy($context);
        }

        if (PolicyConfig::getBool('global_proxy')) {
            $results['global_proxy'] = self::installGlobalProxy($context);
        }

        if (PolicyConfig::getBool('func_restriction')) {
            $results['func_restriction'] = self::installFuncRestriction($context);
        }

        $results['device_configured'] = self::sendDeviceConfigured($context);
        $results['device_information'] = self::sendDeviceInformation($context);

        return $results;
    }

    private static function enableActivationLock(array $context): array
    {
        $serial = trim((string) ($context['serial_number'] ?? ''));
        $udid = trim((string) ($context['device_udid'] ?? ''));
        $ip = trim((string) ($context['ip'] ?? ''));
        $eventTime = trim((string) ($context['event_time'] ?? ''));
        $eventId = trim((string) ($context['event_id'] ?? ''));

        if ($eventTime === '') {
            $eventTime = date('Y-m-d H:i:s');
        }

        $device = MdmDevice::findBySerial($serial);
        $remark = $device['remark'] ?? '';

        if (!DepConfig::isConfigured()) {
            $msg = 'DEP 未配置，无法开启激活锁';
            self::logActivationLock($serial, $udid, $remark, $eventId, $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        if ($serial === '') {
            $msg = '设备序列号为空';
            self::logActivationLock($serial, $udid, $remark, $eventId, $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        $generated = depGenerateActivationLockBypass();
        $bypassCode = $generated['bypass_code'];
        $escrowKey = $generated['escrow_key'];
        $lostMessage = '';

        $result = DepClient::enableActivationLock($serial, $escrowKey, $lostMessage);

        if (!$result['ok']) {
            self::logActivationLock($serial, $udid, $remark, $eventId, $eventTime, $ip, false, $result['msg']);
            return ['ok' => false, 'msg' => $result['msg']];
        }

        $data = $result['data'] ?? [];
        $status = strtoupper((string) ($data['response_status'] ?? ''));

        if ($status !== 'SUCCESS') {
            $msg = $status !== '' ? ('DEP 返回状态：' . $status) : 'DEP 服务器响应异常';
            self::logActivationLock($serial, $udid, $remark, $eventId, $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg, 'response_status' => $status];
        }

        try {
            DepActivationLock::save($serial, $bypassCode, $escrowKey, $lostMessage, null);
            MdmDevice::setActivationLockStatus($serial, true);
        } catch (Exception $e) {
            $msg = '激活锁已开启，但本地绕过码保存失败：' . $e->getMessage();
            self::logActivationLock($serial, $udid, $remark, $eventId, $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        self::logActivationLock($serial, $udid, $remark, $eventId, $eventTime, $ip, true, '开启成功');

        return [
            'ok'              => true,
            'msg'             => '开启成功',
            'serial_number'   => $data['serial_number'] ?? $serial,
            'response_status' => $status,
        ];
    }

    private static function logActivationLock(
        string $serial,
        string $udid,
        string $remark,
        string $eventId,
        string $eventTime,
        string $ip,
        bool $success,
        string $message
    ): void {
        if ($serial === '') {
            return;
        }

        $status = $success ? '完成:开启成功' : ('失败:' . $message);

        DeviceLog::logDeviceEvent($serial, [
            'device_udid'    => $udid,
            'device_remark'  => $remark,
            'serial_number'  => $serial,
            'operation_type' => '开启激活锁',
            'comm_id'        => $eventId,
            'push_id'        => '',
            'command_type'   => 'activationlock',
            'status'         => $status,
            'confirmed_at'   => $eventTime,
            'created_at'     => $eventTime,
            'ip'             => $ip,
        ]);
    }

    private static function installDnsProxy(array $context): array
    {
        $serial = trim((string) ($context['serial_number'] ?? ''));
        $udid = trim((string) ($context['device_udid'] ?? ''));
        $ip = trim((string) ($context['ip'] ?? ''));
        $eventTime = trim((string) ($context['event_time'] ?? ''));
        $eventId = trim((string) ($context['event_id'] ?? ''));

        if ($eventTime === '') {
            $eventTime = date('Y-m-d H:i:s');
        }

        $device = MdmDevice::findBySerial($serial);
        $remark = $device['remark'] ?? '';

        if ($serial === '') {
            $msg = '设备序列号为空';
            self::logDnsProxy($serial, $udid, $remark, '', $eventId, $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        if ($udid === '') {
            $msg = '设备 UDID 为空';
            self::logDnsProxy($serial, $udid, $remark, '', $eventId, $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        if (!MdmConfig::isConfigured()) {
            $msg = 'MDM 未配置，无法安装 DNS 代理';
            self::logDnsProxy($serial, $udid, $remark, '', $eventId, $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        $configCheck = PolicyDnsProfile::validateConfig();
        if (!$configCheck['ok']) {
            self::logDnsProxy($serial, $udid, $remark, '', $eventId, $eventTime, $ip, false, $configCheck['msg']);
            return ['ok' => false, 'msg' => $configCheck['msg']];
        }

        try {
            $profileXml = PolicyDnsProfile::buildProfile();
            $commandContent = PolicyDnsProfile::buildInstallCommand($profileXml);
        } catch (InvalidArgumentException $e) {
            self::logDnsProxy($serial, $udid, $remark, '', $eventId, $eventTime, $ip, false, $e->getMessage());
            return ['ok' => false, 'msg' => $e->getMessage()];
        }

        $result = MdmClient::enqueueCommand($udid, $commandContent);

        if (!$result['ok']) {
            self::logDnsProxy($serial, $udid, $remark, '', $eventId, $eventTime, $ip, false, $result['msg']);
            return ['ok' => false, 'msg' => $result['msg']];
        }

        $commId = $result['command_uuid'] ?? ($result['replaced_command_uuid'] ?? '');
        $pushId = is_scalar($result['push_result'] ?? '')
            ? (string) ($result['push_result'] ?? '')
            : json_encode($result['push_result'] ?? '', JSON_UNESCAPED_UNICODE);

        self::logDnsProxy($serial, $udid, $remark, $commId, $eventId, $eventTime, $ip, true, '等待响应', $pushId);

        return [
            'ok'           => true,
            'msg'          => $result['msg'] ?? '指令推送成功',
            'command_uuid' => $commId,
            'push_result'  => $result['push_result'] ?? '',
            'request_type' => $result['request_type'] ?? 'InstallProfile',
        ];
    }

    private static function logDnsProxy(
        string $serial,
        string $udid,
        string $remark,
        string $commId,
        string $eventId,
        string $eventTime,
        string $ip,
        bool $success,
        string $message,
        string $pushId = ''
    ): void {
        if ($serial === '') {
            return;
        }

        $status = $success ? $message : ('失败:' . $message);
        $logCommId = $commId !== '' ? $commId : $eventId;

        DeviceLog::logDeviceEvent($serial, [
            'device_udid'    => $udid,
            'device_remark'  => $remark,
            'serial_number'  => $serial,
            'operation_type' => '安装DNS代理',
            'comm_id'        => $logCommId,
            'push_id'        => $pushId,
            'command_type'   => 'dnsSettings',
            'status'         => $status,
            'confirmed_at'   => null,
            'created_at'     => $eventTime,
            'ip'             => $ip,
        ]);
    }

    private static function installGlobalProxy(array $context): array
    {
        $serial = trim((string) ($context['serial_number'] ?? ''));
        $udid = trim((string) ($context['device_udid'] ?? ''));
        $ip = trim((string) ($context['ip'] ?? ''));
        $eventTime = trim((string) ($context['event_time'] ?? ''));
        $eventId = trim((string) ($context['event_id'] ?? ''));

        if ($eventTime === '') {
            $eventTime = date('Y-m-d H:i:s');
        }

        $device = MdmDevice::findBySerial($serial);
        $remark = $device['remark'] ?? '';

        if ($serial === '') {
            $msg = '设备序列号为空';
            self::logGlobalProxy($serial, $udid, $remark, '', $eventId, $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        if ($udid === '') {
            $msg = '设备 UDID 为空';
            self::logGlobalProxy($serial, $udid, $remark, '', $eventId, $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        if (!MdmConfig::isConfigured()) {
            $msg = 'MDM 未配置，无法安装全局代理';
            self::logGlobalProxy($serial, $udid, $remark, '', $eventId, $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        $configCheck = PolicyGlobalProxyProfile::validateConfig();
        if (!$configCheck['ok']) {
            self::logGlobalProxy($serial, $udid, $remark, '', $eventId, $eventTime, $ip, false, $configCheck['msg']);
            return ['ok' => false, 'msg' => $configCheck['msg']];
        }

        try {
            $profileXml = PolicyGlobalProxyProfile::buildProfile();
            $commandContent = PolicyDnsProfile::buildInstallCommand($profileXml);
        } catch (InvalidArgumentException $e) {
            self::logGlobalProxy($serial, $udid, $remark, '', $eventId, $eventTime, $ip, false, $e->getMessage());
            return ['ok' => false, 'msg' => $e->getMessage()];
        }

        $result = MdmClient::enqueueCommand($udid, $commandContent);

        if (!$result['ok']) {
            self::logGlobalProxy($serial, $udid, $remark, '', $eventId, $eventTime, $ip, false, $result['msg']);
            return ['ok' => false, 'msg' => $result['msg']];
        }

        $commId = $result['command_uuid'] ?? ($result['replaced_command_uuid'] ?? '');
        $pushId = is_scalar($result['push_result'] ?? '')
            ? (string) ($result['push_result'] ?? '')
            : json_encode($result['push_result'] ?? '', JSON_UNESCAPED_UNICODE);

        self::logGlobalProxy($serial, $udid, $remark, $commId, $eventId, $eventTime, $ip, true, '等待响应', $pushId);

        return [
            'ok'           => true,
            'msg'          => $result['msg'] ?? '指令推送成功',
            'command_uuid' => $commId,
            'push_result'  => $result['push_result'] ?? '',
            'request_type' => $result['request_type'] ?? 'InstallProfile',
        ];
    }

    private static function logGlobalProxy(
        string $serial,
        string $udid,
        string $remark,
        string $commId,
        string $eventId,
        string $eventTime,
        string $ip,
        bool $success,
        string $message,
        string $pushId = ''
    ): void {
        if ($serial === '') {
            return;
        }

        $status = $success ? $message : ('失败:' . $message);
        $logCommId = $commId !== '' ? $commId : $eventId;

        DeviceLog::logDeviceEvent($serial, [
            'device_udid'    => $udid,
            'device_remark'  => $remark,
            'serial_number'  => $serial,
            'operation_type' => '安装全局代理',
            'comm_id'        => $logCommId,
            'push_id'        => $pushId,
            'command_type'   => 'HTTPproxy',
            'status'         => $status,
            'confirmed_at'   => null,
            'created_at'     => $eventTime,
            'ip'             => $ip,
        ]);
    }

    private static function installFuncRestriction(array $context): array
    {
        $serial = trim((string) ($context['serial_number'] ?? ''));
        $udid = trim((string) ($context['device_udid'] ?? ''));
        $ip = trim((string) ($context['ip'] ?? ''));
        $eventTime = trim((string) ($context['event_time'] ?? ''));
        $eventId = trim((string) ($context['event_id'] ?? ''));

        if ($eventTime === '') {
            $eventTime = date('Y-m-d H:i:s');
        }

        $device = MdmDevice::findBySerial($serial);
        $remark = $device['remark'] ?? '';

        if ($serial === '') {
            $msg = '设备序列号为空';
            self::logFuncRestriction($serial, $udid, $remark, '', $eventId, $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        if ($udid === '') {
            $msg = '设备 UDID 为空';
            self::logFuncRestriction($serial, $udid, $remark, '', $eventId, $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        if (!MdmConfig::isConfigured()) {
            $msg = 'MDM 未配置，无法安装功能限制';
            self::logFuncRestriction($serial, $udid, $remark, '', $eventId, $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        $configCheck = PolicyFuncRestrictionProfile::validateConfig();
        if (!$configCheck['ok']) {
            self::logFuncRestriction($serial, $udid, $remark, '', $eventId, $eventTime, $ip, false, $configCheck['msg']);
            return ['ok' => false, 'msg' => $configCheck['msg']];
        }

        try {
            $profileXml = PolicyFuncRestrictionProfile::buildProfile();
            $commandContent = PolicyDnsProfile::buildInstallCommand($profileXml);
        } catch (InvalidArgumentException $e) {
            self::logFuncRestriction($serial, $udid, $remark, '', $eventId, $eventTime, $ip, false, $e->getMessage());
            return ['ok' => false, 'msg' => $e->getMessage()];
        }

        $result = MdmClient::enqueueCommand($udid, $commandContent);

        if (!$result['ok']) {
            self::logFuncRestriction($serial, $udid, $remark, '', $eventId, $eventTime, $ip, false, $result['msg']);
            return ['ok' => false, 'msg' => $result['msg']];
        }

        $commId = $result['command_uuid'] ?? ($result['replaced_command_uuid'] ?? '');
        $pushId = is_scalar($result['push_result'] ?? '')
            ? (string) ($result['push_result'] ?? '')
            : json_encode($result['push_result'] ?? '', JSON_UNESCAPED_UNICODE);

        self::logFuncRestriction($serial, $udid, $remark, $commId, $eventId, $eventTime, $ip, true, '等待响应', $pushId);

        return [
            'ok'           => true,
            'msg'          => $result['msg'] ?? '指令推送成功',
            'command_uuid' => $commId,
            'push_result'  => $result['push_result'] ?? '',
            'request_type' => $result['request_type'] ?? 'InstallProfile',
        ];
    }

    private static function logFuncRestriction(
        string $serial,
        string $udid,
        string $remark,
        string $commId,
        string $eventId,
        string $eventTime,
        string $ip,
        bool $success,
        string $message,
        string $pushId = ''
    ): void {
        if ($serial === '') {
            return;
        }

        $status = $success ? $message : ('失败:' . $message);
        $logCommId = $commId !== '' ? $commId : $eventId;

        DeviceLog::logDeviceEvent($serial, [
            'device_udid'    => $udid,
            'device_remark'  => $remark,
            'serial_number'  => $serial,
            'operation_type' => '安装功能限制',
            'comm_id'        => $logCommId,
            'push_id'        => $pushId,
            'command_type'   => 'applicationaccess',
            'status'         => $status,
            'confirmed_at'   => null,
            'created_at'     => $eventTime,
            'ip'             => $ip,
        ]);
    }

    private static function buildDeviceConfiguredCommand(): string
    {
        $lines = [];
        $lines[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $lines[] = '<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">';
        $lines[] = '<plist version="1.0">';
        $lines[] = '<dict>';
        $lines[] = "\t<key>Command</key>";
        $lines[] = "\t<dict>";
        $lines[] = "\t\t<key>RequestType</key>";
        $lines[] = "\t\t<string>DeviceConfigured</string>";
        $lines[] = "\t</dict>";
        $lines[] = "\t<key>CommandUUID</key>";
        $lines[] = "\t<string>(CommandUUID)</string>";
        $lines[] = '</dict>';
        $lines[] = '</plist>';

        return implode("\n", $lines) . "\n";
    }

    private static function sendDeviceConfigured(array $context): array
    {
        $serial = trim((string) ($context['serial_number'] ?? ''));
        $udid = trim((string) ($context['device_udid'] ?? ''));
        $ip = trim((string) ($context['ip'] ?? ''));
        $eventTime = trim((string) ($context['event_time'] ?? ''));
        $eventId = trim((string) ($context['event_id'] ?? ''));

        if ($eventTime === '') {
            $eventTime = date('Y-m-d H:i:s');
        }

        $device = MdmDevice::findBySerial($serial);
        $remark = $device['remark'] ?? '';

        if ($serial === '') {
            $msg = '设备序列号为空';
            self::logDeviceConfigured($serial, $udid, $remark, '', $eventId, $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        if ($udid === '') {
            $msg = '设备 UDID 为空';
            self::logDeviceConfigured($serial, $udid, $remark, '', $eventId, $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        if (!MdmConfig::isConfigured()) {
            $msg = 'MDM 未配置，无法发送配置完成指令';
            self::logDeviceConfigured($serial, $udid, $remark, '', $eventId, $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        $commandContent = self::buildDeviceConfiguredCommand();
        $result = MdmClient::enqueueCommand($udid, $commandContent);

        if (!$result['ok']) {
            self::logDeviceConfigured($serial, $udid, $remark, '', $eventId, $eventTime, $ip, false, $result['msg']);
            return ['ok' => false, 'msg' => $result['msg']];
        }

        $commId = $result['command_uuid'] ?? ($result['replaced_command_uuid'] ?? '');
        $pushId = is_scalar($result['push_result'] ?? '')
            ? (string) ($result['push_result'] ?? '')
            : json_encode($result['push_result'] ?? '', JSON_UNESCAPED_UNICODE);

        self::logDeviceConfigured($serial, $udid, $remark, $commId, $eventId, $eventTime, $ip, true, '等待响应', $pushId);

        return [
            'ok'           => true,
            'msg'          => $result['msg'] ?? '指令推送成功',
            'command_uuid' => $commId,
            'push_result'  => $result['push_result'] ?? '',
            'request_type' => $result['request_type'] ?? 'DeviceConfigured',
        ];
    }

    private static function logDeviceConfigured(
        string $serial,
        string $udid,
        string $remark,
        string $commId,
        string $eventId,
        string $eventTime,
        string $ip,
        bool $success,
        string $message,
        string $pushId = ''
    ): void {
        if ($serial === '') {
            return;
        }

        $status = $success ? $message : ('失败:' . $message);
        $logCommId = $commId !== '' ? $commId : $eventId;

        DeviceLog::logDeviceEvent($serial, [
            'device_udid'    => $udid,
            'device_remark'  => $remark,
            'serial_number'  => $serial,
            'operation_type' => '配置完成',
            'comm_id'        => $logCommId,
            'push_id'        => $pushId,
            'command_type'   => 'DeviceConfigured',
            'status'         => $status,
            'confirmed_at'   => null,
            'created_at'     => $eventTime,
            'ip'             => $ip,
        ]);
    }

    private static function sendDeviceInformation(array $context): array
    {
        $serial = trim((string) ($context['serial_number'] ?? ''));
        $udid = trim((string) ($context['device_udid'] ?? ''));
        $ip = trim((string) ($context['ip'] ?? ''));
        $eventTime = trim((string) ($context['event_time'] ?? ''));
        $eventId = trim((string) ($context['event_id'] ?? ''));

        if ($eventTime === '') {
            $eventTime = date('Y-m-d H:i:s');
        }

        $device = MdmDevice::findBySerial($serial);
        $remark = $device['remark'] ?? '';

        if ($serial === '') {
            $msg = '设备序列号为空';
            self::logDeviceInformation($serial, $udid, $remark, '', $eventId, $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        if ($udid === '') {
            $msg = '设备 UDID 为空';
            self::logDeviceInformation($serial, $udid, $remark, '', $eventId, $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        if (!MdmConfig::isConfigured()) {
            $msg = 'MDM 未配置，无法发送更新设备信息指令';
            self::logDeviceInformation($serial, $udid, $remark, '', $eventId, $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        $commandContent = PolicyDeviceInformation::buildCommand();
        $result = MdmClient::enqueueCommand($udid, $commandContent);

        if (!$result['ok']) {
            self::logDeviceInformation($serial, $udid, $remark, '', $eventId, $eventTime, $ip, false, $result['msg']);
            return ['ok' => false, 'msg' => $result['msg']];
        }

        $commId = $result['command_uuid'] ?? ($result['replaced_command_uuid'] ?? '');
        $pushId = is_scalar($result['push_result'] ?? '')
            ? (string) ($result['push_result'] ?? '')
            : json_encode($result['push_result'] ?? '', JSON_UNESCAPED_UNICODE);

        self::logDeviceInformation($serial, $udid, $remark, $commId, $eventId, $eventTime, $ip, true, '等待响应', $pushId);

        return [
            'ok'           => true,
            'msg'          => $result['msg'] ?? '指令推送成功',
            'command_uuid' => $commId,
            'push_result'  => $result['push_result'] ?? '',
            'request_type' => $result['request_type'] ?? 'DeviceInformation',
        ];
    }

    private static function logDeviceInformation(
        string $serial,
        string $udid,
        string $remark,
        string $commId,
        string $eventId,
        string $eventTime,
        string $ip,
        bool $success,
        string $message,
        string $pushId = ''
    ): void {
        if ($serial === '') {
            return;
        }

        $status = $success ? $message : ('失败:' . $message);
        $logCommId = $commId !== '' ? $commId : $eventId;

        DeviceLog::logDeviceEvent($serial, [
            'device_udid'    => $udid,
            'device_remark'  => $remark,
            'serial_number'  => $serial,
            'operation_type' => '更新设备信息',
            'comm_id'        => $logCommId,
            'push_id'        => $pushId,
            'command_type'   => 'Queries',
            'status'         => $status,
            'confirmed_at'   => null,
            'created_at'     => $eventTime,
            'ip'             => $ip,
        ]);
    }
}
