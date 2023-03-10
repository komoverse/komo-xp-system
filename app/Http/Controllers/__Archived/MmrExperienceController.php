<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\MmrExperience;
use App\Models\MmrExperienceEvent;
use App\Helpers\Helper;
use Carbon\Carbon;

class MmrExperienceController extends Controller
{
    public function __construct(Request $request){
        $this->is_https = Helper::is_https();
        $this->is_local = Helper::is_local();
        $this->json = array('status' => 'fail', 'message' => null);
    }

    public function api_mmr_experience(Request $request){
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
        if ($request['add_mmr_experience'] == 'true' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->add_mmr_experience($request);
        }

        if ($request['get_mmr_experience'] == 'true' && $_SERVER['REQUEST_METHOD'] === 'GET') {
            $jsonify_data = true;
            return $this->get_mmr_experience($request, $jsonify_data);
        }
    }

    /* ----- API FUNCTIONS ----- */

    private function add_mmr_experience(Request $request){
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

        // Start tallying up the experience gained.
        $mmr_experience = $this->get_mmr_experience($request);
        $mmr_experience->total_experience += $request['amount'];

        // Create an event for audit purposes before saving.
        $mmr_experience_event = $this->create_mmr_experience_event($request, $mmr_experience);
        $mmr_experience->save();

        // Return API status.
        $this->json['status'] = 'success';
        $this->json['message'] = 'MMR Experience successfully added to account! Audit record has been created.';
        $this->json['data']['mmr_experience'] = $mmr_experience;
        $this->json['data']['mmr_experience_event'] = $mmr_experience_event;
        return response()->json($this->json, 200); // OK
    }

    private function get_mmr_experience(Request $request, $jsonify_data = false) {
        // Validate entry.
        $validator = Validator::make($request->all(), [
            'account_id' => 'required|exists:tb_account,id|max:255',
            'api_key' => 'required|exists:tb_api_key,api_key|max:255',
        ]);

        if ($validator->fails()) {
            $this->json['message'] = $validator->errors();
            return response()->json($this->json, 400); // Bad Request
        }

        // Check for MMR experience based on given api key.
        $mmr_experience = MmrExperience::where('account_id', $request['account_id'])
            ->where('api_key', $request['api_key'])
            ->orderBy('id', 'ASC')
            ->first();

        // If no mmr experience found, initialize one.
        if ($mmr_experience == null) {
            $mmr_experience = $this->initialize_mmr_experience($request);
        }

        // Return data.
        if ($jsonify_data) return response()->json($mmr_experience, 200); // OK
        return $mmr_experience;
    }

    /* ----- HELPER FUNCTIONS ----- */

    private function create_mmr_experience_event(Request $request, MmrExperience $mmr_experience) {
        $delta = $mmr_experience->total_experience - $mmr_experience->getOriginal('total_experience');

        return MmrExperienceEvent::create([
            'mmr_experience_id' => $mmr_experience->id,
            'delta' => $delta,
        ]);
    }

    private function initialize_mmr_experience(Request $request) {
        // // Get initial MMR experience (default to 0) if none found.
        // $initial_mmr_experience = DB::table('tb_default_mmr_score')
        //     ->where('api_key', $request['api_key'])
        //     ->first();

        if (isset($initial_mmr_experience)) {
            $initial_mmr_experience = $initial_mmr_experience->default;
        }

        if (!isset($initial_mmr_experience)) {
            $initial_mmr_experience = 0;
        }

        // Initialize MMR Experience.
        $mmr_experience = MmrExperience::create([
            'account_id' => $request['account_id'],
            'api_key' => $request['api_key'],
            'total_experience' => $initial_mmr_experience,
        ]);

        return $mmr_experience;
    }
}
