<?php

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
