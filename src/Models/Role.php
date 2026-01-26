<?php

namespace Upsoftware\Svarium\Models;

use Spatie\Permission\Models\Role as BaseRole;

class Role extends BaseRole
{
    protected $connection = 'central';
}
