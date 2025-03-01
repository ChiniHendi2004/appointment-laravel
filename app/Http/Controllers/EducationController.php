<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class EducationController extends Controller
{
    // ✅ Fetch or create/update education details
    public function saveOrUpdateQualification(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }

            // ✅ Validate request
            $request->validate([
                'qualification' => 'required|string',
                'institute_specialization' => 'required|string',
                'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            ]);

            // ✅ Check if the user already has an educational record
            $education = DB::table('educational_information')
                ->where('user_id', $user->id)
                ->first();

            $filePath = $education->file_path ?? null; // Keep existing file if no new file is uploaded

            // ✅ Handle file upload with a unique name
            if ($request->hasFile('file')) {
                $file = $request->file('file');

                // ✅ Delete old file if it exists
                if ($filePath) {
                    Storage::disk('public')->delete($filePath);
                }

                // ✅ Generate a unique filename and store it
                $fileName = time() . '.' . $file->getClientOriginalExtension();
                $filePath = $file->storeAs('education_files', $fileName, 'public');
            }

            if ($education) {
                // ✅ Update existing record
                DB::table('educational_information')
                    ->where('user_id', $user->id)
                    ->update([
                        'qualification' => $request->input('qualification'),
                        'institute_specialization' => $request->input('institute_specialization'),
                        'file_path' => $filePath,
                        'updated_at' => now(),
                    ]);

                return response()->json([
                    'status' => true,
                    'message' => 'Updated successfully',
                    'file_url' => $filePath ? asset("storage/$filePath") : null,
                ]);
            } else {
                // ✅ Create new record
                $insertId = DB::table('educational_information')->insertGetId([
                    'user_id' => $user->id,
                    'qualification' => $request->input('qualification'),
                    'institute_specialization' => $request->input('institute_specialization'),
                    'file_path' => $filePath,
                    'created_at' => now(),
                ]);

                return response()->json([
                    'status' => true,
                    'message' => 'Inserted successfully',
                    'id' => $insertId,
                    'file_url' => $filePath ? asset("storage/$filePath") : null,
                ]);
            }
        } catch (JWTException $e) {
            return response()->json(['status' => false, 'message' => 'Token is invalid or expired'], 401);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ✅ Fetch user education details
    public function getQualifications()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }

            $qualification = DB::table('educational_information')
                ->where('user_id', $user->id)
                ->select('id', 'qualification', 'institute_specialization', 
                         DB::raw("CONCAT('" . asset('storage/') . "/', file_path) as file_url"),
                         'created_at')
                ->first(); // Changed to first() since we are fetching one record

            return response()->json([
                'status' => true,
                'message' => 'Data fetched successfully',
                'data' => $qualification,
            ]);
        } catch (JWTException $e) {
            return response()->json(['status' => false, 'message' => 'Token is invalid or expired'], 401);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
