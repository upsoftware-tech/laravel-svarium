<?php

use \Upsoftware\Svarium\Services\DeviceTracking\DeviceHijackingDetectorDefault;

return [
    'panel' => [
        'enabled' => true,
        'route_prefix' => 'panel.auth',
        'prefix' => '',
    ],
    'tracking' => [
        'user_model' => null,
        'detect_on_login' => true,
        'geoip_provider' => null,
        'device_cookie' => 'device_uuid',
        'cookie_http_only' => true,
        'session_key' => 'device-tracking',
        'hijacking_detector' => DeviceHijackingDetectorDefault::class,
    ]
];
