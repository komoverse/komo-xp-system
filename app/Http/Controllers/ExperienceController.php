<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;
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
    public function __construct(){
        $this->is_https = Helper::is_https();
        $this->is_local = Helper::is_local();
        $this->is_production = Helper::is_production();

        // Default JSON.
        $this->json = array('status' => 'fail', 'message' => null);
    }

    public function api_daily_experience_post(Request $request){
        // Verify connection is from HTTPS, but automatically passes if APP_ENV is local.
        $connection_safe = $this->verify_connection($request);
        if (!$connection_safe) return response()->json($this->json, 400); // Bad Request

        // Verify that user exists in the main database.
        $user_exists = $this->verify_user($request);
        if (!$user_exists) return response()->json($this->json, 400); // Bad Request

        // Throttle attempts to this API by 30 attempts/minute.
        $rate_limited = $this->verify_rate_limit($request);
        if ($rate_limited) return response()->json($this->json, 400); // Bad Request

        // List of API actions.
        if ($request['add-xp'] == 'true') {
            return $this->add_xp($request);
        }
    }

    /* ----- HELPER FUNCTIONS ----- */

    private function add_xp(Request $request){
        // Validate entry.
        $validator = Validator::make($request->all(), [
            'komo-username' => 'required|exists:tb_account,komo_username|max:255',
            'amount' => 'required|numeric|max:2147483647',
            'source' => 'required|exists:tb_api_key,source|max:255',
            'api_key' => 'required|exists:tb_api_key,api_key|max:255',
        ]);

        if ($validator->fails()) {
            $this->json['message'] = (!$this->is_production) ? $validator->errors() : null;
            return response()->json($this->json, 400); // Bad Request
        }

        // Start tallying up the experience gained.
        $daily_experience = $this->retrieve_daily_experience($request);
        $daily_experience->total_experience += max($daily_experience->total_experience + $request['amount'], 0);
        $daily_experience->save();

        // Create an event for audit purposes.
        $daily_experience_event = $this->create_daily_experience_event($request, $daily_experience);

        // Return API status.
        $this->json['status'] = 'success';
        $this->json['message'] = (!$this->is_production) ? 'Experience successfully added to account! Audit record has been created.': null;
        return $this->json;
    }

    private function create_daily_experience_event(Request $request, DailyExperience $daily_experience) {
        $delta = $daily_experience->total_experience - $daily_experience->getOriginal('total_experience');

        return DailyExperienceEvent::create([
            'daily_experience_id' => $daily_experience->id,
            'source' => $request['source'],
            'delta' => $delta,
        ]);
    }

    private function retrieve_daily_experience (Request $request) {
        $daily_experience = DailyExperience::where('komo_username', $request['komo-username'])
            ->whereDate('created_at', Carbon::today())
            ->orderBy('id', 'ASC')
            ->first();

        if ($daily_experience == null) {
            $daily_experience = $this->initialize_daily_experience($request);
        }

        return $daily_experience;
    }

    private function initialize_daily_experience(Request $request) {
        $daily_experience = DailyExperience::create([
            'komo_username' => $request['komo-username'],
            'total_experience' => 0,
        ]);

        return $daily_experience;
    }

    private function verify_connection(Request $request){
        if ($this->is_https == false && !$this->is_local == true) {
            $this->json['message'] = (!$this->is_production) ? 'Connection fired from an unsecured connection (use HTTPS).' : null;
            return false;
        }

        return true;
    }

    private function verify_user(Request $request){
        $validator = Validator::make($request->all(), [
            'komo-username' => 'required|exists:tb_account,komo_username|max:255',
        ]);

        if ($validator->fails()) {
            $this->json['message'] = (!$this->is_production) ? $validator->errors() : null;
            return false;
        }

        return true;
    }

    private function verify_rate_limit(Request $request, $attempts_per_minute = 1){
        $rate_limited = !RateLimiter::attempt(
            'User: ' . $request['komo-username'],
            $attempts_per_minute,
            function() {}
        );

        if ($rate_limited) {
            $this->json['message'] = (!$this->is_production) ? 'You are being rate limited.' : null;
            return true;
        }

        return false;
    }
}
