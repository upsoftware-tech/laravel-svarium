<?php

namespace Upsoftware\Svarium\Http\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;
use Inertia\Inertia;

class HandleInertiaRequests extends Middleware
{
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'locale' => session()->has('locale') ? session()->get('locale') : app()->getLocale(),
            'locales' => Inertia::once(fn () => locales()),
            'workspaces' => Auth::check() ? [] : [],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
                'message' => fn () => $request->session()->get('message'),
            ],
        ]);
    }
}
