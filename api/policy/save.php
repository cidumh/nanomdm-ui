<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/policy.php';
require_once __DIR__ . '/../../includes/dep.php';
require_once __DIR__ . '/../../includes/logger.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, '请求方式不允许');
}

if (!isInstalled()) {
    jsonResponse(503, '系统尚未安装');
}

$session = Auth::requireLogin();
PolicyConfig::ensureTables();

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$activationLock = !empty($input['activation_lock']);
$dnsProxy       = !empty($input['dns_proxy']);
$globalProxy    = !empty($input['global_proxy']);
$funcRestriction = !empty($input['func_restriction']);

if ($activationLock && !DepConfig::isConfigured()) {
    jsonResponse(1, '请先完成 DEP 配置后再开启激活锁');
}

if ($dnsProxy) {
    $org   = PolicyConfig::orgName(trim($input['dns_org_name'] ?? ''));
    $id    = trim($input['dns_identifier'] ?? '');
    $url   = PolicyConfig::dnsServerUrl(trim($input['dns_server_url'] ?? ''));
    $addr1 = trim($input['dns_address_1'] ?? '');
    $addr2 = trim($input['dns_address_2'] ?? '');

    if ($id === '') {
        jsonResponse(1, 'DNS 代理：请填写配置标识');
    }
    if (!policyIsUrl($url)) {
        jsonResponse(1, 'DNS 代理：ServerURL 格式不正确');
    }
    if ($addr1 !== '' && !policyIsIpv4($addr1)) {
        jsonResponse(1, 'DNS 代理：ServerAddresses 第 1 个地址必须是 IPv4');
    }
    if ($addr2 !== '' && !policyIsIpv4($addr2)) {
        jsonResponse(1, 'DNS 代理：ServerAddresses 第 2 个地址必须是 IPv4');
    }
}

if ($globalProxy) {
    $org = PolicyConfig::orgName(trim($input['proxy_org_name'] ?? ''));
    $id  = trim($input['proxy_identifier'] ?? '');
    $pac = trim($input['proxy_pac_url'] ?? '');

    if ($org === '' || $id === '' || $pac === '') {
        jsonResponse(1, '全局代理：请填写机构名称、配置标识和 ProxyPACURL');
    }
    if (!policyIsUrl($pac)) {
        jsonResponse(1, '全局代理：ProxyPACURL 格式不正确');
    }
}

$funcData = $input['func_restrictions'] ?? [];
if (!is_array($funcData)) {
    jsonResponse(1, '功能限制数据格式错误');
}

$defaults = PolicyConfig::funcRestrictionDefaults();
$merged = array_merge($defaults, $funcData);
foreach (PolicyConfig::funcRestrictionKeys() as $item) {
    $merged[$item['key']] = !empty($merged[$item['key']]);
}
$merged['camera_whitelist_enabled'] = !empty($merged['camera_whitelist_enabled']);
$merged['allowedCameraRestrictionBundleIDs'] = trim($merged['allowedCameraRestrictionBundleIDs'] ?? '');

if ($funcRestriction && $merged['camera_whitelist_enabled'] && $merged['allowedCameraRestrictionBundleIDs'] === '') {
    jsonResponse(1, '功能限制：开启相机白名单后请填写应用 ID');
}

if ($funcRestriction) {
    $funcOrg = PolicyConfig::orgName(trim($input['func_org_name'] ?? ''));
    $funcId  = trim($input['func_identifier'] ?? '');
    if ($funcId === '') {
        jsonResponse(1, '功能限制：请填写配置标识');
    }
}

$funcData = $merged;

try {
    PolicyConfig::set('activation_lock', $activationLock ? '1' : '0');
    PolicyConfig::set('dns_proxy', $dnsProxy ? '1' : '0');
    PolicyConfig::set('dns_org_name', PolicyConfig::orgName(trim($input['dns_org_name'] ?? '')));
    PolicyConfig::set('dns_identifier', trim($input['dns_identifier'] ?? ''));
    PolicyConfig::set('dns_server_url', PolicyConfig::dnsServerUrl(trim($input['dns_server_url'] ?? '')));
    PolicyConfig::set('dns_address_1', trim($input['dns_address_1'] ?? ''));
    PolicyConfig::set('dns_address_2', trim($input['dns_address_2'] ?? ''));
    PolicyConfig::set('global_proxy', $globalProxy ? '1' : '0');
    PolicyConfig::set('proxy_org_name', PolicyConfig::orgName(trim($input['proxy_org_name'] ?? '')));
    PolicyConfig::set('proxy_identifier', trim($input['proxy_identifier'] ?? ''));
    PolicyConfig::set('proxy_pac_url', trim($input['proxy_pac_url'] ?? ''));
    PolicyConfig::set('func_restriction', $funcRestriction ? '1' : '0');
    PolicyConfig::set('func_org_name', PolicyConfig::orgName(trim($input['func_org_name'] ?? '')));
    PolicyConfig::set('func_identifier', trim($input['func_identifier'] ?? ''));
    PolicyConfig::setFuncRestrictions($funcData);
    PolicyConfig::removeDeprecatedKeys();

    Logger::system('保存策略配置', '保存成功', '策略配置已更新', (int)$session['user_id'], $session['username'] ?? '');
    jsonResponse(0, '策略配置已保存');
} catch (Exception $e) {
    jsonResponse(1, '保存失败：' . $e->getMessage());
}
