<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\DailyExperience;
use App\Models\DailyExperienceEvent;
use App\Models\GameExperienceMultiplier;
use App\Helpers\Helper;
use Carbon\Carbon;

class DailyExperienceController extends Controller
{
    public function __construct(Request $request){
        $this->is_https = Helper::is_https();
        $this->is_local = Helper::is_local();
        $this->json = array('status' => 'fail', 'message' => null);
    }

    public function api_daily_experience(Request $request){
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
        if ($request['add_daily_experience'] == 'true' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->add_daily_experience($request);
        }

        if ($request['get_daily_experience'] == 'true' && $_SERVER['REQUEST_METHOD'] === 'GET') {
            $jsonify_data = true;
            return $this->get_daily_experience($request, $jsonify_data);
        }
    }
}
