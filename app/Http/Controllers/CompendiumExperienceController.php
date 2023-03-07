<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\DB;
use App\Models\CompendiumExperience;
use App\Models\CompendiumExperienceEvent;
use App\Models\GameExperienceMultiplier;
// use App\Models\RawExperienceRecord;
use App\Models\Season;
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
    }

    /* ----- API FUNCTIONS ----- */

    private function add_compendium_experience(Request $request){
        // Validate entry.
        $validator = Validator::make($request->all(), [
            'season-id' => 'required|exists:tb_seasons,id',
            'source' => 'required|max:255',
            'api-key' => 'required|exists:tb_api_key,api_key|max:255',
            'game-experience' => 'required|numeric|max:2147483647',
            'security-hash' => 'required',
        ]);

        if ($validator->fails()) {
            $this->json['message'] = $validator->errors();
            return response()->json($this->json, 400); // Bad Request
        }

        // Verify security hash.
        $local_string = $request['account-id'] . $request['season-id'] . $request['source'] . $request['api-key'] . $request['game-experience'];
        $local_hash = $this->generate_local_hash($local_string, $request['account-id']);

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

        dd($compendium_xp_multiplier);
        // if (isset($compendium_xp_multiplier)) $compendium_xp_multipli

        // Find the compendium multipliers of the respective game.
        // $game_multiplier =

        // // Check if .env has KOMOCHESS_API_KEY.
        // $komochess_api_key = env('KOMOCHESS_API_KEY');
        // if (!$komochess_api_key) {
        //     $this->json['message'] = 'It\'s not you, it\'s us. We forgot to set our API key.';
        //     $this->json['data'] = $request->all();
        //     return response()->json($this->json, 500); // Internal Server Error
        // }

        //
        //
        // dd("This works so far, but databases that cover every game's experiences still need to be made. That's my (Karuna's) responsibility.");
        //
        // // If the experience is specifically sourced from Komochess, add the compendium XP without further calculations.
        // $average_experience_per_player_per_day = env('KOMOCHESS_DEFAULT_AVERAGE_XP', 100);
        //
        // $average_exp_per_day = 100;
        // $pegging_factor = $average_exp_per_day / $request['game-experience'];
        //
        // dd($pegging_factor);
        //
        // // Start tallying up the experience gained.
        // $compendium_experience = $this->get_compendium_experience($request);
        // $compendium_experience->total_experience = max($compendium_experience->total_experience + ($request['game-experience'] * $pegging_factor), 0);

        // // Create an event for audit purposes before saving.
        // $compendium_experience_event = $this->create_compendium_experience_event($request, $compendium_experience);
        // $compendium_experience->save();
        //
        // // Return API status.
        // $this->json['status'] = 'success';
        // $this->json['message'] = 'Experience successfully added to account! Audit record has been created.';
        // return response()->json($this->json, 200); // OK
    }

    // private function get_compendium_experience(Request $request, $jsonify_data = false) {
    //     $compendium_experience = CompendiumExperience::where('account_id', $request['account-id'])
    //         ->whereDate('created_at', Carbon::today())
    //         ->orderBy('id', 'ASC')
    //         ->first();
    //
    //     if ($compendium_experience == null) {
    //         $compendium_experience = $this->initialize_compendium_experience($request);
    //     }
    //
    //     if ($jsonify_data) return response()->json($compendium_experience, 200); // OK
    //     return $compendium_experience;
    // }

    /* ----- HELPER FUNCTIONS ----- */

    // private function create_compendium_experience_event(Request $request, CompendiumExperience $compendium_experience) {
    //     $delta = $compendium_experience->total_experience - $compendium_experience->getOriginal('total_experience');
    //
    //     return DailyExperienceEvent::create([
    //         'compendium_experience_id' => $compendium_experience->id,
    //         'source' => $request['source'],
    //         'delta' => $delta,
    //     ]);
    // }

    // private function initialize_compendium_experience(Request $request) {
    //     $compendium_experience = CompendiumExperience::create([
    //         'season_id' => $request['season-id'],
    //         'account_id' => $request['account-id'],
    //         'total_experience' => 0,
    //     ]);
    //
    //     return $compendium_experience;
    // }

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
