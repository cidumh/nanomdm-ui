<?php

$logPageTitle = '设备日志';

$logPageDesc = '记录设备管理相关操作';

$logPageCompact = true;

$logSearchScopes = [

    ['value' => 'all', 'label' => '全部'],

    ['value' => 'udid', 'label' => 'UDID'],

    ['value' => 'serial_number', 'label' => '序列号'],

    ['value' => 'operation_type', 'label' => '操作类型'],

    ['value' => 'comm_id', 'label' => '通讯ID'],

    ['value' => 'push_id', 'label' => '推送ID'],

    ['value' => 'command_type', 'label' => '指令类型'],

    ['value' => 'status', 'label' => '状态'],

];

$logPageConfig = [

    'apiUrl'  => 'api/logs/device/list.php',

    'columns' => [

        ['key' => 'device_udid', 'label' => 'UDID', 'class' => 'col-mono'],

        ['key' => 'device_remark', 'label' => '设备备注', 'class' => 'col-remark'],

        ['key' => 'serial_number', 'label' => '序列号', 'class' => 'col-mono'],

        ['key' => 'operation_type', 'label' => '操作类型', 'class' => 'col-type'],

        ['key' => 'comm_id', 'label' => '通讯ID', 'class' => 'col-mono col-copy', 'type' => 'copy'],

        ['key' => 'push_id', 'label' => '推送ID', 'class' => 'col-mono col-copy', 'type' => 'copy'],

        ['key' => 'command_type', 'label' => '指令类型', 'class' => 'col-type'],

        ['key' => 'status', 'label' => '状态', 'class' => 'col-status'],

        ['key' => 'created_at', 'label' => '操作时间', 'class' => 'col-time'],

        ['key' => 'confirmed_at', 'label' => '确认时间', 'class' => 'col-time'],

    ],

    'rowHighlight' => [
        'field' => 'status',
        'rules' => [
            ['match' => '失败', 'class' => 'log-row-fail'],
            ['match' => '完成', 'class' => 'log-row-success'],
            ['match' => '等待', 'class' => 'log-row-wait'],
        ],
    ],

];

include __DIR__ . '/logs_page_shell.php';

