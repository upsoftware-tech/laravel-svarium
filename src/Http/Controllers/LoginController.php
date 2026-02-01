<?php

namespace Upsoftware\Svarium\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Upsoftware\Svarium\Models\ModelHasRole;
use App\Models\User;
use Upsoftware\Svarium\Models\Setting;
use Upsoftware\Svarium\Models\UserAuth;

class LoginController extends Controller
{
    public function init(Request $request) {
        if (Auth::check()) {
            return redirect('/');
        }

        $data = Setting::getSettingGlobal('login.config', []);

        return inertia('Auth/Login', $data);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'min:8'],
        ]);

        $user = User::where('email', $request->email)->first();
        $has_role = false;
        $tenant_id = null;
        if ($user) {
            if (tenant() && tenant()->id) {
                $tenant_id = tenant()->id;
            }
            $has_role = ModelHasRole::where('model_id', $user->id)->where('tenant_id', $tenant_id)->where('model_type', 'App\Models\User')->where('status', 1)->count() > 0;
        }
        if (! $user || ! $has_role || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('svarium::validation.Invalid email address or password')],
            ]);
        }

        if ($user->getSetting('otp_status', true, 'central') === true) {
            $userAuth = UserAuth::setToken($user, 'login');
            return redirect()->route('auth.method', ['type' => 'login', 'userAuth' => $userAuth->hash]);
        } else {
            Auth::login($user);
            return redirect()->intended('/');
        }
    }
}
