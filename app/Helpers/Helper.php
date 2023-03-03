<?php

namespace App\Helpers;

class Helper
{
    public static function is_https() {
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') return true;

        return isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on');
    }

    public static function is_local() {
        if (env('APP_ENV') == 'local') return true;

        return false;
    }
}
