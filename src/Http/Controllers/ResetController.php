<?php

namespace Upsoftware\Svarium\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Upsoftware\Svarium\Models\ModelHasRole;
use Upsoftware\Svarium\Models\UserAuth;

class ResetController extends Controller
{
    public function init(UserAuth $userAuth) {
        set_title('Reset');
        return inertia('Auth/Reset');
    }

    public function reset(Request $request) {
        $request->validate([
            'email' => ['required', 'string', 'email:rfc,dns'],
        ]);

        $tenant_id = false;
        $user = User::where('email', $request->email)->first();
        $has_role = false;

        if ($user) {
            if (tenant() && tenant()->id) {
                $tenant_id = tenant()->id;
            }
            $queryRole = ModelHasRole::where('model_id', $user->id)->where('model_type', 'App\Models\User')->where('status', 1);
            if (config('tenancy.enabled', false)) {
                $queryRole->where('tenant_id', $tenant_id);
            }
            $has_role = $queryRole->count() > 0;
        }

        $session = sha1(md5(time()));
        if ($user && $has_role) {
            $authSession = UserAuth::create([
                'type' => 'reset',
                'user_id' => $user->id,
            ]);
            $authSession->sendEmail('reset');
            $session = $authSession->hash;
        }

        return redirect()->route('auth.verification', ['type' => 'reset', 'userAuth' => $session]);
    }
}
