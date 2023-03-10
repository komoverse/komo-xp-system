<?php

namespace App\Http\Controllers\Subcontrollers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\UnifiedDailyExperience;
use App\Models\UnifiedDailyExperienceEvent;
use App\Models\GameExperienceMultiplier;
use App\Helpers\Helper;
use Carbon\Carbon;

class UnifiedDailyExperienceController extends Controller
{
    public function __construct(){
        $this->json = array('status' => 'fail', 'message' => null);
    }

    public function add_unified_daily_experience(Request $request){
        // Check if game has any multipliers (default to 0x if there is none).
        $game_multipliers = GameExperienceMultiplier::where('api_key', $request['api_key'])->first();
        $unified_daily_xp_multiplier = 0.0;

        if (isset($game_multipliers)) $unified_daily_xp_multiplier = $game_multipliers->unified_daily_multiplier;
        else GameExperienceMultiplier::create([
            'api_key' => $request['api_key'],
            'unified_daily_multiplier' => 1,
            'daily_multiplier' => 1,
            'mmr_multiplier' => 1,
            'compendium_multiplier' => 1,
        ]);

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

    public function get_unified_daily_experience(Request $request,$jsonify_data = false) {
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
