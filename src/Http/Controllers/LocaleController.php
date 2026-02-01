<?php

namespace Upsoftware\Svarium\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\App;

class LocaleController extends Controller
{
    public function __invoke(string $locale) {
        session()->put('locale', $locale);
        app()->setLocale($locale);
        return back();
    }
}
