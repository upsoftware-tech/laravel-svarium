<?php

namespace Upsoftware\Svarium\Http\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;
use Inertia\Inertia;
use Upsoftware\Svarium\Models\Setting;
use Upsoftware\Svarium\Services\LayoutService;

class HandleInertiaRequests extends Middleware
{
    public function share(Request $request): array
    {
        $setting = [];

        return array_merge(parent::share($request), [
            'locale' => session()->has('locale') ? session()->get('locale') : app()->getLocale(),
            'locales' => Inertia::once(fn () => locales()),
            'workspaces' => Auth::check() && method_exists(Auth::user(), 'getWorkspaces') ? $request->user()->getWorkspaces() : false,
            'title' => fn () => get_title(),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
                'message' => fn () => $request->session()->get('message'),
            ],
            'setting' => Setting::getSettingGlobal('layout'),
        ]);
    }
}
