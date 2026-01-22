<?php

namespace Upsoftware\Svarium\Models;

use App\Models\User as UserBase;
use Upsoftware\Svarium\Traits\HasSetting;

class User extends UserBase {
    use HasSetting;

    public function routeNotificationForSms()
    {
        return $this->phone_number;
    }
}
