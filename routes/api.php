<?php

use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\WorkInfoController;
use App\Http\Controllers\EducationController;
use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\PersonalInfoController;

// ✅ Register API
Route::post('/register', function (Request $request) {
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:6|confirmed',
    ]);

    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()], 400);
    }

    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
    ]);

    $token = JWTAuth::fromUser($user);

    return response()->json([
        'message' => 'User registered successfully',
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email
        ],
        'token' => $token
    ], 201);
});

// ✅ Login API
Route::post('/login', function (Request $request) {
    $credentials = $request->only('email', 'password');

    if (!$token = JWTAuth::attempt($credentials)) {
        return response()->json(['error' => 'Invalid credentials'], 401);
    }

    $user = Auth::user();

    return response()->json([
        'message' => 'Login successful',
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email
        ],
        'token' => 'Bearer ' . $token  // ✅ Add 'Bearer ' prefix
    ]);
});


// ✅ Protected Routes (Require Authentication)
Route::middleware(['jwt.auth'])->group(function () {
    // Get Authenticated User
    Route::get('/user', function () {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['error' => 'User not found'], 401);
            }
            return response()->json(['user' => $user]);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['error' => 'Token is invalid or expired'], 401);
        }
    });

    // ✅ Logout API
    Route::post('/logout', function () {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json(['message' => 'Successfully logged out']);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['error' => 'Invalid token'], 401);
        }
    });


    // Route::get('/personal-info/fetch', [PersonalInfoController::class, 'FetchPersonalInfo']);
    Route::get('/personal-info/get', [PersonalInfoController::class, 'FetchPersonalInfo']);
    Route::get('/personal-info/list', [PersonalInfoController::class, 'FetchPersonalList']);
    Route::post('/personal-info/Create', [PersonalInfoController::class, 'CreateOrUpdatePersonalInfo']);
    Route::post('/personal-info/update/{id}', [PersonalInfoController::class, 'CreateOrUpdatePersonalInfo']);
    Route::post('/edit-profile/img', [PersonalInfoController::class, 'updateProfileImage']);
    Route::get('/fetch-profile', [PersonalInfoController::class, 'FetchProfile']);

    // ✅ Education APIs 
    Route::post('/education-info/create', [EducationController::class, 'saveOrUpdateQualification']); // ✅ Create or Update
    Route::get('/education-info/get', [EducationController::class, 'getQualifications']);

    // Business Info Routes
    Route::get('/business-info', [BusinessController::class, 'FetchBusinessInfo']);
    Route::post('/business-info', [BusinessController::class, 'storeOrUpdateBusiness']);

     // Availability Routes
    Route::post('/set-unavailability', [AvailabilityController::class, 'setUnavailability']);
    Route::get('/get-available-slots/{date}', [AvailabilityController::class, 'getAvailableSlots']);
    Route::get('/get-slots/{date}', [AvailabilityController::class, 'getSlots']);


    // Booking Routes
    Route::post('/book-slot', [BookingController::class, 'bookSlot']);
    Route::post('/cancel-appointment', [BookingController::class, 'cancelAppointment']);
    Route::get('/user-appointments', [BookingController::class, 'getbookedbyAppointments']);
    Route::get('/my-appointments', [BookingController::class, 'getMyAppointments']);
    Route::get('/today-appointments', [BookingController::class, 'getTodayAppointments']);

    // Work Work Routes
    Route::get('/work-info/get', [WorkInfoController::class, 'FetchWorkInfo']);
    Route::post('/work-info/Create', [WorkInfoController::class, 'CreateOrUpdateWorkInfo']);
    //Role wise list
    Route::get('/users-by-role', [PersonalInfoController::class, 'getUsersByRole']);
});
