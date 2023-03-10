<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Subcontrollers\UnifiedDailyExperienceController;
use App\Http\Controllers\Subcontrollers\DailyExperienceController;
use App\Http\Controllers\Subcontrollers\MmrExperienceController;
use App\Http\Controllers\Subcontrollers\CompendiumExperienceController;
use App\Helpers\Helper;

class ExperienceController extends Controller
{
    public function __construct(Request $request){
        // Initialize controllers.
        $this->unified_daily_experience_controller = new UnifiedDailyExperienceController;
        $this->daily_experience_controller = new DailyExperienceController;
        $this->mmr_experience_controller = new MmrExperienceController;
        $this->compendium_experience_controller = new CompendiumExperienceController;
    }

    public function api_experience(Request $request){
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
        if ($request['add_experience'] == 'true' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->add_experience($request);
        }

        if ($request['get_all_experiences'] == 'true' && $_SERVER['REQUEST_METHOD'] === 'GET') {
            return $this->get_experience($request);
        }

        if ($request['get_unified_daily_experience'] == 'true' && $_SERVER['REQUEST_METHOD'] === 'GET') {
            return $this->get_unified_daily_experience($request);
        }

        if ($request['get_daily_experience'] == 'true' && $_SERVER['REQUEST_METHOD'] === 'GET') {
            return $this->get_daily_experience($request);
        }

        if ($request['get_mmr_experience'] == 'true' && $_SERVER['REQUEST_METHOD'] === 'GET') {
            return $this->get_mmr_experience($request);
        }

        if ($request['get_compendium_experience'] == 'true' && $_SERVER['REQUEST_METHOD'] === 'GET') {
            return $this->get_compendium_experience($request);
        }
    }

    /* ----- API FUNCTIONS ----- */

    private function add_experience(Request $request){
        // Validate entry.
        $validator = Validator::make($request->all(), [
            'account_id' => 'required|exists:tb_account,id|max:255',
            'api_key' => 'required|exists:tb_api_key,api_key|max:255',
            'amount' => 'required|numeric|max:2147483647',
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

        // Add inputs into all four experience types.
        $json['data']['unified_daily_experience'] = $this->unified_daily_experience_controller->add_unified_daily_experience($request);
        $json['data']['daily_experience'] = $this->daily_experience_controller->add_daily_experience($request);
        $json['data']['mmr_experience'] = $this->mmr_experience_controller->add_mmr_experience($request);
        $json['data']['compendium_experience'] = $this->compendium_experience_controller->add_compendium_experience($request);

        return $json;
    }

    private function get_experience(Request $request){
        // Validate entry.
        $validator = Validator::make($request->all(), [
            'account_id' => 'required|exists:tb_account,id|max:255',
            'api_key' => 'required|exists:tb_api_key,api_key|max:255',
        ]);

        if ($validator->fails()) {
            $this->json['message'] = $validator->errors();
            return response()->json($this->json, 400); // Bad Request
        }

        // Return inputs of all four experience types.
        $json['data']['unified_daily_experience'] = $this->unified_daily_experience_controller->get_unified_daily_experience($request, true);
        $json['data']['daily_experience'] = $this->daily_experience_controller->get_daily_experience($request, true);
        $json['data']['mmr_experience'] = $this->mmr_experience_controller->get_mmr_experience($request, true);
        $json['data']['compendium_experience'] = $this->compendium_experience_controller->get_compendium_experience($request, true);

        return $json;
    }

    private function get_unified_daily_experience(Request $request){
        // Validate entry.
        $validator = Validator::make($request->all(), [
            'account_id' => 'required|exists:tb_account,id|max:255',
        ]);

        if ($validator->fails()) {
            $this->json['message'] = $validator->errors();
            return response()->json($this->json, 400); // Bad Request
        }

        return $this->unified_daily_experience_controller->get_unified_daily_experience($request, true);
    }

    private function get_daily_experience(Request $request){
        // Validate entry.
        $validator = Validator::make($request->all(), [
            'account_id' => 'required|exists:tb_account,id|max:255',
            'api_key' => 'required|exists:tb_api_key,api_key|max:255',
        ]);

        if ($validator->fails()) {
            $this->json['message'] = $validator->errors();
            return response()->json($this->json, 400); // Bad Request
        }

        return $this->daily_experience_controller->get_daily_experience($request, true);
    }

    private function get_mmr_experience(Request $request){
        // Validate entry.
        $validator = Validator::make($request->all(), [
            'account_id' => 'required|exists:tb_account,id|max:255',
            'api_key' => 'required|exists:tb_api_key,api_key|max:255',
        ]);

        if ($validator->fails()) {
            $this->json['message'] = $validator->errors();
            return response()->json($this->json, 400); // Bad Request
        }

        return $this->mmr_experience_controller->get_mmr_experience($request, true);
    }

    private function get_compendium_experience(Request $request){
        // Validate entry.
        $validator = Validator::make($request->all(), [
            'account_id' => 'required|exists:tb_account,id|max:255',
        ]);

        if ($validator->fails()) {
            $this->json['message'] = $validator->errors();
            return response()->json($this->json, 400); // Bad Request
        }

        return $this->compendium_experience_controller->get_compendium_experience($request, true);
    }
}
