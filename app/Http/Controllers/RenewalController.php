<?php

namespace App\Http\Controllers;

use App\Models\Renewal;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class RenewalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $renewals = Renewal::all();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Renewals retrieved successfully',
            'data' => $renewals
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'service_name' => 'required|string|max:255',
            'service_type' => 'required|string|max:255',
            'provider' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'cost' => 'required|numeric|min:0',
            'reminder_type' => 'required|string|max:255',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $renewal = Renewal::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Renewal created successfully',
            'data' => $renewal
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $renewal = Renewal::find($id);

        if (!$renewal) {
            return response()->json([
                'status' => 'error',
                'message' => 'Renewal not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Renewal retrieved successfully',
            'data' => $renewal
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $renewal = Renewal::find($id);

        if (!$renewal) {
            return response()->json([
                'status' => 'error',
                'message' => 'Renewal not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'service_name' => 'sometimes|required|string|max:255',
            'service_type' => 'sometimes|required|string|max:255',
            'provider' => 'sometimes|required|string|max:255',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after_or_equal:start_date',
            'cost' => 'sometimes|required|numeric|min:0',
            'reminder_type' => 'sometimes|required|string|max:255',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $renewal->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Renewal updated successfully',
            'data' => $renewal
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $renewal = Renewal::find($id);

        if (!$renewal) {
            return response()->json([
                'status' => 'error',
                'message' => 'Renewal not found'
            ], 404);
        }

        $renewal->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Renewal deleted successfully'
        ]);
    }

    /**
     * Get renewal statistics
     * 
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        // Get current date
        $today = Carbon::now()->startOfDay();
        
        // Consider renewals expiring in the next 30 days as "expiring soon"
        $expiringThreshold = Carbon::now()->addDays(30)->endOfDay();
        
        // Count active renewals (end date >= today)
        $active = Renewal::where('end_date', '>=', $today)->count();
        
        // Count renewals expiring soon (end date between today and threshold)
        $expiringSoon = Renewal::where('end_date', '>=', $today)
            ->where('end_date', '<=', $expiringThreshold)
            ->count();
        
        // Count expired renewals (end date < today)
        $expired = Renewal::where('end_date', '<', $today)->count();
        
        // Get total number of renewals
        $total = Renewal::count();
        
        // Calculate total cost of all renewals
        $totalCost = (int) Renewal::sum('cost');
        
        return response()->json([
            'status' => 'success',
            'message' => 'Renewal statistics retrieved successfully',
            'data' => [
                'active' => $active,
                'expiringSoon' => $expiringSoon,
                'expired' => $expired,
                'total' => $total,
                'totalCost' => $totalCost
            ]
        ]);
    }

    /**
     * Get renewals by status
     * 
     * @param string $status
     * @return JsonResponse
     */
    public function getByStatus(string $status): JsonResponse
    {
        // Validate the status parameter
        if (!in_array($status, ['active', 'expired', 'cancelled'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid status. Allowed values: active, expired, cancelled'
            ], 400);
        }

        // Get renewals with the specified status
        $renewals = Renewal::where('status', $status)->get();

        return response()->json([
            'status' => 'success',
            'message' => "Renewals with status '$status' retrieved successfully",
            'data' => $renewals
        ]);
    }

    /**
     * Get renewals for the authenticated user
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getUserRenewals(Request $request): JsonResponse
    {
        // Get the currently authenticated user
        $user = $request->user();
        
        // Get renewals belonging to this user
        $renewals = Renewal::where('user_id', $user->id)->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Your renewals retrieved successfully',
            'data' => $renewals
        ]);
    }

    public function getUserDetails()
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        return response()->json([
            'success' => true
        ]);
    }
}
