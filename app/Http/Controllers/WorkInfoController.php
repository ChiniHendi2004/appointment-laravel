<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class WorkInfoController extends Controller
{

    // Fetch Work Information
    public function FetchWorkInfo(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }

            $data = DB::table('work_information')->where('user_id', $user->id)->get();

            return response()->json(['status' => true, 'data' => $data]);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['status' => false, 'message' => 'Token is invalid or expired'], 401);
        }
    }

    // Store and Update Work Information
    public function CreateOrUpdateWorkInfo(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }

           
            $existingInfo = DB::table('work_information')->where('user_id', $user->id)->first();

            if ($existingInfo) {
                // ✅ Update existing record
                DB::table('work_information')
                    ->where('user_id', $user->id)
                    ->update([
                        'work_name' => $request->input('work_name'),
                        'company_name' => $request->input('company_name'),
                        'position' => $request->input('position'),
                        'duration' => $request->input('duration'),
                        'current_working' => $request->input('current_working'),
                        'state' => $request->input('state'),
                        'district' => $request->input('district'),
                        'village' => $request->input('village'),
                        'pincode' => $request->input('pincode'),
                        'updated_at' => now(),
                    ]);

                return response()->json(['status' => true, 'message' => 'Updated successfully']);
            } else {
                // ✅ Insert new record
                $insertId = DB::table('work_information')->insertGetId([
                    'user_id' => $user->id,
                    'work_name' => $request->input('work_name'),
                    'company_name' => $request->input('company_name'),
                    'position' => $request->input('position'),
                    'duration' => $request->input('duration'),
                    'current_working' => $request->input('current_working'),
                    'state' => $request->input('state'),
                    'district' => $request->input('district'),
                    'village' => $request->input('village'),
                    'pincode' => $request->input('pincode'),
                    'updated_at' => now(),
                ]);

                return response()->json(['status' => true, 'message' => 'Inserted successfully', 'id' => $insertId]);
            }
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['status' => false, 'message' => 'Token is invalid or expired'], 401);
        }
    }
}
