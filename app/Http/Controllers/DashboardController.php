<?php

namespace App\Http\Controllers;

use App\Models\Renewal;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    /**
     * Get summary statistics for the dashboard.
     *
     * @return JsonResponse
     */
    public function summary(): JsonResponse
    {
        $userId = auth()->id();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Dashboard summary retrieved successfully',
            'data' => [
                'upcoming_7_days' => Renewal::where('user_id', $userId)
                    ->whereBetween('end_date', [now(), now()->addDays(7)])
                    ->count(),
                'upcoming_30_days' => Renewal::where('user_id', $userId)
                    ->whereBetween('end_date', [now(), now()->addDays(30)])
                    ->count(),
                'overdue' => Renewal::where('user_id', $userId)
                    ->where('end_date', '<', now())
                    ->where('status', '!=', 'renewed')
                    ->count(),
                'recently_renewed' => Renewal::where('user_id', $userId)
                    ->where('status', 'renewed')
                    ->where('updated_at', '>=', now()->subDays(30))
                    ->count(),
            ]
        ]);
    }

    /**
     * Get quick links data for the dashboard.
     *
     * @return JsonResponse
     */
    public function quickLinks(): JsonResponse
    {
        $userId = auth()->id();
        
        $items = Renewal::where('user_id', $userId)
            ->where(function($query) {
                $query->where('end_date', '<=', now()->addDays(7))
                      ->orWhere(function($q) {
                          $q->where('end_date', '<', now())
                            ->where('status', '!=', 'renewed');
                      });
            })
            ->select(['id', 'item_name', 'end_date', 'status'])
            ->orderBy('end_date')
            ->get();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Quick links retrieved successfully',
            'data' => $items
        ]);
    }
}
