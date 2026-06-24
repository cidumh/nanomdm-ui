<?php
/**
 * 简约线条 SVG 图标
 */
function uiIcon(string $name): string
{
    $paths = [
        'dashboard' => '<path d="M4 10.5L12 4l8 6.5V19a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1v-8.5z"/>',
        'policy' => '<path d="M12 3L4 7v6c0 5 3.5 8.5 8 9 4.5-.5 8-4 8-9V7l-8-4z"/><path d="M9 12l2 2 4-4"/>',
        'dep' => '<rect x="4" y="8" width="16" height="12" rx="1"/><path d="M8 8V6a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M4 13h16"/>',
        'dep_manage' => '<path d="M4 7h16v12H4z"/><path d="M8 11h8M8 15h5"/><path d="M9 7V5h6v2"/>',
        'apns' => '<path d="M12 3a5 5 0 0 1 5 5v1.5a3 3 0 0 1 0 6V17a5 5 0 0 1-10 0v-1.5a3 3 0 0 1 0-6V8a5 5 0 0 1 5-5z"/><path d="M10 17h4"/>',
        'mdm' => '<circle cx="12" cy="12" r="3"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
        'profile' => '<path d="M6 4h12a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1z"/><path d="M8 9h8M8 13h8M8 17h5"/>',
        'devices' => '<rect x="7" y="3" width="10" height="18" rx="2"/><path d="M11 18h2"/>',
        'device_logs' => '<path d="M6 4h12v16H6z"/><path d="M9 8h6M9 12h6M9 16h4"/><path d="M8 4V3h8v1"/>',
        'system_logs' => '<path d="M4 19V5"/><path d="M4 19h16"/><path d="M8 15l3-3 3 2 4-5"/>',
        'api_logs' => '<circle cx="6" cy="12" r="2"/><circle cx="18" cy="6" r="2"/><circle cx="18" cy="18" r="2"/><path d="M8 11l8-4M8 13l8 4"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/>',
    ];

    if (!isset($paths[$name])) {
        return '';
    }

    return '<svg class="ui-line-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . $paths[$name]
        . '</svg>';
}

function uiFeatureIcon(string $name): string
{
    $svg = uiIcon($name);
    if ($svg === '') {
        return '';
    }
    return '<div class="feature-icon">' . $svg . '</div>';
}

function uiNavIcon(string $name): string
{
    $svg = uiIcon($name);
    if ($svg === '') {
        return '';
    }
    return '<span class="nav-icon">' . $svg . '</span>';
}
