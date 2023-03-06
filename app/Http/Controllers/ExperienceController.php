<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\DB;
use App\Models\DailyExperience;
use App\Models\DailyExperienceEvent;
use App\Models\MmrExperience;
use App\Models\MmrExperienceEvent;
use App\Models\Experience;
use App\Models\ExperienceEvent;
use App\Helpers\Helper;
use Carbon\Carbon;

class ExperienceController extends Controller
{
    public function __construct(Request $request){
        $this->is_https = Helper::is_https();
        $this->is_local = Helper::is_local();
        $this->json = array('status' => 'fail', 'message' => null);

        // Verify connection is from HTTPS, but automatically passes if APP_ENV is local.
        $this->connection_safe = $this->verify_connection($request);
        if (!$this->connection_safe) return response()->json($this->json, 403); // Forbidden

        // Verify that komo username exists in the main database.
        $this->user_exists = $this->verify_user($request);
        if (!$this->user_exists) return response()->json($this->json, 400); // Bad Request

        // Throttle attempts to this API by 30 attempts/minute.
        $this->rate_limited = $this->verify_rate_limit($request);
        if ($this->rate_limited) return response()->json($this->json, 429); // Too Many Requests
    }

    public function api_daily_experience_post(Request $request){
        if ($request['add-daily-experience'] == 'true') {
            return $this->add_daily_experience($request);
        }
    }

    public function api_daily_experience_get(Request $request){
        if ($request['get-daily-experience'] == 'true') {
            $jsonify_data = true;
            return $this->get_daily_experience($request, $jsonify_data);
        }
    }

    public function api_compendium_experience_post(Request $request){
        if ($request['add-compendium-experience'] == 'true') {
            return $this->add_compendium_experience($request);
        }
    }

    // public function api_compendium_experience_get(Request $request){
    //     if ($request['get-compendium-experience'] == 'true') {
    //         $jsonify_data = true;
    //         return $this->get_compendium_experience($request, $jsonify_data);
    //     }
    // }

    /* ----- API FUNCTIONS ----- */

    private function add_daily_experience(Request $request){
        // Validate entry.
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|max:2147483647',
            'source' => 'required|exists:tb_api_key,source|max:255',
            'api-key' => 'required|exists:tb_api_key,api_key|max:255',
            'security-hash' => 'required',
        ]);

        if ($validator->fails()) {
            $this->json['message'] = $validator->errors();
            return response()->json($this->json, 400); // Bad Request
        }

        // Verify security hash.
        $local_string = $request['komo-username'] . $request['amount'] . $request['source'] . $request['api-key'];
        $local_hash = $this->generate_local_hash($local_string, $request['komo-username']);

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
        $this->json['message'] = 'Experience successfully added to account! Audit record has been created.';
        return response()->json($this->json, 200); // OK
    }

    // private function add_compendium_experience(Request $request){
    //     // Validate entry.
    //     $validator = Validator::make($request->all(), [ // NOTE: CHANGE THIS!!!
    //         // 'amount' => 'required|numeric|max:2147483647',
    //         // 'source' => 'required|exists:tb_api_key,source|max:255',
    //         // 'api-key' => 'required|exists:tb_api_key,api_key|max:255',
    //         'security-hash' => 'required',
    //     ]);
    //
    //     if ($validator->fails()) {
    //         $this->json['message'] = $validator->errors();
    //         return response()->json($this->json, 400); // Bad Request
    //     }
    //
    //     // Verify security hash.
    //     $local_string = $request['komo-username']; // NOTE: CHANGE THIS!!!
    //     $local_hash = $this->generate_local_hash($local_string, $request['komo-username']);
    //
    //     if ($local_hash != $request['security-hash']) {
    //         $this->json['message'] = 'Hash does not match.';
    //         return response()->json($this->json, 403); // Forbidden
    //     }
    //
    //     // Start tallying up the experience gained.
    //     $compendium_experience = $this->get_compendium_experience($request);
    //     $compendium_experience->total_experience = max($compendium_experience->total_experience + $request['amount'], 0);
    //
    //     // Create an event for audit purposes before saving.
    //     $compendium_experience_event = $this->create_compendium_experience_event($request, $compendium_experience);
    //     $compendium_experience->save();
    //
    //     // Return API status.
    //     $this->json['status'] = 'success';
    //     $this->json['message'] = 'Experience successfully added to account! Audit record has been created.';
    //     return response()->json($this->json, 200); // OK
    // }

    private function get_daily_experience (Request $request, $jsonify_data = false) {
        $daily_experience = DailyExperience::where('komo_username', $request['komo-username'])
            ->whereDate('created_at', Carbon::today())
            ->orderBy('id', 'ASC')
            ->first();

        if ($daily_experience == null) {
            $daily_experience = $this->initialize_daily_experience($request);
        }

        if ($jsonify_data) return response()->json($daily_experience, 200); // OK
        return $daily_experience;
    }

    private function get_compendium_experience (Request $request, $jsonify_data = false) {
        $compendium_experience = CompendiumExperience::where('komo_username', $request['komo-username'])
            ->whereDate('created_at', Carbon::today())
            ->orderBy('id', 'ASC')
            ->first();

        if ($compendium_experience == null) {
            $compendium_experience = $this->initialize_compendium_experience($request);
        }

        if ($jsonify_data) return response()->json($compendium_experience, 200); // OK
        return $compendium_experience;
    }

    /* ----- HELPER FUNCTIONS ----- */

    private function create_daily_experience_event(Request $request, DailyExperience $daily_experience) {
        $delta = $daily_experience->total_experience - $daily_experience->getOriginal('total_experience');

        return DailyExperienceEvent::create([
            'daily_experience_id' => $daily_experience->id,
            'source' => $request['source'],
            'delta' => $delta,
        ]);
    }

    private function initialize_daily_experience(Request $request) {
        $daily_experience = DailyExperience::create([
            'komo_username' => $request['komo-username'],
            'total_experience' => 0,
        ]);

        return $daily_experience;
    }

    private function initialize_compendium_experience(Request $request) {
        $compendium_experience = CompendiumExperience::create([
            'season_id' => $request['season-id'],
            'komo_username' => $request['komo-username'],
            'total_experience' => 0,
        ]);

        return $compendium_experience;
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
            'komo-username' => 'required|exists:tb_account,komo_username|max:255',
        ]);

        if ($validator->fails()) {
            $this->json['message'] = $validator->errors();
            return false;
        }

        return true;
    }

    private function verify_rate_limit(Request $request, $attempts_per_minute = 30){
        $rate_limited = !RateLimiter::attempt(
            'User: ' . $request['komo-username'],
            $attempts_per_minute,
            function() {}
        );

        if ($rate_limited) {
            $this->json['message'] = 'You are being rate limited.';
            return true;
        }

        return false;
    }

    private function generate_local_hash($local_string, $komo_username){
        $cipher_algorithm = 'AES-256-CBC';
        $passphrase = $this->retrieve_user_salt($komo_username);
        $options = 0;
        $iv = env('XP_SECURITY_KEY', null);

        $local_hash = openssl_encrypt($local_string, $cipher_algorithm, $passphrase, $options, $iv);
        return $local_hash;
    }

    private function retrieve_user_salt($komo_username){
        return DB::table('tb_account')
            ->where('komo_username', $komo_username)
            ->where('is_verified', 1)
            ->where('is_suspended', 0)
            ->first()
            ->salt;
    }
}
