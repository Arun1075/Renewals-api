<?php

namespace App\Http\Controllers;

use App\Models\Renewal;
use App\Models\RenewalLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RenewalLogController extends Controller
{
    /**
     * Display a listing of logs for a specific renewal.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function index(string $id): JsonResponse
    {
        // Check if the renewal exists and belongs to the authenticated user
        $renewal = Renewal::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();
            
        if (!$renewal) {
            return response()->json([
                'status' => 'error',
                'message' => 'Renewal not found'
            ], 404);
        }
        
        // Get the logs for this renewal
        $logs = RenewalLog::where('renewal_id', $id)
            ->orderBy('date', 'desc')
            ->get();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Renewal logs retrieved successfully',
            'data' => $logs
        ]);
    }

    /**
     * Store a newly created log entry for a renewal.
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function store(Request $request, string $id): JsonResponse
    {
        // Check if the renewal exists and belongs to the authenticated user
        $renewal = Renewal::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();
            
        if (!$renewal) {
            return response()->json([
                'status' => 'error',
                'message' => 'Renewal not found'
            ], 404);
        }
        
        $validated = $request->validate([
            'action' => 'required|string',
            'notes' => 'nullable|string'
        ]);
        
        // Create the log entry
        $log = RenewalLog::create([
            'renewal_id' => $id,
            'action' => $validated['action'],
            'date' => now(),
            'created_by' => auth()->id(),
            'notes' => $validated['notes'] ?? null
        ]);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Log entry created successfully',
            'data' => $log
        ], 201);
    }
}
