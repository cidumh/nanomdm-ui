<?php
/**
 * 设备 MDM 指令
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mdm.php';
require_once __DIR__ . '/mdm_client.php';
require_once __DIR__ . '/mdm_device.php';
require_once __DIR__ . '/device_log.php';
require_once __DIR__ . '/policy_device_information.php';
require_once __DIR__ . '/apns_cert.php';
require_once __DIR__ . '/dep_activation_lock.php';
require_once __DIR__ . '/dep.php';
require_once __DIR__ . '/dep_profile.php';
require_once __DIR__ . '/apple_escrow_client.php';
require_once __DIR__ . '/device_detail.php';
require_once __DIR__ . '/profile.php';

class DeviceCommand
{
    public static function sendDeviceInformation(string $serial): array
    {
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
            self::logDeviceInformation($serial, $udid, $remark, '', $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        if (!MdmConfig::isConfigured()) {
            $msg = '请先在 MDM 配置中填写 MDM Server URL';
            self::logDeviceInformation($serial, $udid, $remark, '', $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        $commandContent = PolicyDeviceInformation::buildCommand();
        $result = MdmClient::enqueueCommand($udid, $commandContent);

        if (!$result['ok']) {
            self::logDeviceInformation($serial, $udid, $remark, '', $eventTime, $ip, false, $result['msg']);
            return ['ok' => false, 'msg' => $result['msg']];
        }

        $commId = $result['command_uuid'] ?? ($result['replaced_command_uuid'] ?? '');
        $pushId = is_scalar($result['push_result'] ?? '')
            ? (string) ($result['push_result'] ?? '')
            : json_encode($result['push_result'] ?? '', JSON_UNESCAPED_UNICODE);

        self::logDeviceInformation($serial, $udid, $remark, $commId, $eventTime, $ip, true, '等待响应', $pushId);

        return [
            'ok'           => true,
            'msg'          => '发送更新设备信息成功,等待指令执行完成后刷新数据即可',
            'command_uuid' => $commId,
            'push_id'      => $pushId,
            'request_type' => $result['request_type'] ?? 'DeviceInformation',
        ];
    }

    public static function sendProfileList(string $serial): array
    {
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
            self::logProfileList($serial, $udid, $remark, '', $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        if (!MdmConfig::isConfigured()) {
            $msg = '请先在 MDM 配置中填写 MDM Server URL';
            self::logProfileList($serial, $udid, $remark, '', $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        $commandContent = self::buildProfileListCommand();
        $result = MdmClient::enqueueCommand($udid, $commandContent);

        if (!$result['ok']) {
            self::logProfileList($serial, $udid, $remark, '', $eventTime, $ip, false, $result['msg']);
            return ['ok' => false, 'msg' => $result['msg']];
        }

        $commId = $result['command_uuid'] ?? ($result['replaced_command_uuid'] ?? '');
        $pushId = is_scalar($result['push_result'] ?? '')
            ? (string) ($result['push_result'] ?? '')
            : json_encode($result['push_result'] ?? '', JSON_UNESCAPED_UNICODE);

        self::logProfileList($serial, $udid, $remark, $commId, $eventTime, $ip, true, '等待响应', $pushId);

        return [
            'ok'           => true,
            'msg'          => '更新配置文件列表成功，请刷新设备日志，等待指令执行成功后再刷新配置文件列表',
            'command_uuid' => $commId,
            'push_id'      => $pushId,
            'request_type' => $result['request_type'] ?? 'ProfileList',
        ];
    }

    public static function sendDeviceNameChange(string $serial, string $deviceName): array
    {
        MdmDevice::ensureTables();
        MdmConfig::ensureTables();

        $serial = trim($serial);
        $deviceName = trim($deviceName);

        if ($serial === '') {
            throw new InvalidArgumentException('设备序列号不能为空');
        }
        if ($deviceName === '') {
            throw new InvalidArgumentException('请输入新的设备名称');
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
            self::logDeviceNameChange($serial, $udid, $remark, '', $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        if (!MdmConfig::isConfigured()) {
            $msg = '请先在 MDM 配置中填写 MDM Server URL';
            self::logDeviceNameChange($serial, $udid, $remark, '', $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        $commandContent = self::buildDeviceNameCommand($deviceName);
        $result = MdmClient::enqueueCommand($udid, $commandContent);

        if (!$result['ok']) {
            self::logDeviceNameChange($serial, $udid, $remark, '', $eventTime, $ip, false, $result['msg']);
            return ['ok' => false, 'msg' => $result['msg']];
        }

        $commId = $result['command_uuid'] ?? ($result['replaced_command_uuid'] ?? '');
        $pushId = is_scalar($result['push_result'] ?? '')
            ? (string) ($result['push_result'] ?? '')
            : json_encode($result['push_result'] ?? '', JSON_UNESCAPED_UNICODE);

        self::logDeviceNameChange($serial, $udid, $remark, $commId, $eventTime, $ip, true, '等待响应', $pushId);

        return [
            'ok'           => true,
            'msg'          => '发送修改设备名称成功',
            'command_uuid' => $commId,
            'push_id'      => $pushId,
            'request_type' => $result['request_type'] ?? 'Settings',
            'device_name'  => $deviceName,
        ];
    }

    public static function sendWallpaperChange(string $serial, string $imageBase64): array
    {
        MdmDevice::ensureTables();
        MdmConfig::ensureTables();

        $serial = trim($serial);
        if ($serial === '') {
            throw new InvalidArgumentException('设备序列号不能为空');
        }

        $imageBase64 = self::normalizeImageBase64($imageBase64);

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
            self::logWallpaperChange($serial, $udid, $remark, '', $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        if (!MdmConfig::isConfigured()) {
            $msg = '请先在 MDM 配置中填写 MDM Server URL';
            self::logWallpaperChange($serial, $udid, $remark, '', $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        $commandContent = self::buildWallpaperCommand($imageBase64);
        $result = MdmClient::enqueueCommand($udid, $commandContent);

        if (!$result['ok']) {
            self::logWallpaperChange($serial, $udid, $remark, '', $eventTime, $ip, false, $result['msg']);
            return ['ok' => false, 'msg' => $result['msg']];
        }

        $commId = $result['command_uuid'] ?? ($result['replaced_command_uuid'] ?? '');
        $pushId = is_scalar($result['push_result'] ?? '')
            ? (string) ($result['push_result'] ?? '')
            : json_encode($result['push_result'] ?? '', JSON_UNESCAPED_UNICODE);

        self::logWallpaperChange($serial, $udid, $remark, $commId, $eventTime, $ip, true, '等待响应', $pushId);

        return [
            'ok'           => true,
            'msg'          => '发送修改壁纸成功',
            'command_uuid' => $commId,
            'push_id'      => $pushId,
            'request_type' => $result['request_type'] ?? 'Settings',
        ];
    }

    public static function sendDeviceConnect(string $serial): array
    {
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
            self::logDeviceConnect($serial, $udid, $remark, $eventTime, $ip, false, $msg, '');
            return ['ok' => false, 'msg' => $msg];
        }

        if (!MdmConfig::isConfigured()) {
            $msg = '请先在 MDM 配置中填写 MDM Server URL';
            self::logDeviceConnect($serial, $udid, $remark, $eventTime, $ip, false, $msg, '');
            return ['ok' => false, 'msg' => $msg];
        }

        $result = MdmClient::pushDevice($udid);

        if (!$result['ok']) {
            self::logDeviceConnect($serial, $udid, $remark, $eventTime, $ip, false, $result['msg'], '');
            return ['ok' => false, 'msg' => $result['msg']];
        }

        $pushId = is_scalar($result['push_result'] ?? '')
            ? (string) ($result['push_result'] ?? '')
            : json_encode($result['push_result'] ?? '', JSON_UNESCAPED_UNICODE);

        self::logDeviceConnect($serial, $udid, $remark, $eventTime, $ip, true, '完成', $pushId);

        return [
            'ok'      => true,
            'msg'     => '发送设备连接成功',
            'push_id' => $pushId,
        ];
    }

    private static function logDeviceConnect(
        string $serial,
        string $udid,
        string $remark,
        string $eventTime,
        string $ip,
        bool $success,
        string $message,
        string $pushId
    ): void {
        if ($serial === '') {
            return;
        }

        $status = $success ? $message : ('失败:' . $message);

        DeviceLog::logDeviceEvent($serial, [
            'device_udid'    => $udid,
            'device_remark'  => $remark,
            'serial_number'  => $serial,
            'operation_type' => '发送设备连接',
            'comm_id'        => '',
            'push_id'        => $pushId,
            'command_type'   => 'PUSH',
            'status'         => $status,
            'confirmed_at'   => $success ? $eventTime : null,
            'created_at'     => $eventTime,
            'ip'             => $ip,
        ]);
    }

    public static function sendDeviceConfigured(string $serial): array
    {
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
            self::logDeviceConfigured($serial, $udid, $remark, '', $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        if (!MdmConfig::isConfigured()) {
            $msg = '请先在 MDM 配置中填写 MDM Server URL';
            self::logDeviceConfigured($serial, $udid, $remark, '', $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        $commandContent = self::buildDeviceConfiguredCommand();
        $result = MdmClient::enqueueCommand($udid, $commandContent);

        if (!$result['ok']) {
            self::logDeviceConfigured($serial, $udid, $remark, '', $eventTime, $ip, false, $result['msg']);
            return ['ok' => false, 'msg' => $result['msg']];
        }

        $commId = $result['command_uuid'] ?? ($result['replaced_command_uuid'] ?? '');
        $pushId = is_scalar($result['push_result'] ?? '')
            ? (string) ($result['push_result'] ?? '')
            : json_encode($result['push_result'] ?? '', JSON_UNESCAPED_UNICODE);

        self::logDeviceConfigured($serial, $udid, $remark, $commId, $eventTime, $ip, true, '等待响应', $pushId);

        return [
            'ok'           => true,
            'msg'          => '发送配置完成成功',
            'command_uuid' => $commId,
            'push_id'      => $pushId,
            'request_type' => $result['request_type'] ?? 'DeviceConfigured',
        ];
    }

    public static function sendRestartDevice(string $serial): array
    {
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
            self::logRestartDevice($serial, $udid, $remark, '', $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        if (!MdmConfig::isConfigured()) {
            $msg = '请先在 MDM 配置中填写 MDM Server URL';
            self::logRestartDevice($serial, $udid, $remark, '', $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        $commandContent = self::buildRestartDeviceCommand();
        $result = MdmClient::enqueueCommand($udid, $commandContent);

        if (!$result['ok']) {
            self::logRestartDevice($serial, $udid, $remark, '', $eventTime, $ip, false, $result['msg']);
            return ['ok' => false, 'msg' => $result['msg']];
        }

        $commId = $result['command_uuid'] ?? ($result['replaced_command_uuid'] ?? '');
        $pushId = is_scalar($result['push_result'] ?? '')
            ? (string) ($result['push_result'] ?? '')
            : json_encode($result['push_result'] ?? '', JSON_UNESCAPED_UNICODE);

        self::logRestartDevice($serial, $udid, $remark, $commId, $eventTime, $ip, true, '等待响应', $pushId);

        return [
            'ok'           => true,
            'msg'          => '发送重启设备成功',
            'command_uuid' => $commId,
            'push_id'      => $pushId,
            'request_type' => $result['request_type'] ?? 'RestartDevice',
        ];
    }

    public static function sendDeviceLocation(string $serial): array
    {
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
            self::logDeviceLocation($serial, $udid, $remark, '', $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        if (!MdmConfig::isConfigured()) {
            $msg = '请先在 MDM 配置中填写 MDM Server URL';
            self::logDeviceLocation($serial, $udid, $remark, '', $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        $commandContent = self::buildDeviceLocationCommand();
        $result = MdmClient::enqueueCommand($udid, $commandContent);

        if (!$result['ok']) {
            self::logDeviceLocation($serial, $udid, $remark, '', $eventTime, $ip, false, $result['msg']);
            return ['ok' => false, 'msg' => $result['msg']];
        }

        $commId = $result['command_uuid'] ?? ($result['replaced_command_uuid'] ?? '');
        $pushId = is_scalar($result['push_result'] ?? '')
            ? (string) ($result['push_result'] ?? '')
            : json_encode($result['push_result'] ?? '', JSON_UNESCAPED_UNICODE);

        self::logDeviceLocation($serial, $udid, $remark, $commId, $eventTime, $ip, true, '等待响应', $pushId);

        return [
            'ok'           => true,
            'msg'          => '发送获取位置成功',
            'command_uuid' => $commId,
            'push_id'      => $pushId,
            'request_type' => $result['request_type'] ?? 'DeviceLocation',
        ];
    }

    public static function sendPlayLostModeSound(string $serial): array
    {
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
            self::logPlayLostModeSound($serial, $udid, $remark, '', $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        if (!MdmConfig::isConfigured()) {
            $msg = '请先在 MDM 配置中填写 MDM Server URL';
            self::logPlayLostModeSound($serial, $udid, $remark, '', $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        $commandContent = self::buildPlayLostModeSoundCommand();
        $result = MdmClient::enqueueCommand($udid, $commandContent);

        if (!$result['ok']) {
            self::logPlayLostModeSound($serial, $udid, $remark, '', $eventTime, $ip, false, $result['msg']);
            return ['ok' => false, 'msg' => $result['msg']];
        }

        $commId = $result['command_uuid'] ?? ($result['replaced_command_uuid'] ?? '');
        $pushId = is_scalar($result['push_result'] ?? '')
            ? (string) ($result['push_result'] ?? '')
            : json_encode($result['push_result'] ?? '', JSON_UNESCAPED_UNICODE);

        self::logPlayLostModeSound($serial, $udid, $remark, $commId, $eventTime, $ip, true, '等待响应', $pushId);

        return [
            'ok'           => true,
            'msg'          => '发送播放声音成功',
            'command_uuid' => $commId,
            'push_id'      => $pushId,
            'request_type' => $result['request_type'] ?? 'PlayLostModeSound',
        ];
    }

    public static function sendClearPasscode(string $serial): array
    {
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

        $token = preg_replace('/\s+/', '', trim((string) ($device['token'] ?? '')));
        if ($token === '') {
            throw new InvalidArgumentException('设备 Token 未同步，无法清除密码和面容');
        }

        $udid = trim((string) ($device['udid'] ?? ''));
        $remark = trim((string) ($device['remark'] ?? ''));
        $eventTime = date('Y-m-d H:i:s');
        $ip = clientIp();

        if ($udid === '') {
            $msg = '设备 UDID 为空';
            self::logClearPasscode($serial, $udid, $remark, '', $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        if (!MdmConfig::isConfigured()) {
            $msg = '请先在 MDM 配置中填写 MDM Server URL';
            self::logClearPasscode($serial, $udid, $remark, '', $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        $commandContent = self::buildClearPasscodeCommand($token);
        $result = MdmClient::enqueueCommand($udid, $commandContent);

        if (!$result['ok']) {
            self::logClearPasscode($serial, $udid, $remark, '', $eventTime, $ip, false, $result['msg']);
            return ['ok' => false, 'msg' => $result['msg']];
        }

        $commId = $result['command_uuid'] ?? ($result['replaced_command_uuid'] ?? '');
        $pushId = is_scalar($result['push_result'] ?? '')
            ? (string) ($result['push_result'] ?? '')
            : json_encode($result['push_result'] ?? '', JSON_UNESCAPED_UNICODE);

        self::logClearPasscode($serial, $udid, $remark, $commId, $eventTime, $ip, true, '等待响应', $pushId);

        return [
            'ok'           => true,
            'msg'          => '发送清除密码和面容成功',
            'command_uuid' => $commId,
            'push_id'      => $pushId,
            'request_type' => $result['request_type'] ?? 'ClearPasscode',
        ];
    }

    public static function sendEnableActivationLock(string $serial, string $lostMessage, ?int $userId = null): array
    {
        MdmDevice::ensureTables();
        DepConfig::ensureTables();
        DepActivationLock::ensureTables();

        $serial = trim($serial);
        $lostMessage = trim($lostMessage);

        if ($serial === '') {
            throw new InvalidArgumentException('设备序列号不能为空');
        }
        if ($lostMessage === '') {
            throw new InvalidArgumentException('请填写丢失提示内容');
        }
        if (mb_strlen($lostMessage) > 500) {
            throw new InvalidArgumentException('丢失提示内容不能超过 500 字');
        }

        $device = MdmDevice::findBySerial($serial);
        if (!$device) {
            throw new InvalidArgumentException('设备不存在');
        }

        $udid = trim((string) ($device['udid'] ?? ''));
        $remark = trim((string) ($device['remark'] ?? ''));
        $eventTime = date('Y-m-d H:i:s');
        $ip = clientIp();

        if (!DepConfig::isConfigured()) {
            $msg = 'DEP 未配置，无法开启激活锁';
            self::logEnableActivationLock($serial, $udid, $remark, '', $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        $generated = depGenerateActivationLockBypass();
        $bypassCode = $generated['bypass_code'];
        $escrowKey = $generated['escrow_key'];

        $result = DepClient::enableActivationLock($serial, $escrowKey, $lostMessage);

        if (!$result['ok']) {
            self::logEnableActivationLock($serial, $udid, $remark, '', $eventTime, $ip, false, $result['msg']);
            return ['ok' => false, 'msg' => $result['msg']];
        }

        $data = $result['data'] ?? [];
        $status = strtoupper((string) ($data['response_status'] ?? ''));

        if ($status !== 'SUCCESS') {
            $msg = $status !== '' ? ('DEP 返回状态：' . $status) : 'DEP 服务器响应异常';
            self::logEnableActivationLock($serial, $udid, $remark, '', $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg, 'response_status' => $status];
        }

        try {
            DepActivationLock::save($serial, $bypassCode, $escrowKey, $lostMessage, $userId);
            MdmDevice::setActivationLockStatus($serial, true);
        } catch (Exception $e) {
            $msg = '激活锁已开启，但本地绕过码保存失败：' . $e->getMessage();
            self::logEnableActivationLock($serial, $udid, $remark, '', $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        self::logEnableActivationLock($serial, $udid, $remark, '', $eventTime, $ip, true, '完成');

        return [
            'ok'              => true,
            'msg'             => '设备 ' . $serial . ' 激活锁已成功开启',
            'request_type'    => 'activationlock',
            'serial_number'   => $data['serial_number'] ?? $serial,
            'response_status' => $status,
        ];
    }

    public static function sendDisableActivationLock(string $serial, string $orgName, string $guid): array
    {
        MdmDevice::ensureTables();
        ApnsCert::ensureTables();
        DepActivationLock::ensureTables();

        $serial = trim($serial);
        $orgName = trim($orgName);
        $guid = trim($guid);

        if ($serial === '') {
            throw new InvalidArgumentException('设备序列号不能为空');
        }
        if ($orgName === '') {
            throw new InvalidArgumentException('请输入组织名称');
        }
        if ($guid === '') {
            throw new InvalidArgumentException('请输入姓名');
        }

        $device = MdmDevice::findBySerial($serial);
        if (!$device) {
            throw new InvalidArgumentException('设备不存在');
        }

        $udid = trim((string) ($device['udid'] ?? ''));
        $remark = trim((string) ($device['remark'] ?? ''));
        $eventTime = date('Y-m-d H:i:s');
        $ip = clientIp();

        $lockRecord = DepActivationLock::getBySerial($serial);
        if (!$lockRecord || trim((string) ($lockRecord['bypass_code'] ?? '')) === '') {
            $msg = '未找到设备绕过码，无法关闭激活锁';
            self::logDisableActivationLock($serial, $udid, $remark, '', $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        $topic = trim((string) ($device['topic'] ?? ''));
        if ($topic === '') {
            $msg = '设备 Topic 为空，无法匹配 APNS 证书';
            self::logDisableActivationLock($serial, $udid, $remark, '', $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        $apnsCert = ApnsCert::findPemByTopic($topic);
        if (!$apnsCert) {
            $msg = '未找到与设备 Topic 匹配的 APNS 证书，请先在 APNS 证书管理中添加';
            self::logDisableActivationLock($serial, $udid, $remark, '', $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        $identifiers = DeviceDetail::extractUnlockIdentifiers($device);

        if ($identifiers['product_type'] === '') {
            $msg = '无法获取设备型号标识，请先更新设备信息';
            self::logDisableActivationLock($serial, $udid, $remark, '', $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        $result = AppleEscrowClient::unlock([
            'pem_cert'        => $apnsCert['pem_cert'],
            'pem_private_key' => $apnsCert['pem_private_key'],
            'product_type'    => $identifiers['product_type'],
            'serial'          => $serial,
            'imei'            => $identifiers['imei'],
            'imei2'           => $identifiers['imei2'],
            'meid'            => $identifiers['meid'],
            'escrow_key'      => trim((string) $lockRecord['bypass_code']),
            'org_name'        => $orgName,
            'guid'            => $guid,
        ]);

        if (!$result['ok']) {
            self::logDisableActivationLock($serial, $udid, $remark, '', $eventTime, $ip, false, $result['msg']);
            return ['ok' => false, 'msg' => $result['msg']];
        }

        MdmDevice::setActivationLockStatus($serial, false);
        self::logDisableActivationLock($serial, $udid, $remark, '', $eventTime, $ip, true, '完成');

        return [
            'ok'           => true,
            'msg'          => '关闭激活锁成功',
            'request_type' => 'escrowKeyUnlock',
        ];
    }

    public static function sendEraseDevice(string $serial): array
    {
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
            self::logEraseDevice($serial, $udid, $remark, '', $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        if (!MdmConfig::isConfigured()) {
            $msg = '请先在 MDM 配置中填写 MDM Server URL';
            self::logEraseDevice($serial, $udid, $remark, '', $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        $commandContent = self::buildEraseDeviceCommand();
        $result = MdmClient::enqueueCommand($udid, $commandContent);

        if (!$result['ok']) {
            self::logEraseDevice($serial, $udid, $remark, '', $eventTime, $ip, false, $result['msg']);
            return ['ok' => false, 'msg' => $result['msg']];
        }

        $commId = $result['command_uuid'] ?? ($result['replaced_command_uuid'] ?? '');
        $pushId = is_scalar($result['push_result'] ?? '')
            ? (string) ($result['push_result'] ?? '')
            : json_encode($result['push_result'] ?? '', JSON_UNESCAPED_UNICODE);

        self::logEraseDevice($serial, $udid, $remark, $commId, $eventTime, $ip, true, '等待响应', $pushId);

        return [
            'ok'           => true,
            'msg'          => '发送抹除还原成功',
            'command_uuid' => $commId,
            'push_id'      => $pushId,
            'request_type' => $result['request_type'] ?? 'EraseDevice',
        ];
    }

    private static function buildRestartDeviceCommand(): string
    {
        $lines = [];
        $lines[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $lines[] = '<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">';
        $lines[] = '<plist version="1.0">';
        $lines[] = '<dict>';
        $lines[] = '    <key>Command</key>';
        $lines[] = '    <dict>';
        $lines[] = '        <key>RequestType</key>';
        $lines[] = '        <string>RestartDevice</string>';
        $lines[] = '    </dict>';
        $lines[] = '    <key>CommandUUID</key>';
        $lines[] = '    <string>_CommandUUID_</string>';
        $lines[] = '</dict>';
        $lines[] = '</plist>';

        return implode("\n", $lines) . "\n";
    }

    private static function buildDeviceLocationCommand(): string
    {
        $lines = [];
        $lines[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $lines[] = '<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">';
        $lines[] = '<plist version="1.0">';
        $lines[] = '<dict>';
        $lines[] = '    <key>Command</key>';
        $lines[] = '    <dict>';
        $lines[] = '        <key>RequestType</key>';
        $lines[] = '        <string>DeviceLocation</string>';
        $lines[] = '    </dict>';
        $lines[] = '    <key>CommandUUID</key>';
        $lines[] = '    <string>_CommandUUID_</string>';
        $lines[] = '</dict>';
        $lines[] = '</plist>';

        return implode("\n", $lines) . "\n";
    }

    private static function buildPlayLostModeSoundCommand(): string
    {
        $lines = [];
        $lines[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $lines[] = '<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">';
        $lines[] = '<plist version="1.0">';
        $lines[] = '<dict>';
        $lines[] = '    <key>Command</key>';
        $lines[] = '    <dict>';
        $lines[] = '        <key>RequestType</key>';
        $lines[] = '        <string>PlayLostModeSound</string>';
        $lines[] = '    </dict>';
        $lines[] = '    <key>CommandUUID</key>';
        $lines[] = '    <string>_CommandUUID_</string>';
        $lines[] = '</dict>';
        $lines[] = '</plist>';

        return implode("\n", $lines) . "\n";
    }

    private static function buildClearPasscodeCommand(string $token): string
    {
        $lines = [];
        $lines[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $lines[] = '<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">';
        $lines[] = '<plist version="1.0">';
        $lines[] = '<dict>';
        $lines[] = '    <key>Command</key>';
        $lines[] = '    <dict>';
        $lines[] = '        <key>RequestType</key>';
        $lines[] = '        <string>ClearPasscode</string>';
        $lines[] = '        <key>UnlockToken</key>';
        $lines[] = '        <data>' . $token . '</data>';
        $lines[] = '    </dict>';
        $lines[] = '    <key>CommandUUID</key>';
        $lines[] = '    <string>_CommandUUID_</string>';
        $lines[] = '</dict>';
        $lines[] = '</plist>';

        return implode("\n", $lines) . "\n";
    }

    private static function buildEraseDeviceCommand(): string
    {
        $lines = [];
        $lines[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $lines[] = '<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">';
        $lines[] = '<plist version="1.0">';
        $lines[] = '<dict>';
        $lines[] = '    <key>Command</key>';
        $lines[] = '    <dict>';
        $lines[] = '        <key>DisallowProximitySetup</key>';
        $lines[] = '        <false/>';
        $lines[] = '        <key>PreserveDataPlan</key>';
        $lines[] = '        <true/>';
        $lines[] = '        <key>RequestType</key>';
        $lines[] = '        <string>EraseDevice</string>';
        $lines[] = '    </dict>';
        $lines[] = '    <key>CommandUUID</key>';
        $lines[] = '    <string>_CommandUUID_</string>';
        $lines[] = '</dict>';
        $lines[] = '</plist>';

        return implode("\n", $lines) . "\n";
    }

    private static function logRestartDevice(
        string $serial,
        string $udid,
        string $remark,
        string $commId,
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

        DeviceLog::logDeviceEvent($serial, [
            'device_udid'    => $udid,
            'device_remark'  => $remark,
            'serial_number'  => $serial,
            'operation_type' => '重启设备',
            'comm_id'        => $commId,
            'push_id'        => $pushId,
            'command_type'   => 'RestartDevice',
            'status'         => $status,
            'confirmed_at'   => null,
            'created_at'     => $eventTime,
            'ip'             => $ip,
        ]);
    }

    private static function logDeviceLocation(
        string $serial,
        string $udid,
        string $remark,
        string $commId,
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

        DeviceLog::logDeviceEvent($serial, [
            'device_udid'    => $udid,
            'device_remark'  => $remark,
            'serial_number'  => $serial,
            'operation_type' => '获取位置',
            'comm_id'        => $commId,
            'push_id'        => $pushId,
            'command_type'   => 'DeviceLocation',
            'status'         => $status,
            'confirmed_at'   => null,
            'created_at'     => $eventTime,
            'ip'             => $ip,
        ]);
    }

    private static function logPlayLostModeSound(
        string $serial,
        string $udid,
        string $remark,
        string $commId,
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

        DeviceLog::logDeviceEvent($serial, [
            'device_udid'    => $udid,
            'device_remark'  => $remark,
            'serial_number'  => $serial,
            'operation_type' => '播放声音',
            'comm_id'        => $commId,
            'push_id'        => $pushId,
            'command_type'   => 'PlayLostModeSound',
            'status'         => $status,
            'confirmed_at'   => null,
            'created_at'     => $eventTime,
            'ip'             => $ip,
        ]);
    }

    private static function logClearPasscode(
        string $serial,
        string $udid,
        string $remark,
        string $commId,
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

        DeviceLog::logDeviceEvent($serial, [
            'device_udid'    => $udid,
            'device_remark'  => $remark,
            'serial_number'  => $serial,
            'operation_type' => '清除密码和面容',
            'comm_id'        => $commId,
            'push_id'        => $pushId,
            'command_type'   => 'ClearPasscode',
            'status'         => $status,
            'confirmed_at'   => null,
            'created_at'     => $eventTime,
            'ip'             => $ip,
        ]);
    }

    private static function logEnableActivationLock(
        string $serial,
        string $udid,
        string $remark,
        string $commId,
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

        DeviceLog::logDeviceEvent($serial, [
            'device_udid'    => $udid,
            'device_remark'  => $remark,
            'serial_number'  => $serial,
            'operation_type' => '开启激活锁',
            'comm_id'        => $commId,
            'push_id'        => $pushId,
            'command_type'   => 'activationlock',
            'status'         => $status,
            'confirmed_at'   => $success ? $eventTime : null,
            'created_at'     => $eventTime,
            'ip'             => $ip,
        ]);
    }

    private static function logDisableActivationLock(
        string $serial,
        string $udid,
        string $remark,
        string $commId,
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

        DeviceLog::logDeviceEvent($serial, [
            'device_udid'    => $udid,
            'device_remark'  => $remark,
            'serial_number'  => $serial,
            'operation_type' => '关闭激活锁',
            'comm_id'        => $commId,
            'push_id'        => $pushId,
            'command_type'   => 'escrowKeyUnlock',
            'status'         => $status,
            'confirmed_at'   => $success ? $eventTime : null,
            'created_at'     => $eventTime,
            'ip'             => $ip,
        ]);
    }

    private static function logEraseDevice(
        string $serial,
        string $udid,
        string $remark,
        string $commId,
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

        DeviceLog::logDeviceEvent($serial, [
            'device_udid'    => $udid,
            'device_remark'  => $remark,
            'serial_number'  => $serial,
            'operation_type' => '抹除还原',
            'comm_id'        => $commId,
            'push_id'        => $pushId,
            'command_type'   => 'EraseDevice',
            'status'         => $status,
            'confirmed_at'   => null,
            'created_at'     => $eventTime,
            'ip'             => $ip,
        ]);
    }

    public static function sendShutDownDevice(string $serial): array
    {
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
            self::logShutDownDevice($serial, $udid, $remark, '', $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        if (!MdmConfig::isConfigured()) {
            $msg = '请先在 MDM 配置中填写 MDM Server URL';
            self::logShutDownDevice($serial, $udid, $remark, '', $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        $commandContent = self::buildShutDownDeviceCommand();
        $result = MdmClient::enqueueCommand($udid, $commandContent);

        if (!$result['ok']) {
            self::logShutDownDevice($serial, $udid, $remark, '', $eventTime, $ip, false, $result['msg']);
            return ['ok' => false, 'msg' => $result['msg']];
        }

        $commId = $result['command_uuid'] ?? ($result['replaced_command_uuid'] ?? '');
        $pushId = is_scalar($result['push_result'] ?? '')
            ? (string) ($result['push_result'] ?? '')
            : json_encode($result['push_result'] ?? '', JSON_UNESCAPED_UNICODE);

        self::logShutDownDevice($serial, $udid, $remark, $commId, $eventTime, $ip, true, '等待响应', $pushId);

        return [
            'ok'           => true,
            'msg'          => '发送关闭设备成功',
            'command_uuid' => $commId,
            'push_id'      => $pushId,
            'request_type' => $result['request_type'] ?? 'ShutDownDevice',
        ];
    }

    public static function sendDeviceLock(string $serial, string $message, string $phoneNumber): array
    {
        MdmDevice::ensureTables();
        MdmConfig::ensureTables();

        $serial = trim($serial);
        $message = trim($message);
        $phoneNumber = trim($phoneNumber);

        if ($serial === '') {
            throw new InvalidArgumentException('设备序列号不能为空');
        }
        if ($message === '') {
            throw new InvalidArgumentException('请输入锁定时显示的信息内容');
        }
        if ($phoneNumber === '') {
            throw new InvalidArgumentException('请输入联系号码');
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
            self::logDeviceLock($serial, $udid, $remark, '', $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        if (!MdmConfig::isConfigured()) {
            $msg = '请先在 MDM 配置中填写 MDM Server URL';
            self::logDeviceLock($serial, $udid, $remark, '', $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        $commandContent = self::buildDeviceLockCommand($message, $phoneNumber);
        $result = MdmClient::enqueueCommand($udid, $commandContent);

        if (!$result['ok']) {
            self::logDeviceLock($serial, $udid, $remark, '', $eventTime, $ip, false, $result['msg']);
            return ['ok' => false, 'msg' => $result['msg']];
        }

        $commId = $result['command_uuid'] ?? ($result['replaced_command_uuid'] ?? '');
        $pushId = is_scalar($result['push_result'] ?? '')
            ? (string) ($result['push_result'] ?? '')
            : json_encode($result['push_result'] ?? '', JSON_UNESCAPED_UNICODE);

        self::logDeviceLock($serial, $udid, $remark, $commId, $eventTime, $ip, true, '等待响应', $pushId);

        return [
            'ok'           => true,
            'msg'          => '发送锁定设备成功',
            'command_uuid' => $commId,
            'push_id'      => $pushId,
            'request_type' => $result['request_type'] ?? 'DeviceLock',
        ];
    }

    public static function sendEnableLostMode(string $serial, string $footnote, string $message, string $phoneNumber): array
    {
        MdmDevice::ensureTables();
        MdmConfig::ensureTables();

        $serial = trim($serial);
        $footnote = trim($footnote);
        $message = trim($message);
        $phoneNumber = trim($phoneNumber);

        if ($serial === '') {
            throw new InvalidArgumentException('设备序列号不能为空');
        }
        if ($footnote === '') {
            throw new InvalidArgumentException('请输入底部显示信息');
        }
        if ($message === '') {
            throw new InvalidArgumentException('请输入提示显示的信息');
        }
        if ($phoneNumber === '') {
            throw new InvalidArgumentException('请输入联系号码');
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
            self::logEnableLostMode($serial, $udid, $remark, '', $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        if (!MdmConfig::isConfigured()) {
            $msg = '请先在 MDM 配置中填写 MDM Server URL';
            self::logEnableLostMode($serial, $udid, $remark, '', $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        $commandContent = self::buildEnableLostModeCommand($footnote, $message, $phoneNumber);
        $result = MdmClient::enqueueCommand($udid, $commandContent);

        if (!$result['ok']) {
            self::logEnableLostMode($serial, $udid, $remark, '', $eventTime, $ip, false, $result['msg']);
            return ['ok' => false, 'msg' => $result['msg']];
        }

        $commId = $result['command_uuid'] ?? ($result['replaced_command_uuid'] ?? '');
        $pushId = is_scalar($result['push_result'] ?? '')
            ? (string) ($result['push_result'] ?? '')
            : json_encode($result['push_result'] ?? '', JSON_UNESCAPED_UNICODE);

        self::logEnableLostMode($serial, $udid, $remark, $commId, $eventTime, $ip, true, '等待响应', $pushId);

        return [
            'ok'           => true,
            'msg'          => '发送丢失锁机成功',
            'command_uuid' => $commId,
            'push_id'      => $pushId,
            'request_type' => $result['request_type'] ?? 'EnableLostMode',
        ];
    }

    public static function sendDisableLostMode(string $serial): array
    {
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
            self::logDisableLostMode($serial, $udid, $remark, '', $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        if (!MdmConfig::isConfigured()) {
            $msg = '请先在 MDM 配置中填写 MDM Server URL';
            self::logDisableLostMode($serial, $udid, $remark, '', $eventTime, $ip, false, $msg);
            return ['ok' => false, 'msg' => $msg];
        }

        $commandContent = self::buildDisableLostModeCommand();
        $result = MdmClient::enqueueCommand($udid, $commandContent);

        if (!$result['ok']) {
            self::logDisableLostMode($serial, $udid, $remark, '', $eventTime, $ip, false, $result['msg']);
            return ['ok' => false, 'msg' => $result['msg']];
        }

        $commId = $result['command_uuid'] ?? ($result['replaced_command_uuid'] ?? '');
        $pushId = is_scalar($result['push_result'] ?? '')
            ? (string) ($result['push_result'] ?? '')
            : json_encode($result['push_result'] ?? '', JSON_UNESCAPED_UNICODE);

        self::logDisableLostMode($serial, $udid, $remark, $commId, $eventTime, $ip, true, '等待响应', $pushId);

        return [
            'ok'           => true,
            'msg'          => '发送解除丢失锁机成功',
            'command_uuid' => $commId,
            'push_id'      => $pushId,
            'request_type' => $result['request_type'] ?? 'DisableLostMode',
        ];
    }

    private static function buildShutDownDeviceCommand(): string
    {
        $lines = [];
        $lines[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $lines[] = '<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">';
        $lines[] = '<plist version="1.0">';
        $lines[] = '<dict>';
        $lines[] = '    <key>Command</key>';
        $lines[] = '    <dict>';
        $lines[] = '        <key>RequestType</key>';
        $lines[] = '        <string>ShutDownDevice</string>';
        $lines[] = '    </dict>';
        $lines[] = '    <key>CommandUUID</key>';
        $lines[] = '    <string>_CommandUUID_</string>';
        $lines[] = '</dict>';
        $lines[] = '</plist>';

        return implode("\n", $lines) . "\n";
    }

    private static function buildDeviceLockCommand(string $message, string $phoneNumber): string
    {
        $escapedMessage = htmlspecialchars($message, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $escapedPhone = htmlspecialchars($phoneNumber, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        $lines = [];
        $lines[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $lines[] = '<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">';
        $lines[] = '<plist version="1.0">';
        $lines[] = '<dict>';
        $lines[] = '    <key>Command</key>';
        $lines[] = '    <dict>';
        $lines[] = '        <key>Message</key>';
        $lines[] = '        <string>' . $escapedMessage . '</string>';
        $lines[] = '        <key>PhoneNumber</key>';
        $lines[] = '        <string>' . $escapedPhone . '</string>';
        $lines[] = '        <key>RequestType</key>';
        $lines[] = '        <string>DeviceLock</string>';
        $lines[] = '    </dict>';
        $lines[] = '    <key>CommandUUID</key>';
        $lines[] = '    <string>_CommandUUID_</string>';
        $lines[] = '</dict>';
        $lines[] = '</plist>';

        return implode("\n", $lines) . "\n";
    }

    private static function buildEnableLostModeCommand(string $footnote, string $message, string $phoneNumber): string
    {
        $escapedFootnote = htmlspecialchars($footnote, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $escapedMessage = htmlspecialchars($message, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $escapedPhone = htmlspecialchars($phoneNumber, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        $lines = [];
        $lines[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $lines[] = '<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">';
        $lines[] = '<plist version="1.0">';
        $lines[] = '<dict>';
        $lines[] = '    <key>Command</key>';
        $lines[] = '    <dict>';
        $lines[] = '        <key>Footnote</key>';
        $lines[] = '        <string>' . $escapedFootnote . '</string>';
        $lines[] = '        <key>Message</key>';
        $lines[] = '        <string>' . $escapedMessage . '</string>';
        $lines[] = '        <key>PhoneNumber</key>';
        $lines[] = '        <string>' . $escapedPhone . '</string>';
        $lines[] = '        <key>RequestType</key>';
        $lines[] = '        <string>EnableLostMode</string>';
        $lines[] = '    </dict>';
        $lines[] = '    <key>CommandUUID</key>';
        $lines[] = '    <string>_CommandUUID_</string>';
        $lines[] = '</dict>';
        $lines[] = '</plist>';

        return implode("\n", $lines) . "\n";
    }

    private static function buildDisableLostModeCommand(): string
    {
        $lines = [];
        $lines[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $lines[] = '<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">';
        $lines[] = '<plist version="1.0">';
        $lines[] = '<dict>';
        $lines[] = '    <key>Command</key>';
        $lines[] = '    <dict>';
        $lines[] = '        <key>RequestType</key>';
        $lines[] = '        <string>DisableLostMode</string>';
        $lines[] = '    </dict>';
        $lines[] = '    <key>CommandUUID</key>';
        $lines[] = '    <string>_CommandUUID_</string>';
        $lines[] = '</dict>';
        $lines[] = '</plist>';

        return implode("\n", $lines) . "\n";
    }

    private static function logShutDownDevice(
        string $serial,
        string $udid,
        string $remark,
        string $commId,
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

        DeviceLog::logDeviceEvent($serial, [
            'device_udid'    => $udid,
            'device_remark'  => $remark,
            'serial_number'  => $serial,
            'operation_type' => '关闭设备',
            'comm_id'        => $commId,
            'push_id'        => $pushId,
            'command_type'   => 'ShutDownDevice',
            'status'         => $status,
            'confirmed_at'   => null,
            'created_at'     => $eventTime,
            'ip'             => $ip,
        ]);
    }

    private static function logDeviceLock(
        string $serial,
        string $udid,
        string $remark,
        string $commId,
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

        DeviceLog::logDeviceEvent($serial, [
            'device_udid'    => $udid,
            'device_remark'  => $remark,
            'serial_number'  => $serial,
            'operation_type' => '锁定设备',
            'comm_id'        => $commId,
            'push_id'        => $pushId,
            'command_type'   => 'DeviceLock',
            'status'         => $status,
            'confirmed_at'   => null,
            'created_at'     => $eventTime,
            'ip'             => $ip,
        ]);
    }

    private static function logEnableLostMode(
        string $serial,
        string $udid,
        string $remark,
        string $commId,
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

        DeviceLog::logDeviceEvent($serial, [
            'device_udid'    => $udid,
            'device_remark'  => $remark,
            'serial_number'  => $serial,
            'operation_type' => '丢失锁机',
            'comm_id'        => $commId,
            'push_id'        => $pushId,
            'command_type'   => 'EnableLostMode',
            'status'         => $status,
            'confirmed_at'   => null,
            'created_at'     => $eventTime,
            'ip'             => $ip,
        ]);
    }

    private static function logDisableLostMode(
        string $serial,
        string $udid,
        string $remark,
        string $commId,
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

        DeviceLog::logDeviceEvent($serial, [
            'device_udid'    => $udid,
            'device_remark'  => $remark,
            'serial_number'  => $serial,
            'operation_type' => '解除丢失锁机',
            'comm_id'        => $commId,
            'push_id'        => $pushId,
            'command_type'   => 'DisableLostMode',
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
        $lines[] = '    <key>Command</key>';
        $lines[] = '    <dict>';
        $lines[] = '        <key>RequestType</key>';
        $lines[] = '        <string>DeviceConfigured</string>';
        $lines[] = '    </dict>';
        $lines[] = '    <key>CommandUUID</key>';
        $lines[] = '    <string>_CommandUUID_</string>';
        $lines[] = '</dict>';
        $lines[] = '</plist>';

        return implode("\n", $lines) . "\n";
    }

    private static function buildProfileListCommand(): string
    {
        $lines = [];
        $lines[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $lines[] = '<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">';
        $lines[] = '<plist version="1.0">';
        $lines[] = '<dict>';
        $lines[] = '    <key>Command</key>';
        $lines[] = '    <dict>';
        $lines[] = '        <key>ManagedOnly</key>';
        $lines[] = '        <false/>';
        $lines[] = '        <key>RequestType</key>';
        $lines[] = '        <string>ProfileList</string>';
        $lines[] = '    </dict>';
        $lines[] = '    <key>CommandUUID</key>';
        $lines[] = '    <string>_CommandUUID_</string>';
        $lines[] = '</dict>';
        $lines[] = '</plist>';

        return implode("\n", $lines) . "\n";
    }

    private static function logDeviceConfigured(
        string $serial,
        string $udid,
        string $remark,
        string $commId,
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

        DeviceLog::logDeviceEvent($serial, [
            'device_udid'    => $udid,
            'device_remark'  => $remark,
            'serial_number'  => $serial,
            'operation_type' => '配置完成',
            'comm_id'        => $commId,
            'push_id'        => $pushId,
            'command_type'   => 'DeviceConfigured',
            'status'         => $status,
            'confirmed_at'   => null,
            'created_at'     => $eventTime,
            'ip'             => $ip,
        ]);
    }

    private static function normalizeImageBase64(string $imageBase64): string
    {
        $imageBase64 = trim($imageBase64);
        if ($imageBase64 === '') {
            throw new InvalidArgumentException('请选择壁纸图片');
        }

        if (preg_match('#^data:image/[^;]+;base64,(.+)$#si', $imageBase64, $matches)) {
            $imageBase64 = $matches[1];
        }

        $imageBase64 = preg_replace('/\s+/', '', $imageBase64);
        if ($imageBase64 === '') {
            throw new InvalidArgumentException('壁纸图片编码无效');
        }

        $decoded = base64_decode($imageBase64, true);
        if ($decoded === false) {
            throw new InvalidArgumentException('壁纸图片编码无效');
        }

        if (strlen($decoded) > 10 * 1024 * 1024) {
            throw new InvalidArgumentException('壁纸图片过大，请选择 10MB 以内的图片');
        }

        return $imageBase64;
    }

    private static function buildWallpaperCommand(string $imageBase64): string
    {
        $lines = [];
        $lines[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $lines[] = '<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">';
        $lines[] = '<plist version="1.0">';
        $lines[] = '<dict>';
        $lines[] = '    <key>Command</key>';
        $lines[] = '    <dict>';
        $lines[] = '        <key>RequestType</key>';
        $lines[] = '        <string>Settings</string>';
        $lines[] = '        <key>Settings</key>';
        $lines[] = '        <array>';
        $lines[] = '            <dict>';
        $lines[] = '                <key>Image</key>';
        $lines[] = '                <data>' . $imageBase64 . '</data>';
        $lines[] = '                <key>Item</key>';
        $lines[] = '                <string>Wallpaper</string>';
        $lines[] = '                <key>Where</key>';
        $lines[] = '                <integer>3</integer>';
        $lines[] = '            </dict>';
        $lines[] = '        </array>';
        $lines[] = '    </dict>';
        $lines[] = '    <key>CommandUUID</key>';
        $lines[] = '    <string>_CommandUUID_</string>';
        $lines[] = '</dict>';
        $lines[] = '</plist>';

        return implode("\n", $lines) . "\n";
    }

    private static function buildDeviceNameCommand(string $deviceName): string
    {
        $escaped = htmlspecialchars($deviceName, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        $lines = [];
        $lines[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $lines[] = '<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">';
        $lines[] = '<plist version="1.0">';
        $lines[] = '<dict>';
        $lines[] = '    <key>Command</key>';
        $lines[] = '    <dict>';
        $lines[] = '        <key>RequestType</key>';
        $lines[] = '        <string>Settings</string>';
        $lines[] = '        <key>Settings</key>';
        $lines[] = '        <array>';
        $lines[] = '            <dict>';
        $lines[] = '                <key>DeviceName</key>';
        $lines[] = '                <string>' . $escaped . '</string>';
        $lines[] = '                <key>Item</key>';
        $lines[] = '                <string>DeviceName</string>';
        $lines[] = '            </dict>';
        $lines[] = '        </array>';
        $lines[] = '    </dict>';
        $lines[] = '    <key>CommandUUID</key>';
        $lines[] = '    <string>_CommandUUID_</string>';
        $lines[] = '</dict>';
        $lines[] = '</plist>';

        return implode("\n", $lines) . "\n";
    }

    private static function logWallpaperChange(
        string $serial,
        string $udid,
        string $remark,
        string $commId,
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

        DeviceLog::logDeviceEvent($serial, [
            'device_udid'    => $udid,
            'device_remark'  => $remark,
            'serial_number'  => $serial,
            'operation_type' => '修改壁纸',
            'comm_id'        => $commId,
            'push_id'        => $pushId,
            'command_type'   => 'Settings',
            'status'         => $status,
            'confirmed_at'   => null,
            'created_at'     => $eventTime,
            'ip'             => $ip,
        ]);
    }

    private static function logDeviceNameChange(
        string $serial,
        string $udid,
        string $remark,
        string $commId,
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

        DeviceLog::logDeviceEvent($serial, [
            'device_udid'    => $udid,
            'device_remark'  => $remark,
            'serial_number'  => $serial,
            'operation_type' => '修改设备名称',
            'comm_id'        => $commId,
            'push_id'        => $pushId,
            'command_type'   => 'DeviceName',
            'status'         => $status,
            'confirmed_at'   => null,
            'created_at'     => $eventTime,
            'ip'             => $ip,
        ]);
    }

    private static function logDeviceInformation(
        string $serial,
        string $udid,
        string $remark,
        string $commId,
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

        DeviceLog::logDeviceEvent($serial, [
            'device_udid'    => $udid,
            'device_remark'  => $remark,
            'serial_number'  => $serial,
            'operation_type' => '更新设备信息',
            'comm_id'        => $commId,
            'push_id'        => $pushId,
            'command_type'   => 'Queries',
            'status'         => $status,
            'confirmed_at'   => null,
            'created_at'     => $eventTime,
            'ip'             => $ip,
        ]);
    }

    private static function logProfileList(
        string $serial,
        string $udid,
        string $remark,
        string $commId,
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

        DeviceLog::logDeviceEvent($serial, [
            'device_udid'    => $udid,
            'device_remark'  => $remark,
            'serial_number'  => $serial,
            'operation_type' => '更新配置文件列表',
            'comm_id'        => $commId,
            'push_id'        => $pushId,
            'command_type'   => 'ProfileList',
            'status'         => $status,
            'confirmed_at'   => null,
            'created_at'     => $eventTime,
            'ip'             => $ip,
        ]);
    }
}
