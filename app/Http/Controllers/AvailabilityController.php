<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class AvailabilityController extends Controller
{
    // ✅ Set Unavailability (Mark unavailable slots)
    public function setUnavailability(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'date' => 'required|date',
            'time_slots' => 'required|array' // Example: ["10:00 AM", "11:00 AM"]
        ]);

        $date = $request->date;
        $timeSlots = $request->time_slots;

        // Remove old unavailability for this date
        DB::table('unavailable_slots')
            ->where('user_id', $user->id)
            ->where('date', $date)
            ->delete();

        // Insert new unavailable slots
        foreach ($timeSlots as $slot) {
            DB::table('unavailable_slots')->insert([
                'user_id' => $user->id,
                'date' => $date,
                'time_slot' => $slot
            ]);
        }

        return response()->json(['message' => 'Unavailability set successfully']);
    }

    // ✅ Fetch Available Slots
    public function getAvailableSlots($date)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $allSlots = [
            "09:00 AM",
            "10:00 AM",
            "11:00 AM",
            "12:00 PM",
            "01:00 PM",
            "02:00 PM",
            "03:00 PM",
            "04:00 PM",
            "05:00 PM",
            "06:00 PM",
            "07:00 PM",
            "08:00 PM",
            "09:00 PM",
            "10:00 PM"
        ];

        // Get unavailable slots
        $unavailableSlots = DB::table('unavailable_slots')
            ->where('user_id', $user->id)
            ->where('date', $date)
            ->pluck('time_slot')
            ->toArray();

        // Get booked slots
        $bookedSlots = DB::table('appointments')
            ->where('provider_id', $user->id)
            ->where('date', $date)
            ->pluck('time_slot')
            ->toArray();

        // Filter available slots
        $availableSlots = array_diff($allSlots, $unavailableSlots, $bookedSlots);

        return response()->json([
            'status' => true,
            'unavailable_slots' => $unavailableSlots,
            'booked_slots' => $bookedSlots,
            'available_slots' => array_values($availableSlots),
        ]);
    }
    public function getSlots(Request $request, $date)
    {
        // Fetch provider_id from request
        $providerId = $request->query('provider_id'); 

        if (!$providerId) {
            return response()->json(['status' => false, 'message' => 'Provider ID is required'], 400);
        }
    
        $allSlots = [
            "09:00 AM", "10:00 AM", "11:00 AM", "12:00 PM",
            "01:00 PM", "02:00 PM", "03:00 PM", "04:00 PM",
            "05:00 PM", "06:00 PM", "07:00 PM", "08:00 PM",
            "09:00 PM", "10:00 PM"
        ];
    
        // Fetch unavailable slots for provider
        $unavailableSlots = DB::table('unavailable_slots')
            ->where('user_id', $providerId)  // Fetching correct provider's unavailable slots
            ->where('date', $date)
            ->pluck('time_slot')
            ->toArray();
    
        // Fetch booked slots for provider
        $bookedSlots = DB::table('appointments')
            ->where('provider_id', $providerId)  // Fetching correct provider's booked slots
            ->where('date', $date)
            ->pluck('time_slot')
            ->toArray();
    
        // Compute available slots
        $availableSlots = array_diff($allSlots, $unavailableSlots, $bookedSlots);
    
        return response()->json([
            'status' => true,
            'provider_id' => $providerId,  // Debugging
            'unavailable_slots' => $unavailableSlots,
            'booked_slots' => $bookedSlots,
            'available_slots' => array_values($availableSlots),
        ]);
    }
}
