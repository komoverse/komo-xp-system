<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\DB;
use App\Models\CompendiumExperience;
use App\Models\CompendiumExperienceEvent;
use App\Models\CompendiumSeason;
use App\Models\GameExperienceMultiplier;
use App\Helpers\Helper;
use Carbon\Carbon;

class CompendiumExperienceController extends Controller
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

    public function api_compendium_experience(Request $request){
        // Guard statements.
        if (!$this->connection_safe) return response()->json($this->json, 403); // Forbidden
        if (!$this->user_exists) return response()->json($this->json, 400); // Bad Request
        if ($this->rate_limited) return response()->json($this->json, 429); // Too Many Requests

        // List of APIs.
        if ($request['add-compendium-experience'] == 'true' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->add_compendium_experience($request);
        }

        if ($request['get-compendium-experience'] == 'true' && $_SERVER['REQUEST_METHOD'] === 'GET') {
            $jsonify_data = true;
            return $this->get_compendium_experience($request, $jsonify_data);
        }
    }

    /* ----- API FUNCTIONS ----- */

    private function add_compendium_experience(Request $request){
        // Validate entry.
        $validator = Validator::make($request->all(), [
            'api-key' => 'required|exists:tb_api_key,api_key|max:255',
            'game-experience' => 'required|numeric|max:2147483647',
            'security-hash' => 'required',
        ]);

        if ($validator->fails()) {
            $this->json['message'] = $validator->errors();
            return response()->json($this->json, 400); // Bad Request
        }

        // Verify security hash.
        $local_string = $request['account-id'] . $request['api-key'] . $request['game-experience'];
        $local_hash = Helper::generate_local_hash($local_string, $request['account-id']);

        if ($local_hash != $request['security-hash']) {
            $this->json['message'] = 'Hash does not match.';
            return response()->json($this->json, 403); // Forbidden
        }

        // Check if game has any multipliers (default to 0x if there is no multiplier).
        $game_multipliers = GameExperienceMultiplier::where('api_key', $request['api-key'])->first();
        $compendium_xp_multiplier = 0.0;
        if (isset($game_multipliers)) {
            $compendium_xp_multiplier = $game_multipliers->compendium_multiplier;
        }

        // Check if a season is currently running. If not, return an acknowledged status.
        $compendium_experience = $this->get_compendium_experience($request);
        if ($compendium_experience == false) {
            $this->json['status'] = 'acknowledged';
            $this->json['message'] = 'Request acknowledged, but there is no running season at the moment. No data has been added to the database.';
            return response()->json($this->json, 200); // OK
        }

        // Tally up the compendium experience with the returned game multipliers.
        $compendium_experience->total_experience += $compendium_xp_multiplier * $request['game-experience'];
        $compendium_experience_event = $this->create_compendium_experience_event($request, $compendium_experience);
        $compendium_experience->save();

        // Return API status.
        $this->json['status'] = 'success';
        $this->json['message'] = 'Compendium Experience successfully added to account! Audit record has been created.';
        $this->json['data'] = $compendium_experience;
        return response()->json($this->json, 200); // OK
    }

    private function get_compendium_experience(Request $request, $jsonify_data = false) {
        // Get current season.
        $current_season = CompendiumSeason::where('start_date', '<=', Carbon::now())
            ->where('end_date', '>=', Carbon::now())
            ->orderBy('id', 'ASC')
            ->first();

        // If there is no season running currently, return a response/false.
        if ($jsonify_data == true && $current_season == null){
            $this->json['status'] = 'acknowledged';
            $this->json['message'] = 'Request acknowledged, but there is currently no running seasons at the moment.';
            return response()->json($this->json, 200); // OK
        }

        if ($jsonify_data == false && $current_season == null){
            return false;
        }

        // Check for compendium experience based on current season ID.
        $compendium_experience = CompendiumExperience::where('account_id', $request['account-id'])
            ->where('season_id', $current_season->id)
            ->orderBy('id', 'ASC')
            ->first();

        // If no compendium experience found, initialize one.
        if ($compendium_experience == null) {
            $compendium_experience = $this->initialize_compendium_experience($request, $current_season->id);
        }

        // Return data.
        if ($jsonify_data) return response()->json($compendium_experience, 200); // OK
        return $compendium_experience;
    }

    /* ----- HELPER FUNCTIONS ----- */

    private function create_compendium_experience_event(Request $request, CompendiumExperience $compendium_experience) {
        $delta = $compendium_experience->total_experience - $compendium_experience->getOriginal('total_experience');

        return CompendiumExperienceEvent::create([
            'compendium_experience_id' => $compendium_experience->id,
            'api_key' => $request['api-key'],
            'delta' => $delta,
        ]);
    }

    private function initialize_compendium_experience(Request $request, $season_id) {
        $compendium_experience = CompendiumExperience::create([
            'season_id' => $current_season->id,
            'account_id' => $request['account-id'],
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
}
