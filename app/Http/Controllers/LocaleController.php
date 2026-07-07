<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function switch(Request $request, string $locale): RedirectResponse
    {
        $supported = array_keys(config('locales.supported', []));

        if (in_array($locale, $supported, true)) {
            $request->session()->put('locale', $locale);

            $user = $request->user();
            if ($user instanceof User) {
                $user->forceFill(['locale' => $locale])->save();
            }
        }

        return back();
    }
}
