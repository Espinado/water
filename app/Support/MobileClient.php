<?php

namespace App\Support;

use Illuminate\Http\Request;

class MobileClient
{
    public static function isMobileRequest(?Request $request = null): bool
    {
        if (! config('water.meter_ocr_requires_mobile', true)) {
            return true;
        }

        $request ??= request();

        if (! $request instanceof Request) {
            return false;
        }

        $userAgent = strtolower($request->userAgent() ?? '');

        if ($userAgent === '') {
            return false;
        }

        return (bool) preg_match(
            '/android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini|mobile/i',
            $userAgent,
        );
    }
}
