<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DailyExperience;
use App\Models\DailyExperienceEvent;
use App\Models\NmrExperience;
use App\Models\NmrExperienceEvent;
use App\Models\Experience;
use App\Models\ExperienceEvent;
use App\Helpers\Helper;
use Carbon\Carbon;

class ExperienceController extends Controller
{
    public function api_daily_experience_post(Request $request){
        // Verify connection is from HTTPS.
        if (Helper::is_https() == false && env('APP_ENV') != 'local') {
            return json_encode(array([
                'status' => 'fail',
                'message' => 'Connection fired from an unsecured connection (use HTTPS).',
            ]));
        }

        // List of API actions.
        if ($request['add-xp'] == 'true') {
            return json_encode($this->add_xp($request));
        }
    }

    // public function api_daily_experience_get(Request $request){
    //     if ($request['get-front-page'] == 'true') {
    //         return json_encode($this->get_frontpage());
    //     }
    // }

    /* ----- HELPER FUNCTIONS ----- */

    private function add_xp(Request $request){
        // TODO: REWORK VALIDATION SO IT SENDS A PROPER ERROR MESSAGE
        $validated = $request->validate([
            'komo-username' => 'required|max:255',
            'amount' => 'required|numeric|max:2147483647',
            'source' => 'required|max:255',
        ]);

        // Initialize variables.
        $daily_experience = $this->get_daily_experience($request);
        $komo_username = $request['komo-username'];
        $amount = $request['amount'];
        $source = $request['source'];

        // Start modifying current daily experience for user.
        $daily_experience->total_experience += $amount;
        if ($daily_experience->total_experience < 0 ) {
            $daily_experience->total_experience = 0;
        }

        $daily_experience->save();

        // Create audit record of this EXP event.
        $delta = $daily_experience->total_experience - $daily_experience->getOriginal('total_experience');
        $daily_experience_event = DailyExperienceEvent::create([
            'daily_experience_id' => $daily_experience->id,
            'source' => $source,
            'delta' => $delta,
        ]);

        return array([
            'status' => 'success',
            'message' => 'Experience successfully added to account! Audit record has been created.',
        ]);
    }

    private function get_daily_experience (Request $request) {
        $komo_username = $request['komo-username'];

        $daily_experience = DailyExperience::where('komo_username', $komo_username)
            ->whereDate('created_at', Carbon::today())
            ->orderBy('id', 'ASC')
            ->first();

        if ($daily_experience == null) {
            $daily_experience = $this->initialize_daily_experience($request);
        }

        return $daily_experience;
    }

    private function initialize_daily_experience(Request $request) {
        $komo_username = $request['komo-username'];

        $daily_experience = DailyExperience::create([
            'komo_username' => $komo_username,
            'total_experience' => 0,
        ]); 

        return $daily_experience;
    }
}
