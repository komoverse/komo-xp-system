<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

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

    public static function generate_local_hash($local_string, $account_id){
        $cipher_algorithm = 'AES-256-CBC';
        $passphrase = Helper::retrieve_user_salt($account_id);
        $options = 0;
        $iv = env('XP_SECURITY_KEY', null);

        $local_hash = openssl_encrypt($local_string, $cipher_algorithm, $passphrase, $options, $iv);
        return $local_hash;
    }

    public static function retrieve_user_salt($account_id){
        $account = DB::table('tb_account')
            ->where('id', $account_id)
            ->where('is_verified', 1)
            ->where('is_suspended', 0)
            ->first();

        if (!isset($account)) return null;
        return $account->salt;
    }
}
