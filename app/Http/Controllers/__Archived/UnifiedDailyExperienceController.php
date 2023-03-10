<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\UnifiedDailyExperience;
use App\Models\UnifiedDailyExperienceEvent;
use App\Models\GameExperienceMultiplier;
use App\Helpers\Helper;
use Carbon\Carbon;

class UnifiedDailyExperienceController extends Controller
{
    public function __construct(Request $request){
        $this->is_https = Helper::is_https();
        $this->is_local = Helper::is_local();
        $this->json = array('status' => 'fail', 'message' => null);
    }

    public function api_unified_daily_experience(Request $request){
        // Guard statements.
        if (!Helper::verify_connection($request)) {
            $this->json['message'] = 'Connection fired from an unsecured connection (use HTTPS).';
            return response()->json($this->json, 403); // Forbidden
        }

        if (!Helper::verify_user($request)) {
            $this->json['message'] = 'Invalid account ID. This either means that the user does not exist, registered but not verified, or is suspended.';
            return response()->json($this->json, 400); // Bad Request
        }

        if (Helper::verify_rate_limit($request)) {
            $this->json['message'] = 'You are being rate limited.';
            return response()->json($this->json, 429); // Too Many Requests
        }

        // List of APIs.
        if ($request['add_unified_daily_experience'] == 'true' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->add_unified_daily_experience($request);
        }

        if ($request['get_unified_daily_experience'] == 'true' && $_SERVER['REQUEST_METHOD'] === 'GET') {
            $jsonify_data = true;
            return $this->get_unified_daily_experience($request, $jsonify_data);
        }
    }

    /* ----- API FUNCTIONS ----- */

    private function add_unified_daily_experience(Request $request){
        // Validate entry.
        $validator = Validator::make($request->all(), [
            'account_id' => 'required|exists:tb_account,id|max:255',
            'amount' => 'required|numeric|max:2147483647',
            'api_key' => 'required|exists:tb_api_key,api_key|max:255',
            'security_hash' => 'required',
        ]);

        if ($validator->fails()) {
            $this->json['message'] = $validator->errors();
            return response()->json($this->json, 400); // Bad Request
        }

        // Verify security hash.
        $local_string = $request['account_id'] . $request['api_key'] . $request['amount'];
        $local_hash = Helper::generate_local_hash($local_string, $request['account_id']);

        if ($local_hash != $request['security_hash']) {
            $this->json['message'] = 'Hash does not match.';
            return response()->json($this->json, 403); // Forbidden
        }

        // Check if game has any multipliers (default to 0x if there is none).
        $game_multipliers = GameExperienceMultiplier::where('api_key', $request['api_key'])->first();
        $unified_daily_xp_multiplier = 0.0;
        if (isset($game_multipliers)) {
            $unified_daily_xp_multiplier = $game_multipliers->unified_daily_multiplier;
        }

        // Start tallying up the experience gained.
        $unified_daily_experience = $this->get_unified_daily_experience($request);
        $unified_daily_experience->total_experience = max($unified_daily_experience->total_experience + ($unified_daily_xp_multiplier * $request['amount']), 0);

        // Create an event for audit purposes before saving.
        $unified_daily_experience_event = $this->create_unified_daily_experience_event($request, $unified_daily_experience);
        $unified_daily_experience->save();

        // Return API status.
        $this->json['status'] = 'success';
        $this->json['message'] = 'Unified Daily Experience successfully added to account! Audit record has been created.';
        $this->json['data']['unified_daily_experience'] = $unified_daily_experience;
        $this->json['data']['unified_daily_experience_event'] = $unified_daily_experience_event;
        return response()->json($this->json, 200); // OK
    }

    private function get_unified_daily_experience(Request $request,$jsonify_data = false) {
        // Validate entry.
        $validator = Validator::make($request->all(), [
            'account_id' => 'required|exists:tb_account,id|max:255',
        ]);

        if ($validator->fails()) {
            $this->json['message'] = $validator->errors();
            return response()->json($this->json, 400); // Bad Request
        }

        // Get unified_daily experience.
        $unified_daily_experience = UnifiedDailyExperience::where('account_id', $request['account_id'])
            ->whereDate('created_at', Carbon::today())
            ->orderBy('id', 'ASC')
            ->first();

        if ($unified_daily_experience == null) {
            $unified_daily_experience = $this->initialize_unified_daily_experience($request);
        }

        if ($jsonify_data) return response()->json($unified_daily_experience, 200); // OK
        return $unified_daily_experience;
    }

    /* ----- HELPER FUNCTIONS ----- */

    private function create_unified_daily_experience_event(Request $request, UnifiedDailyExperience $unified_daily_experience) {
        $delta = $unified_daily_experience->total_experience - $unified_daily_experience->getOriginal('total_experience');

        return UnifiedDailyExperienceEvent::create([
            'unified_daily_experience_id' => $unified_daily_experience->id,
            'api_key' => $request['api_key'],
            'delta' => $delta,
        ]);
    }

    private function initialize_unified_daily_experience(Request $request) {
        $unified_daily_experience = UnifiedDailyExperience::create([
            'account_id' => $request['account_id'],
            'total_experience' => 0,
        ]);

        return $unified_daily_experience;
    }
}
