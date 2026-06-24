<?php
$logPageTitle = '系统日志';
$logPageDesc = '记录面板操作，包括登录、保存配置、添加证书、开启激活锁等';
$logSearchScopes = [
    ['value' => 'all', 'label' => '全部'],
    ['value' => 'username', 'label' => '用户名'],
    ['value' => 'operation_type', 'label' => '操作类型'],
    ['value' => 'operation_result', 'label' => '操作结果'],
    ['value' => 'ip', 'label' => 'IP'],
];
$logPageConfig = [
    'apiUrl'  => 'api/logs/system/list.php',
    'columns' => [
        ['key' => 'id', 'label' => '日志ID', 'class' => 'col-id'],
        ['key' => 'username', 'label' => '操作用户名', 'class' => 'col-user'],
        ['key' => 'operation_type', 'label' => '操作类型', 'class' => 'col-type'],
        ['key' => 'operation_result', 'label' => '操作结果', 'class' => 'col-result'],
        ['key' => 'detail', 'label' => '详细内容', 'class' => 'col-content col-copy', 'type' => 'copy'],
        ['key' => 'ip', 'label' => '用户IP', 'class' => 'col-ip'],
        ['key' => 'created_at', 'label' => '记录时间', 'class' => 'col-time'],
    ],
    'rowHighlight' => [
        'field' => 'operation_result',
        'rules' => [
            ['match' => '失败', 'class' => 'log-row-fail'],
            ['match' => '成功', 'class' => 'log-row-success'],
        ],
    ],
];
include __DIR__ . '/logs_page_shell.php';
