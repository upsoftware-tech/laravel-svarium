<?php

use Jenssegers\Agent\Agent;
use Upsoftware\Svarium\Models\Setting;

if (!function_exists('layout')) {
    function layout() {
        return app('layout');
    }
}

function locales() {
    $locales = Setting::getSettingGlobal('locales', []);
    return array_values(array_map(function ($value) {
        $array = [];
        $array["value"] = $value["value"] ?? $value["code"] ?? $value["id"] ?? '';

        if (!isset($value["icon"])) {
            $array["icon"] = ["type" => "icon", "value" => "cif:".$value['flag'] ?? $value['code']];
        } else {
            $array["icon"] = $value["icon"];
        }

        $array["label"] = $value["native"] ?? $value['localized'] ?? '';

        return $array;
    }, $locales));
}

function set_title($title) {
    layout()->set_title($title);
}

function get_title() {
    return layout()->get_title();
}

function central_connection() {
    if ($forcedConnection = config('svarium.database_connection')) {
        return $forcedConnection;
    }

    if (config()->has('tenancy.database.central_connection')) {
        return config('tenancy.database.central_connection');
    }

    if (config()->has('database.connections.central')) {
        return 'central';
    }

    return config('database.default');
}

function device(): array {
    $agent = new Agent();
    $array = [];
    $array['ip'] = request()->ip();
    $array['deviceType'] = $agent->device();
    $array['platform'] = $agent->platform();
    $array['platformVer'] = $agent->version($array['platform']);
    $array['browser'] = $agent->browser();
    $array['browserVer'] = $agent->version($array['browser']);

    return $array;
}

function svarium_path($path = ''): string
{
    return app_path(implode('/', ['Svarium', $path]));
}

function svarium_resources($path = ''): string
{
    return svarium_path(implode('/', ['Resources', $path]));
}

function pluck(string $modelClass, string $value, ?string $key = null): array
{
    if (!class_exists($modelClass)) {
        return [];
    }
    return $modelClass::pluck($value, $key)->toArray();
}
