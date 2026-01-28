<?php

namespace Upsoftware\Svarium\Models;

use Spatie\Permission\Models\Role as BaseRole;
use Upsoftware\Svarium\Traits\UsesConnection;

class Role extends BaseRole
{
    use UsesConnection;
}
