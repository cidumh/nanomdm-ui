<?php

$logPageTitle = 'API通讯日志';

$logPageDesc = '记录 MDM 服务器通讯内容，包括面板发送指令';

$logPageCompact = true;

$logSearchScopes = [

    ['value' => 'all', 'label' => '全部'],

    ['value' => 'udid', 'label' => 'UDID'],

    ['value' => 'topic_type', 'label' => '指令类型'],

    ['value' => 'topic_id', 'label' => '主题ID'],

    ['value' => 'comm_id', 'label' => '通讯ID'],

    ['value' => 'push_id', 'label' => '推送ID'],

    ['value' => 'content', 'label' => '内容'],

    ['value' => 'ip', 'label' => 'IP'],

];

$logPageConfig = [

    'apiUrl'  => 'api/logs/api/list.php',

    'columns' => [

        ['key' => 'id', 'label' => 'ID', 'class' => 'col-id'],

        ['key' => 'device_udid', 'label' => 'UDID', 'class' => 'col-mono'],

        ['key' => 'topic_type', 'label' => '指令类型', 'class' => 'col-type'],

        ['key' => 'topic_id', 'label' => '主题ID', 'class' => 'col-mono'],

        ['key' => 'comm_id', 'label' => '通讯ID', 'class' => 'col-mono col-copy', 'type' => 'copy'],

        ['key' => 'push_id', 'label' => '推送ID', 'class' => 'col-mono col-copy', 'type' => 'copy'],

        ['key' => 'content', 'label' => '内容', 'class' => 'col-content col-copy', 'type' => 'copy'],

        ['key' => 'ip', 'label' => 'IP', 'class' => 'col-ip'],

        ['key' => 'transfer', 'label' => '传输', 'class' => 'col-transfer'],

        ['key' => 'created_at', 'label' => '时间', 'class' => 'col-time'],

    ],

    'rowHighlight' => [
        'field' => 'transfer',
        'rules' => [
            ['match' => '发送', 'class' => 'log-row-send', 'exact' => true],
            ['match' => '接收', 'class' => 'log-row-recv', 'exact' => true],
        ],
    ],

];

include __DIR__ . '/logs_page_shell.php';

