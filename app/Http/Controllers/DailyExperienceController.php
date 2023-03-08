<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\DB;
use App\Models\DailyExperience;
use App\Models\DailyExperienceEvent;
use App\Helpers\Helper;
use Carbon\Carbon;

class DailyExperienceController extends Controller
{
    public function __construct(Request $request){
        $this->is_https = Helper::is_https();
        $this->is_local = Helper::is_local();
        $this->json = array('status' => 'fail', 'message' => null);

        // Verify connection is from HTTPS, but automatically passes if APP_ENV is local.
        $this->connection_safe = $this->verify_connection($request);

        // Verify that given account id exists in the main database.
        $this->user_exists = $this->verify_user($request);

        // Throttle attempts to this API by 30 attempts/minute.
        $this->rate_limited = $this->verify_rate_limit($request);
    }

    public function api_daily_experience(Request $request){
        // Guard statements.
        if (!$this->connection_safe) return response()->json($this->json, 403); // Forbidden
        if (!$this->user_exists) return response()->json($this->json, 400); // Bad Request
        if ($this->rate_limited) return response()->json($this->json, 429); // Too Many Requests

        // List of APIs.
        if ($request['add-daily-experience'] == 'true' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->add_daily_experience($request);
        }

        if ($request['get-daily-experience'] == 'true' && $_SERVER['REQUEST_METHOD'] === 'GET') {
            $jsonify_data = true;
            return $this->get_daily_experience($request, $jsonify_data);
        }
    }

    /* ----- API FUNCTIONS ----- */

    private function add_daily_experience(Request $request){
        // Validate entry.
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|max:2147483647',
            'api-key' => 'required|exists:tb_api_key,api_key|max:255',
            'security-hash' => 'required',
        ]);

        if ($validator->fails()) {
            $this->json['message'] = $validator->errors();
            return response()->json($this->json, 400); // Bad Request
        }

        // Verify security hash.
        $local_string = $request['account-id'] . $request['amount'] . $request['api-key'];
        $local_hash = $this->generate_local_hash($local_string, $request['account-id']);

        if ($local_hash != $request['security-hash']) {
            $this->json['message'] = 'Hash does not match.';
            return response()->json($this->json, 403); // Forbidden
        }

        // Start tallying up the experience gained.
        $daily_experience = $this->get_daily_experience($request);
        $daily_experience->total_experience = max($daily_experience->total_experience + $request['amount'], 0);

        // Create an event for audit purposes before saving.
        $daily_experience_event = $this->create_daily_experience_event($request, $daily_experience);
        $daily_experience->save();

        // Return API status.
        $this->json['status'] = 'success';
        $this->json['message'] = 'Daily Experience successfully added to account! Audit record has been created.';
        $this->json['data'] = $daily_experience;
        return response()->json($this->json, 200); // OK
    }

    private function get_daily_experience(Request $request,$jsonify_data = false) {
        $daily_experience = DailyExperience::where('account_id', $request['account-id'])
            ->whereDate('created_at', Carbon::today())
            ->orderBy('id', 'ASC')
            ->first();

        if ($daily_experience == null) {
            $daily_experience = $this->initialize_daily_experience($request);
        }

        if ($jsonify_data) return response()->json($daily_experience, 200); // OK
        return $daily_experience;
    }

    /* ----- HELPER FUNCTIONS ----- */

    private function create_daily_experience_event(Request $request, DailyExperience $daily_experience) {
        $delta = $daily_experience->total_experience - $daily_experience->getOriginal('total_experience');

        return DailyExperienceEvent::create([
            'daily_experience_id' => $daily_experience->id,
            'api_key' => $request['api-key'],
            'delta' => $delta,
        ]);
    }

    private function initialize_daily_experience(Request $request) {
        $daily_experience = DailyExperience::create([
            'account_id' => $request['account-id'],
            'total_experience' => 0,
        ]);

        return $daily_experience;
    }

    private function verify_connection(Request $request){
        if ($this->is_https == false && !$this->is_local == true) {
            $this->json['message'] = 'Connection fired from an unsecured connection (use HTTPS).';
            return false;
        }

        return true;
    }

    private function verify_user(Request $request){
        $validator = Validator::make($request->all(), [
            'account-id' => 'required|exists:tb_account,id|max:255',
        ]);
        if ($validator->fails()) {
            $this->json['message'] = $validator->errors();
            return false;
        }

        return true;
    }

    private function verify_rate_limit(Request $request, $attempts_per_minute = 30){
        $rate_limited = !RateLimiter::attempt(
            'User: ' . $request['account-id'],
            $attempts_per_minute,
            function() {}
        );

        if ($rate_limited) {
            $this->json['message'] = 'You are being rate limited.';
            return true;
        }

        return false;
    }

    private function generate_local_hash($local_string, $account_id){
        $cipher_algorithm = 'AES-256-CBC';
        $passphrase = $this->retrieve_user_salt($account_id);
        $options = 0;
        $iv = env('XP_SECURITY_KEY', null);

        $local_hash = openssl_encrypt($local_string, $cipher_algorithm, $passphrase, $options, $iv);
        return $local_hash;
    }

    private function retrieve_user_salt($account_id){
        $account = DB::table('tb_account')
            ->where('id', $account_id)
            ->where('is_verified', 1)
            ->where('is_suspended', 0)
            ->first();

        if (!isset($account)) return null;
        return $account->salt;
    }
}
