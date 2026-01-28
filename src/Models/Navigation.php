<?php

namespace Upsoftware\Svarium\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Upsoftware\Svarium\Traits\HasHash;
use Upsoftware\Svarium\Traits\HasSetting;
use Upsoftware\Svarium\Traits\UsesConnection;

class Navigation extends Model
{
    use UsesConnection, HasTranslations, HasSetting, HasHash;

    protected $fillable = ['label', 'icon', 'route_name', 'parent_id', 'order', 'permission', 'status', 'position'];

    public array $translatable = ['label'];

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('order');
    }

    public static function getTree()
    {
        return self::whereNull('parent_id')
            ->with('children')
            ->where('is_active', true)
            ->orderBy('order')
            ->get();
    }
}
