<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class BookingController extends Controller
{
    // ✅ Book a Slot
    public function bookSlot(Request $request)
    {
        Log::info('Incoming booking request:', $request->all()); // ✅ Log request payload

        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }

            $request->validate([
                'provider_id' => 'required|integer',
                'date' => 'required|date',
                'time_slot' => 'required|string'
            ]);

            $providerId = $request->provider_id;
            $date = $request->date;
            $timeSlot = $request->time_slot;

            Log::info("Checking availability for provider: $providerId, date: $date, time: $timeSlot");

            // Check if slot is available
            $isUnavailable = DB::table('unavailable_slots')
                ->where('user_id', $providerId)
                ->where('date', $date)
                ->where('time_slot', $timeSlot)
                ->exists();

            $isBooked = DB::table('appointments')
                ->where('provider_id', $providerId)
                ->where('date', $date)
                ->where('time_slot', $timeSlot)
                ->exists();

            if ($isUnavailable || $isBooked) {
                return response()->json(['status' => false, 'message' => 'Slot is not available'], 400);
            }

            // Book the slot
            DB::table('appointments')->insert([
                'provider_id' => $providerId,
                'customer_id' => $user->id,
                'date' => $date,
                'time_slot' => $timeSlot
            ]);

            Log::info("Appointment booked successfully for user {$user->id}");

            return response()->json(['status' => true, 'message' => 'Appointment booked successfully']);
        } catch (\Exception $e) {
            Log::error('Booking error: ' . $e->getMessage()); // ✅ Log error details
            return response()->json(['status' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }



    // ✅ Cancel Appointment
    public function cancelAppointment(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        // Check if the user owns this appointment
        $appointment = DB::table('appointments')
            ->where('id', $request->appointment_id)
            ->where(function ($query) use ($user) {
                $query->where('customer_id', $user->id)
                    ->orWhere('provider_id', $user->id);
            })
            ->first();

        if (!$appointment) {
            return response()->json(['message' => 'Unauthorized or appointment not found'], 403);
        }

        // Update the status instead of deleting the appointment
        $updated = DB::table('appointments')
            ->where('id', $request->appointment_id)
            ->update(['status' => '1']);

        if ($updated) {
            return response()->json(['message' => 'Appointment cancelled successfully']);
        } else {
            return response()->json(['message' => 'Failed to cancel appointment. No rows affected.'], 500);
        }
    }


    // ✅ Get User's Appointments
    public function getbookedbyAppointments()
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }
    
        $appointments = DB::table('appointments')
            ->where('customer_id', $user->id)
            ->where('STATUS', '0')
            ->leftJoin('personal_information', 'personal_information.user_id', '=', 'appointments.provider_id')
            ->select(
                'appointments.id',
                'appointments.provider_id',
                'appointments.customer_id',
                'appointments.date',
                'appointments.time_slot',
                'appointments.STATUS',
                'personal_information.user_id',
                'personal_information.full_name',
                'personal_information.date_of_birth',
                'personal_information.gender',
                'personal_information.email',
                'personal_information.phone_no',
                'personal_information.state',
                'personal_information.district',
                'personal_information.village',
                'personal_information.pincode',
                'personal_information.created_at',
                'personal_information.updated_at',
                'personal_information.role',
                'personal_information.profile_img'
            )
            ->get();
    
        // Modify profile_img to include full URL
        foreach ($appointments as $item) {
            if (!empty($item->profile_img)) {
                $item->profile_img = asset('storage/' . $item->profile_img);
            } else {
                $item->profile_img = asset('assets/images/dummy-profile.png'); // Default profile image
            }
        }
    
        return response()->json(['appointments' => $appointments]);
    }
    
    public function getMyAppointments()
{
    $user = JWTAuth::parseToken()->authenticate();
    if (!$user) {
        return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
    }

    $appointments = DB::table('appointments')
        ->where('provider_id', $user->id)
        ->where('STATUS', '0')
        ->leftJoin('personal_information', 'personal_information.user_id', '=', 'appointments.customer_id')
        ->select(
            'appointments.id',
            'appointments.provider_id',
            'appointments.customer_id',
            'appointments.date',
            'appointments.time_slot',
            'appointments.STATUS',
            'personal_information.user_id',
            'personal_information.full_name',
            'personal_information.date_of_birth',
            'personal_information.gender',
            'personal_information.email',
            'personal_information.phone_no',
            'personal_information.state',
            'personal_information.district',
            'personal_information.village',
            'personal_information.pincode',
            'personal_information.created_at',
            'personal_information.updated_at',
            'personal_information.role',
            'personal_information.profile_img'
        )
        ->get();

    // Modify profile_img to include full URL
    foreach ($appointments as $item) {
        if (!empty($item->profile_img)) {
            $item->profile_img = asset('storage/' . $item->profile_img);
        } else {
            $item->profile_img = asset('assets/images/dummy-profile.png'); // Default profile image
        }
    }

    return response()->json(['appointments' => $appointments]);
}

    public function getTodayAppointments()
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }
    
        $appointments = DB::table('appointments')
             ->where('provider_id', $user->id)
            ->where('STATUS', '0')
            ->whereDate('date', Carbon::today())
            ->leftJoin('personal_information', 'personal_information.user_id', '=', 'appointments.customer_id')
            ->select(
                'appointments.id',
                'appointments.provider_id',
                'appointments.customer_id',
                'appointments.date',
                'appointments.time_slot',
                'appointments.STATUS',
                'personal_information.user_id',
                'personal_information.full_name',
                'personal_information.date_of_birth',
                'personal_information.gender',
                'personal_information.email',
                'personal_information.phone_no',
                'personal_information.state',
                'personal_information.district',
                'personal_information.village',
                'personal_information.pincode',
                'personal_information.created_at',
                'personal_information.updated_at',
                'personal_information.role',
                'personal_information.profile_img'
            )
            ->get();
    
        if ($appointments->isNotEmpty()) {
            foreach ($appointments as $appointment) {
                if (!empty($appointment->profile_img)) {
                    $appointment->profile_img = asset('storage/' . $appointment->profile_img);
                }
            }
            return response()->json(['status' => true, 'data' => $appointments]);
        } else {
            return response()->json(['status' => false, 'message' => 'No appointments found']);
        }
    }
    
}
