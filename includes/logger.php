<?php

/**

 * 日志记录 - 系统/设备/API 三类

 */



require_once __DIR__ . '/db.php';

require_once __DIR__ . '/device_log.php';

require_once __DIR__ . '/system_log.php';

require_once __DIR__ . '/api_log.php';



class Logger

{

    /**

     * 系统日志 - 面板操作

     */

    public static function system(string $operationType, string $operationResult, string $detail = '', ?int $userId = null, ?string $username = null): void

    {

        try {

            SystemLog::insert([

                'user_id'          => $userId,

                'username'         => $username,

                'operation_type'   => $operationType,

                'operation_result' => $operationResult,

                'detail'           => $detail,

                'ip'               => clientIp(),

            ]);

        } catch (Exception $e) {

        }

    }



    /**

     * 设备日志 - 简单文本记录（兼容旧调用）

     */

    public static function device(string $action, string $detail = '', ?int $userId = null): void

    {

        try {

            DeviceLog::ensureTables();

            DeviceLog::insert([

                'user_id'        => $userId,

                'operation_type' => $action,

                'action'         => $action,

                'detail'         => $detail,

                'ip'             => clientIp(),

            ]);

        } catch (Exception $e) {

        }

    }



    /**

     * 设备日志 - 结构化记录

     */

    public static function deviceOperation(array $data, ?int $userId = null): void

    {

        try {

            $data['user_id'] = $userId;

            if (empty($data['ip'])) {

                $data['ip'] = clientIp();

            }

            DeviceLog::insert($data);

        } catch (Exception $e) {

        }

    }



    /**

     * API 日志 - MDM 服务端通讯记录

     */

    public static function apiMessage(array $data): void

    {

        try {

            ApiLog::insert($data);

        } catch (Exception $e) {

        }

    }

}


