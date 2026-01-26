<?php

namespace Upsoftware\Svarium\Models;

use Illuminate\Database\Eloquent\Model;

class ModelHasRole extends Model
{
    protected $connection = 'central';

    public function role()
    {
        return $this->hasOne(Role::class, 'id', 'role_id');
    }

    public function tenant()
    {
        return $this->hasOne(Tenant::class, 'id', 'tenant_id');
    }
}
