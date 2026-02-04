<?php

namespace Upsoftware\Svarium\Models;

use App\Models\User as UserBase;
use IvanoMatteo\LaravelDeviceTracking\Traits\UseDevices;
use Upsoftware\Svarium\Traits\HasSetting;
use Upsoftware\Svarium\Traits\UsesConnection;

class User extends UserBase {
    use HasSetting, UsesConnection, UseDevices;


    public function routeNotificationForSms()
    {
        return $this->phone_number;
    }
}
