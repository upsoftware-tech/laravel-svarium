<?php

namespace Upsoftware\Svarium\Models;

use Illuminate\Database\Eloquent\Model;

class UserAuthCode extends Model
{
    public $guarded = [];
    protected $connection = 'central';
}
