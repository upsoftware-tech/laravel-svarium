<?php

namespace Upsoftware\Svarium\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Upsoftware\Svarium\Models\UserAuth;

class VerificationController extends Controller
{
    public function init($type, UserAuth $userAuth) {
        $data = [];
        $data['session'] = $userAuth->hash;
        $data['type'] = $type;

        return inertia('Auth/Verification', $data);
    }

    public function set(Request $request, $type, UserAuth $userAuth)
    {
        if (!$userAuth->verifyCode($request->code)) {
            throw ValidationException::withMessages([
                'code' => [__('svarium::messages.Invalid verification code')],
            ]);
        }

        if ($type === 'login') {
            Auth::login($userAuth->user);
            return redirect()->intended('/');
        } else if ($type === 'reset') {
            return redirect()->route('reset.password', ['userAuth' => $userAuth->hash]);
        }
    }
}
