<?php

namespace App\Http\Controllers;

use App\Models\ReminderLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReminderController extends Controller
{
    /**
     * Display a listing of reminder logs.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        // Get reminder logs for the current user's renewals
        $logs = ReminderLog::with('renewal')
            ->whereHas('renewal', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->orderBy('sent_at', 'desc')
            ->get();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Reminder logs retrieved successfully',
            'data' => $logs
        ]);
    }

    /**
     * Store a newly created reminder log.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'renewal_id' => 'required|exists:renewals,id',
            'type' => 'required|in:email,sms,in-app',
            'delivered' => 'boolean',
            'notes' => 'nullable|string'
        ]);
        
        // Ensure the renewal belongs to the authenticated user
        $renewal = \App\Models\Renewal::where('id', $validated['renewal_id'])
            ->where('user_id', auth()->id())
            ->first();
        
        if (!$renewal) {
            return response()->json([
                'status' => 'error',
                'message' => 'Renewal not found or does not belong to you'
            ], 404);
        }
        
        // Create the reminder log
        $reminderLog = ReminderLog::create([
            'renewal_id' => $validated['renewal_id'],
            'type' => $validated['type'],
            'sent_at' => now(),
            'delivered' => $validated['delivered'] ?? true,
            'notes' => $validated['notes'] ?? null
        ]);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Reminder log created successfully',
            'data' => $reminderLog
        ], 201);
    }
}
