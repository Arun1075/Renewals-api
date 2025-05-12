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
    public function index(Request $request): JsonResponse
    {
        $renewals = Renewal::where('user_id', auth()->id())
            ->when($request->category, fn($q) => $q->where('category', $request->category))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->due_date, fn($q) => $q->whereDate('end_date', '<=', $request->due_date))
            ->get();
        
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
            'item_name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'vendor' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'reminder_days_before' => 'required|integer|min:1',
            'notes' => 'nullable|string',
            'cost' => 'nullable|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();
        $data['user_id'] = auth()->id();
        $data['status'] = 'active';

        $renewal = Renewal::create($data);

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
        $renewal = Renewal::where('user_id', auth()->id())->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'item_name' => 'sometimes|string|max:255',
            'category' => 'sometimes|string|max:255',
            'vendor' => 'nullable|string|max:255',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
            'reminder_days_before' => 'sometimes|integer|min:1',
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

    /**
     * Update the status of a renewal.
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $renewal = Renewal::where('user_id', auth()->id())->find($id);

        if (!$renewal) {
            return response()->json([
                'status' => 'error',
                'message' => 'Renewal not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,renewed,inactive,cancelled',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $renewal->update($request->only('status', 'start_date', 'end_date'));
        
        // Log the status change
        \App\Models\RenewalLog::create([
            'renewal_id' => $renewal->id,
            'action' => 'Status updated to ' . $request->status,
            'date' => now(),
            'created_by' => auth()->id()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Renewal status updated successfully',
            'data' => $renewal
        ]);
    }
}
