<?php

namespace App\Http\Controllers\Subcontrollers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\DailyExperience;
use App\Models\DailyExperienceEvent;
use App\Models\GameExperienceMultiplier;
use App\Helpers\Helper;
use Carbon\Carbon;

class DailyExperienceController extends Controller
{
    public function __construct(){
        $this->json = array('status' => 'fail', 'message' => null);
    }

    public function add_daily_experience(Request $request){
        // Check if game has any multipliers (default to 0x if there is none).
        $game_multipliers = GameExperienceMultiplier::where('api_key', $request['api_key'])->first();
        $daily_xp_multiplier = 0.0;
        if (isset($game_multipliers)) {
            $daily_xp_multiplier = $game_multipliers->daily_multiplier;
        }

        // Start tallying up the experience gained.
        $daily_experience = $this->get_daily_experience($request);
        $daily_experience->total_experience = max($daily_experience->total_experience + ($daily_xp_multiplier * $request['amount']), 0);

        // Create an event for audit purposes before saving.
        $daily_experience_event = $this->create_daily_experience_event($request, $daily_experience);
        $daily_experience->save();

        // Return API status.
        $this->json['status'] = 'success';
        $this->json['message'] = 'Daily Experience successfully added to account! Audit record has been created.';
        $this->json['data']['daily_experience'] = $daily_experience;
        $this->json['data']['daily_experience_event'] = $daily_experience_event;
        return response()->json($this->json, 200); // OK
    }

    public function get_daily_experience(Request $request, $jsonify_data = false) {
        // Validate entry.
        $validator = Validator::make($request->all(), [
            'account_id' => 'required|exists:tb_account,id|max:255',
            'api_key' => 'required|exists:tb_api_key,api_key|max:255',
        ]);

        if ($validator->fails()) {
            $this->json['message'] = $validator->errors();
            return response()->json($this->json, 400); // Bad Request
        }

        // Check for Daily experience based on given api key.
        $daily_experience = DailyExperience::where('account_id', $request['account_id'])
            ->where('api_key', $request['api_key'])
            ->whereDate('created_at', Carbon::today())
            ->orderBy('id', 'ASC')
            ->first();

        // If no daily experience found, initialize one.
        if ($daily_experience == null) {
            $daily_experience = $this->initialize_daily_experience($request);
        }

        // Return data.
        if ($jsonify_data) return response()->json($daily_experience, 200); // OK
        return $daily_experience;
    }

    /* ----- HELPER FUNCTIONS ----- */

    private function create_daily_experience_event(Request $request, DailyExperience $daily_experience) {
        $delta = $daily_experience->total_experience - $daily_experience->getOriginal('total_experience');

        return DailyExperienceEvent::create([
            'daily_experience_id' => $daily_experience->id,
            'delta' => $delta,
        ]);
    }

    private function initialize_daily_experience(Request $request) {
        // Initialize Daily Experience.
        $daily_experience = DailyExperience::create([
            'account_id' => $request['account_id'],
            'api_key' => $request['api_key'],
            'total_experience' => 0,
        ]);

        return $daily_experience;
    }
}
