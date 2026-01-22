<?php

namespace Upsoftware\Svarium\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model {
    protected $fillable = [
        'model_type',
        'model_id',
        'values'
    ];

    protected $casts = [
        'values' => 'array',
    ];

    /**
     * Pobierz ustawienie dla danego modelu.
     *
     * @param string $modelType
     * @param int $modelId
     * @param string $key
     * @return mixed|null
     */
    public static function getSetting($modelType, $modelId, $key, $connection = null)
    {
        $query = $connection ? self::on($connection) : self::query();

        $setting = $query->where('model_type', $modelType)
            ->where('model_id', $modelId)
            ->first();

        return $setting ? ($setting->values[$key] ?? null) : null;
    }

    /**
     * Ustaw wartość ustawienia dla danego modelu.
     *
     * @param string $modelType
     * @param int $modelId
     * @param string $key
     * @param mixed $value
     * @return string|bool
     */
    public static function setSetting($modelType, $modelId, $settingKey, $value = null, $connection = null)
    {
        if (is_array($settingKey)) {
            foreach ($settingKey as $key => $value) {
                self::setSetting($modelType, $modelId, $key, $value, $connection);
            }
            return null;
        }

        $query = $connection ? self::on($connection) : new self;

        $setting = $query->firstOrCreate(
            ['model_type' => $modelType, 'model_id' => $modelId],
            ['values' => []]
        );

        $setting->values = array_merge($setting->values, [$settingKey => $value]);
        $setting->save();

        return $setting;
    }

    /**
     * Usuń ustawienie dla danego modelu.
     *
     * @param string $modelType
     * @param int $modelId
     * @param string $key
     * @return bool
     */
    public static function removeSetting($modelType, $modelId, $settingKey, $connection = null)
    {
        if (is_array($settingKey)) {
            foreach ($settingKey as $key) {
                self::removeSetting($modelType, $modelId, $key);
            }
            return true;
        }

        $setting = self::where('model_type', $modelType)
            ->where('model_id', $modelId)
            ->first();

        if (!$setting || !isset($setting->values[$settingKey])) {
            return false;
        }

        $values = $setting->values;
        unset($values[$settingKey]);

        $setting->values = $values;
        $setting->save();

        return true;
    }
}
