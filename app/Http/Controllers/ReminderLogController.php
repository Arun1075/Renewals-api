<?php

namespace App\Http\Controllers;

use App\Models\ReminderLog;
use App\Models\Renewal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReminderLogController extends Controller
{
    /**
     * Display a listing of reminder logs.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Start with a query for all reminder logs
        $query = ReminderLog::with('renewal');

        // For non-admin users, only show their reminder logs
        if (!Auth::user()->isAdmin()) {
            $query->whereHas('renewal', function ($q) {
                $q->where('user_id', Auth::id());
            });
        }

        // Filter by notification type if provided
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by delivery status if provided
        if ($request->has('delivered')) {
            $query->where('delivered', $request->boolean('delivered'));
        }

        // Filter by renewal if provided
        if ($request->has('renewal_id')) {
            $query->where('renewal_id', $request->renewal_id);
        }

        // Filter by date range if provided
        if ($request->has('from_date')) {
            $query->whereDate('sent_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->whereDate('sent_at', '<=', $request->to_date);
        }

        // Sort by sent_at date by default, newest first
        $query->orderBy('sent_at', 'desc');

        // Paginate the results
        $reminderLogs = $query->paginate($request->input('per_page', 15));

        return response()->json([
            'data' => $reminderLogs,
            'message' => 'Reminder logs retrieved successfully'
        ]);
    }

    /**
     * Display reminder logs for a specific renewal.
     *
     * @param Renewal $renewal
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function forRenewal(Renewal $renewal, Request $request)
    {
        // Check if user has permission to view this renewal's logs
        if (!Auth::user()->isAdmin() && $renewal->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Get reminder logs for this specific renewal
        $query = $renewal->reminderLogs();

        // Filter by notification type if provided
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by delivery status if provided
        if ($request->has('delivered')) {
            $query->where('delivered', $request->boolean('delivered'));
        }

        // Sort by sent_at date, newest first
        $query->orderBy('sent_at', 'desc');

        // Paginate the results
        $reminderLogs = $query->paginate($request->input('per_page', 15));

        return response()->json([
            'data' => $reminderLogs,
            'renewal' => $renewal->only(['id', 'item_name', 'end_date']),
            'message' => 'Reminder logs for renewal retrieved successfully'
        ]);
    }

    /**
     * Get statistics about reminder logs.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats()
    {
        $query = ReminderLog::query();

        // For non-admin users, only include their reminder logs
        if (!Auth::user()->isAdmin()) {
            $query->whereHas('renewal', function ($q) {
                $q->where('user_id', Auth::id());
            });
        }

        $stats = [
            'total' => $query->count(),
            'by_type' => [
                'email' => $query->clone()->where('type', 'email')->count(),
                'sms' => $query->clone()->where('type', 'sms')->count(),
                'in-app' => $query->clone()->where('type', 'in-app')->count(),
            ],
            'delivery_status' => [
                'delivered' => $query->clone()->where('delivered', true)->count(),
                'failed' => $query->clone()->where('delivered', false)->count(),
            ],
            'last_sent' => $query->clone()->max('sent_at'),
        ];

        return response()->json([
            'data' => $stats,
            'message' => 'Reminder log statistics retrieved successfully'
        ]);
    }
}
