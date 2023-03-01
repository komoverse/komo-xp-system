<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DailyExperience;
use App\Models\DailyExperienceEvent;
use App\Models\NmrExperience;
use App\Models\NmrExperienceEvent;
use App\Models\Experience;
use App\Models\ExperienceEvent;

class ExperienceController extends Controller
{
    public function api_daily_experience_post(Request $request){
        if ($request['add-xp'] == 'true') {
            return json_encode($this->add_xp());
        }

        return "test get";
    }

    // public function api_daily_experience_get(Request $request){
    //     if ($request['get-front-page'] == 'true') {
    //         return json_encode($this->get_frontpage());
    //     }
    // }

    /* ----- HELPER FUNCTIONS ----- */

    private function add_xp($request){
        
    }
}
