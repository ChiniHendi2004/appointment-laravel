<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Illuminate\Support\Facades\Log;

class PersonalInfoController extends Controller
{
    // ✅ Fetch all personal information for the logged-in user
    public function FetchPersonalInfo(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }

            $data = DB::table('personal_information')->where('user_id', $user->id)->get();

            return response()->json(['status' => true, 'data' => $data]);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['status' => false, 'message' => 'Token is invalid or expired'], 401);
        }
    }

    public function FetchProfile(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }
    
            $data = DB::table('personal_information')
                ->where('user_id', $user->id)
                ->select('full_name', 'profile_img')
                ->first();  // Use `first()` if you expect only one record
    
            if ($data) {
                // Generate full URL for the profile image if it exists
                if ($data->profile_img) {
                    // Use asset() to generate a URL to the public directory
                    $data->profile_img = asset('storage/' . $data->profile_img);
                }
    
                return response()->json(['status' => true, 'data' => $data]);
            }
            // Check if data is found
            else {
                return response()->json(['status' => false, 'message' => 'No profile found'], 404);
            }
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['status' => false, 'message' => 'Token is invalid or expired'], 401);
        }
    }
    


    // ✅ Store personal information (user_id is auto-fetched from JWT)
    public function CreateOrUpdatePersonalInfo(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }

            // Check if the personal info already exists
            $existingInfo = DB::table('personal_information')->where('user_id', $user->id)->first();

            if ($existingInfo) {
                // ✅ Update existing record
                DB::table('personal_information')
                    ->where('user_id', $user->id)
                    ->update([
                        'full_name' => $request->input('full_name'),
                        'date_of_birth' => $request->input('dob'),
                        'gender' => $request->input('gender'),
                        'email' => $request->input('email'),
                        'phone_no' => $request->input('phone'),
                        'state' => $request->input('state'),
                        'district' => $request->input('district'),
                        'village' => $request->input('village'),
                        'pincode' => $request->input('pincode'),
                        'role' => $request->input('role'),
                        'updated_at' => now(),
                    ]);

                return response()->json(['status' => true, 'message' => 'Updated successfully']);
            } else {
                // ✅ Insert new record
                $insertId = DB::table('personal_information')->insertGetId([
                    'user_id' => $user->id,
                    'full_name' => $request->input('full_name'),
                    'date_of_birth' => $request->input('dob'),
                    'gender' => $request->input('gender'),
                    'email' => $request->input('email'),
                    'phone_no' => $request->input('phone'),
                    'state' => $request->input('state'),
                    'district' => $request->input('district'),
                    'village' => $request->input('village'),
                    'pincode' => $request->input('pincode'),
                    'role' => $request->input('role'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return response()->json(['status' => true, 'message' => 'Inserted successfully', 'id' => $insertId]);
            }
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['status' => false, 'message' => 'Token is invalid or expired'], 401);
        }
    }


    public function FetchPersonalList(Request $request)
{
    try {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        // Fetch all personal info excluding the logged-in user
        $data = DB::table('personal_information')
            ->where('user_id', '!=', $user->id)
            ->get();

        // Loop through the data and generate full URLs for the profile image if it exists
        foreach ($data as $item) {
            if ($item->profile_img) {
                // Use asset() to generate a URL to the public directory
                $item->profile_img = asset('storage/' . $item->profile_img);
            }
        }

        return response()->json(['status' => true, 'data' => $data]);
    } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
        return response()->json(['status' => false, 'message' => 'Token is invalid or expired'], 401);
    }
}


    // ✅ Fetch single record
    public function fetchSingleInfo($id, Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            // ✅ Get the authenticated user directly

            if (!$user) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }

            $data = DB::table('personal_information')
                ->where('id', $id)
                ->where('user_id', $user->id) // ✅ Ensures only the user's data is fetched
                ->first();

            if ($data) {
                return response()->json(['status' => true, 'data' => $data]);
            }

            return response()->json(['status' => false, 'message' => 'Record not found'], 404);
        } catch (TokenExpiredException $e) {
            return response()->json(['status' => false, 'message' => 'Token expired'], 401);
        } catch (TokenInvalidException $e) {
            return response()->json(['status' => false, 'message' => 'Invalid token'], 401);
        } catch (JWTException $e) {
            return response()->json(['status' => false, 'message' => 'Token is invalid or expired'], 401);
        }
    }

    // ✅ Update personal information
    public function updatePersonalInfo(Request $request, $id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }

            $updated = DB::table('personal_information')
                ->where('id', $id)
                ->where('user_id', $user->id)
                ->update([
                    'full_name' => $request->input('full_name'),
                    'email' => $request->input('email'),
                    'phone_no' => $request->input('phone'),
                    'updated_at' => now(),
                ]);

            if ($updated) {
                return response()->json(['status' => true, 'message' => 'Updated successfully']);
            }

            return response()->json(['status' => false, 'message' => 'Update failed or no changes detected']);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['status' => false, 'message' => 'Token is invalid or expired'], 401);
        }
    }


    public function updateProfileImage(Request $request)
    {
        try {
            // Authenticate user from JWT token
            $user = JWTAuth::parseToken()->authenticate();

            // If user is not authenticated, return unauthorized response
            if (!$user) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }

            // Check if the request has a file
            if ($request->hasFile('profile_img')) {
                $file = $request->file('profile_img');

                // Generate a unique filename
                $fileName = time() . '.' . $file->getClientOriginalExtension();

                // Store the file in public disk (storage/app/public/profile_images)
                $filePath = $file->storeAs('profile_images', $fileName, 'public');

                // Update the user's profile image path in personal_information table
                DB::table('personal_information')
                    ->where('user_id', $user->id)  // Make sure you're matching the user_id
                    ->update(['profile_img' => $filePath]);

                return response()->json([
                    'status' => true,
                    'message' => 'Profile image updated successfully',
                    'image_url' => asset('storage/' . $filePath) // Return full image URL
                ]);
            }

            return response()->json(['status' => false, 'message' => 'No image uploaded'], 400);
        } catch (\Exception $e) {
            // Catch any errors
            return response()->json(['status' => false, 'message' => 'Something went wrong', 'error' => $e->getMessage()], 500);
        }
    }

    public function getUsersByRole(Request $request)
    {
        try {
            // Authenticate the user using JWT
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }

            // Get role from request
            $role = $request->query('role');
            // Fetch users based on role
            $users = DB::table('personal_information')
                ->where('role', $role)
                ->get();

            return response()->json(['status' => true, 'data' => $users], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Token is invalid or expired'], 401);
        }
    }
}
