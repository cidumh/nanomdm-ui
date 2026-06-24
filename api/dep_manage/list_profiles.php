<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/dep.php';
require_once __DIR__ . '/../../includes/dep_profile.php';

if (!isInstalled()) {
    jsonResponse(503, '系统尚未安装');
}

Auth::requireLogin();
DepProfile::ensureTables();

$rows = DepProfile::listAll();
$list = [];

foreach ($rows as $row) {
    $list[] = [
        'id'            => (int)$row['id'],
        'profile_uuid'  => $row['profile_uuid'],
        'profile_name'  => $row['profile_name'],
        'mdm_url'       => $row['mdm_url'],
        'web_url'       => $row['web_url'],
        'department'    => $row['department'],
        'language'      => $row['language'],
        'region'        => $row['region'],
        'created_at'    => $row['created_at'],
        'updated_at'    => $row['updated_at'],
    ];
}

jsonResponse(0, 'ok', $list);
