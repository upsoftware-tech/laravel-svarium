<?php

namespace Upsoftware\Svarium\Models;

use Illuminate\Database\Eloquent\Model;
use Upsoftware\Core\Casts\StringOrArray;

class SettingGlobal extends Model {

    protected $fillable = [
        'key',
        'value'
    ];

    protected $casts = [
        'value' => StringOrArray::class,
    ];

    public static function get(string $key, $default = null) {
        return static::where('key', $key)->value('value') ?? $default;
    }

    public static function set(string $key, $value): void {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    public static function remove(string $key): void {
        static::where('key', $key)->delete();
    }
}
