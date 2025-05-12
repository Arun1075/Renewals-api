<?php

namespace App\Services;

use App\Models\Renewal;
use App\Models\ReminderLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * Send a notification for a renewal.
     *
     * @param Renewal $renewal
     * @param int $daysBeforeExpiry
     * @return array Array of created reminder logs
     */
    public function sendRenewalReminders(Renewal $renewal, int $daysBeforeExpiry): array
    {
        $user = $renewal->user;
        $reminderLogs = [];

        // Generate message content
        $messageContent = "Your {$renewal->item_name} service will expire in {$daysBeforeExpiry} days (on {$renewal->end_date->format('Y-m-d')}).";
        
        // Send email notification if enabled
        if ($user->email_enabled) {
            $success = $this->sendEmailNotification($user->email, $renewal, $messageContent);
            $reminderLogs[] = $renewal->logReminder('email', $success, "Email reminder: {$messageContent} Recipient: {$user->email}");
        }
        
        // Send SMS notification if enabled
        if ($user->sms_enabled && $user->phone_number) {
            $success = $this->sendSmsNotification($user->phone_number, $messageContent);
            $reminderLogs[] = $renewal->logReminder('sms', $success, "SMS reminder: {$messageContent} Recipient: {$user->phone_number}");
        }
        
        // Create in-app notification if enabled
        if ($user->in_app_enabled) {
            $success = $this->createInAppNotification($user, $messageContent);
            $reminderLogs[] = $renewal->logReminder('in-app', $success, "In-app notification: {$messageContent}");
        }
        
        return $reminderLogs;
    }
    
    /**
     * Send an email notification.
     *
     * @param string $email
     * @param Renewal $renewal
     * @param string $message
     * @return bool
     */
    private function sendEmailNotification(string $email, Renewal $renewal, string $message): bool
    {
        try {
            // This would be implemented with Laravel's Mail functionality
            // For now, just log it
            Log::info("Email notification would be sent to {$email} for renewal {$renewal->id}: {$message}");
            
            // In a real implementation:
            // Mail::to($email)->send(new RenewalReminderMail($renewal, $message));
            
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send email notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send an SMS notification.
     *
     * @param string $phoneNumber
     * @param string $message
     * @return bool
     */
    private function sendSmsNotification(string $phoneNumber, string $message): bool
    {
        try {
            // This would be implemented with an SMS service like Twilio
            // For now, just log it
            Log::info("SMS notification would be sent to {$phoneNumber}: {$message}");
            
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send SMS notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create an in-app notification.
     *
     * @param \App\Models\User $user
     * @param string $message
     * @return bool
     */
    private function createInAppNotification($user, string $message): bool
    {
        try {
            // This would be implemented with Laravel's notification system
            // For now, just log it
            Log::info("In-app notification would be created for user {$user->id}: {$message}");
            
            // In a real implementation:
            // $user->notify(new RenewalReminderNotification($message));
            
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to create in-app notification: " . $e->getMessage());
            return false;
        }
    }
}