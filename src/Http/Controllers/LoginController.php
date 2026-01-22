<?php

namespace Upsoftware\Svarium\Http\Controllers;

use App\Http\Controllers\Controller;

class LoginController extends Controller
{
    public function init() {
        return inertia('Auth/Login');
    }
}
