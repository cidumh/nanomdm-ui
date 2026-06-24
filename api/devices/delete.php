<?php

require_once __DIR__ . '/../../includes/bootstrap.php';

require_once __DIR__ . '/../../includes/db.php';

require_once __DIR__ . '/../../includes/auth.php';

require_once __DIR__ . '/../../includes/mdm_device.php';

require_once __DIR__ . '/../../includes/device_log.php';



if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    jsonResponse(405, '请求方式不允许');

}



if (!isInstalled()) {

    jsonResponse(503, '系统尚未安装');

}



$session = Auth::requireLogin();



$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {

    $input = $_POST;

}



$serial = trim($input['serial_number'] ?? '');



try {

    $device = MdmDevice::findBySerial($serial);

    if (!$device) {

        throw new InvalidArgumentException('设备不存在');

    }



    $result = MdmDevice::deleteBySerial($serial);



    DeviceLog::logDeviceEvent($serial, [

        'user_id'        => (int) ($session['user_id'] ?? 0) ?: null,

        'device_udid'    => $device['udid'] ?? '',

        'device_remark'  => $device['remark'] ?? '',

        'serial_number'  => $serial,

        'operation_type' => '删除设备',

        'command_type'   => '',

        'status'         => '完成',

        'detail'         => 'udid=' . ($device['udid'] ?? ''),

        'ip'             => clientIp(),

    ]);



    jsonResponse(0, '设备已删除', $result);

} catch (InvalidArgumentException $e) {

    jsonResponse(1, $e->getMessage());

} catch (Exception $e) {

    jsonResponse(1, '删除失败：' . $e->getMessage());

}

