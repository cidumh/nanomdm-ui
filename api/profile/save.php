<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/profile.php';
require_once __DIR__ . '/../../includes/logger.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, '请求方式不允许');
}

if (!isInstalled()) {
    jsonResponse(503, '系统尚未安装');
}

$session = Auth::requireLogin();
ProfileConfig::ensureTables();

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$profileName           = trim($input['profile_name'] ?? '');
$profileDescription    = trim($input['profile_description'] ?? '');
$orgName               = trim($input['org_name'] ?? '');
$profileIdentifier     = trim($input['profile_identifier'] ?? '');
$mdmServerUrl          = trim($input['mdm_server_url'] ?? '');
$mdmCheckinUrl         = trim($input['mdm_checkin_url'] ?? '');
$apnsTopicId           = trim($input['apns_topic_id'] ?? '');
$mdmPayloadIdentifier  = trim($input['mdm_payload_identifier'] ?? '');
$userAgreementEnabled  = !empty($input['user_agreement_enabled']);
$userAgreementContent  = trim($input['user_agreement_content'] ?? '');
$scepEnabled           = !empty($input['scep_enabled']);
$scepUrl               = trim($input['scep_url'] ?? '');
$scepChallenge         = trim($input['scep_challenge'] ?? '');
$scepIdentifier        = trim($input['scep_identifier'] ?? '');

if ($mdmServerUrl === '') {
    jsonResponse(1, '请填写 MDM ServerURL');
}
if (!profileIsUrl($mdmServerUrl)) {
    jsonResponse(1, 'MDM ServerURL 格式不正确');
}
if ($mdmCheckinUrl !== '' && !profileIsUrl($mdmCheckinUrl)) {
    jsonResponse(1, 'MDM CheckInURL 格式不正确');
}
if ($userAgreementEnabled && $userAgreementContent === '') {
    jsonResponse(1, '开启用户协议时请填写协议内容');
}
if ($scepEnabled) {
    if ($scepUrl === '') {
        jsonResponse(1, '开启 SCEP 时请填写 SCEP URL 地址');
    }
    if (!profileIsUrl($scepUrl)) {
        jsonResponse(1, 'SCEP URL 地址格式不正确');
    }
}

try {
    ProfileConfig::set('profile_name', $profileName);
    ProfileConfig::set('profile_description', $profileDescription);
    ProfileConfig::set('org_name', $orgName);
    ProfileConfig::set('profile_identifier', $profileIdentifier);
    ProfileConfig::set('mdm_server_url', $mdmServerUrl);
    ProfileConfig::set('mdm_checkin_url', $mdmCheckinUrl);
    ProfileConfig::set('apns_topic_id', $apnsTopicId);
    ProfileConfig::set('mdm_payload_identifier', $mdmPayloadIdentifier);
    ProfileConfig::set('user_agreement_enabled', $userAgreementEnabled ? '1' : '0');
    ProfileConfig::set('user_agreement_content', $userAgreementContent);
    ProfileConfig::set('scep_enabled', $scepEnabled ? '1' : '0');
    ProfileConfig::set('scep_url', $scepUrl);
    ProfileConfig::set('scep_challenge', $scepChallenge);
    ProfileConfig::set('scep_identifier', $scepIdentifier);

    Logger::system('保存描述文件配置', '保存成功', ProfileConfig::profileName($profileName), (int)$session['user_id'], $session['username'] ?? '');

    jsonResponse(0, '描述文件配置已保存', ProfileConfig::getAll());
} catch (Exception $e) {
    jsonResponse(1, '保存失败：' . $e->getMessage());
}
