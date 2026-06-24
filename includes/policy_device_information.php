<?php
/**
 * 策略配置：更新设备信息（DeviceInformation）指令
 */

class PolicyDeviceInformation
{
    private const QUERIES = [
        'UDID',
        'Languages',
        'Locales',
        'DeviceID',
        'OrganizationInfo',
        'LastCloudBackupDate',
        'AwaitingConfiguration',
        'MDMOptions',
        'iTunesStoreAccountIsActive',
        'iTunesStoreAccountHash',
        'DeviceName',
        'OSVersion',
        'BuildVersion',
        'ModelName',
        'Model',
        'ProductName',
        'SerialNumber',
        'DeviceCapacity',
        'AvailableDeviceCapacity',
        'BatteryLevel',
        'CellularTechnology',
        'ICCID',
        'BluetoothMAC',
        'WiFiMAC',
        'EthernetMACs',
        'CurrentCarrierNetwork',
        'SubscriberCarrierNetwork',
        'CurrentMCC',
        'CurrentMNC',
        'SubscriberMCC',
        'SubscriberMNC',
        'SIMMCC',
        'SIMMNC',
        'SIMCarrierNetwork',
        'CarrierSettingsVersion',
        'PhoneNumber',
        'DataRoamingEnabled',
        'VoiceRoamingEnabled',
        'PersonalHotspotEnabled',
        'IsRoaming',
        'IMEI',
        'MEID',
        'ModemFirmwareVersion',
        'IsSupervised',
        'IsDeviceLocatorServiceEnabled',
        'IsActivationLockEnabled',
        'IsDoNotDisturbInEffect',
        'EASDeviceIdentifier',
        'IsCloudBackupEnabled',
        'OSUpdateSettings',
        'LocalHostName',
        'HostName',
        'CatalogURL',
        'IsDefaultCatalog',
        'PreviousScanDate',
        'PreviousScanResult',
        'PerformPeriodicCheck',
        'AutomaticCheckEnabled',
        'BackgroundDownloadEnabled',
        'AutomaticAppInstallationEnabled',
        'AutomaticOSInstallationEnabled',
        'AutomaticSecurityUpdatesEnabled',
        'OSUpdateSettings',
        'LocalHostName',
        'HostName',
        'IsMultiUser',
        'IsMDMLostModeEnabled',
        'MaximumResidentUsers',
        'PushToken',
        'DiagnosticSubmissionEnabled',
        'AppAnalyticsEnabled',
        'IsNetworkTethered',
        'ServiceSubscriptions',
    ];

    public static function buildCommand(): string
    {
        $lines = [];
        $lines[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $lines[] = '<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">';
        $lines[] = '<plist version="1.0">';
        $lines[] = '<dict>';
        $lines[] = '    <key>Command</key>';
        $lines[] = '    <dict>';
        $lines[] = '        <key>Queries</key>';
        $lines[] = '        <array>';
        foreach (self::QUERIES as $query) {
            $lines[] = '            <string>' . htmlspecialchars($query, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</string>';
        }
        $lines[] = '        </array>';
        $lines[] = '        <key>RequestType</key>';
        $lines[] = '        <string>DeviceInformation</string>';
        $lines[] = '    </dict>';
        $lines[] = '    <key>CommandUUID</key>';
        $lines[] = '    <string>_CommandUUID_</string>';
        $lines[] = '</dict>';
        $lines[] = '</plist>';

        return implode("\n", $lines) . "\n";
    }
}
