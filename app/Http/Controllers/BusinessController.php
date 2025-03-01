<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;


class BusinessController extends Controller
{
    // Fetch Business Information
    public function fetchBusinessInfo(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }

            // Fetch existing business information
            $business = DB::table('business_information')
                ->where('user_id', $user->id)
                ->first();

            if ($business) {
                // Generate full URL for the business logo if it exists
                if ($business->business_logo) {
                    // Use asset() to generate a URL to the public directory
                    $business->business_logo = asset('storage/' . $business->business_logo);
                }

                return response()->json(['status' => true, 'data' => $business]);
            } else {
                return response()->json(['status' => false, 'message' => 'No business information found'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Something went wrong', 'error' => $e->getMessage()], 500);
        }
    }


    public function storeOrUpdateBusiness(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }

            // Check if business data exists
            $business = DB::table('business_information')->where('user_id', $user->id)->first();

            // Handle File Upload
            $logoPath = $business->business_logo ?? null;
            if ($request->hasFile('business_logo')) {
                $file = $request->file('business_logo');

                // Delete the old file if it exists
                if ($logoPath && Storage::disk('public')->exists($logoPath)) {
                    Storage::disk('public')->delete($logoPath); // Delete file from storage/app/public
                }

                // ✅ Generate a unique filename and store it in the public folder
                $fileName = time() . '.' . $file->getClientOriginalExtension();
                $logoPath = $file->storeAs('business_logos', $fileName, 'public'); // Store file in storage/app/public/business_logos
            }

            if ($business) {
                // Update existing business information
                DB::table('business_information')
                    ->where('user_id', $user->id)
                    ->update([
                        'business_name' => $request->business_name,
                        'business_type' => $request->business_type,
                        'business_address' => $request->business_address,
                        'city' => $request->city,
                        'state' => $request->state,
                        'district' => $request->district,
                        'pincode' => $request->pincode,
                        'business_logo' => $logoPath,
                        'updated_at' => now(),
                    ]);

                return response()->json(['status' => true, 'message' => 'Business information updated successfully']);
            } else {
                // Create new business information
                DB::table('business_information')->insert([
                    'user_id' => $user->id,
                    'business_name' => $request->business_name,
                    'business_type' => $request->business_type,
                    'business_address' => $request->business_address,
                    'city' => $request->city,
                    'state' => $request->state,
                    'district' => $request->district,
                    'pincode' => $request->pincode,
                    'business_logo' => $logoPath,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return response()->json(['status' => true, 'message' => 'Business information saved successfully']);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Something went wrong', 'error' => $e->getMessage()], 500);
        }
    }
}
