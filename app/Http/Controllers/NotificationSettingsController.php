<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class NotificationSettingsController extends Controller
{
    /**
     * Get the current user's notification settings.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSettings()
    {
        $user = Auth::user();
        
        return response()->json([
            'data' => [
                'email_enabled' => $user->email_enabled,
                'sms_enabled' => $user->sms_enabled,
                'in_app_enabled' => $user->in_app_enabled,
                'phone_number' => $user->phone_number,
                'email' => $user->email
            ],
            'message' => 'Notification settings retrieved successfully'
        ]);
    }
    
    /**
     * Update the current user's notification settings.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateSettings(Request $request)
    {
        $user = Auth::user();
        
        try {
            $validated = $request->validate([
                'email_enabled' => 'boolean',
                'sms_enabled' => 'boolean',
                'in_app_enabled' => 'boolean',
                'phone_number' => 'nullable|string|max:20'
            ]);
            
            // If SMS is being enabled, make sure the phone number is provided
            if (isset($validated['sms_enabled']) && $validated['sms_enabled'] && 
                (!isset($validated['phone_number']) && !$user->phone_number)) {
                return response()->json([
                    'message' => 'Phone number is required when SMS notifications are enabled',
                    'errors' => ['phone_number' => ['Phone number is required']]
                ], 422);
            }
            
            $user->update($validated);
            
            return response()->json([
                'data' => [
                    'email_enabled' => $user->email_enabled,
                    'sms_enabled' => $user->sms_enabled,
                    'in_app_enabled' => $user->in_app_enabled,
                    'phone_number' => $user->phone_number,
                    'email' => $user->email
                ],
                'message' => 'Notification settings updated successfully'
            ]);
            
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }
    }
}
