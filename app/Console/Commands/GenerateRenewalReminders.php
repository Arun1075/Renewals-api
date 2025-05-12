<?php

namespace App\Console\Commands;

use App\Models\Renewal;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class GenerateRenewalReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-renewal-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate reminder logs for renewals approaching expiration date';

    /**
     * Default reminder days if not specified for a renewal
     * 
     * @var array
     */
    protected $defaultReminderDays = [1, 7, 30];

    /**
     * The notification service
     * 
     * @var NotificationService
     */
    protected $notificationService;

    /**
     * Create a new command instance.
     *
     * @param NotificationService $notificationService
     * @return void
     */
    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for renewals that need reminders...');
        
        $processedCount = 0;
        
        // Get all active renewals
        $renewals = Renewal::where('status', '!=', 'cancelled')
            ->whereDate('end_date', '>', now())
            ->get();
            
        foreach ($renewals as $renewal) {
            $daysUntilExpiry = Carbon::now()->startOfDay()->diffInDays($renewal->end_date, false);
            
            // Use the renewal's reminder_days_before or default settings
            $reminderDays = $renewal->reminder_days_before ? [$renewal->reminder_days_before] : $this->defaultReminderDays;
            
            foreach ($reminderDays as $days) {
                // If today is exactly X days before expiry
                if ($daysUntilExpiry === $days) {
                    $reminderLogs = $this->notificationService->sendRenewalReminders($renewal, $days);
                    $this->info("Created " . count($reminderLogs) . " reminder logs for renewal ID: {$renewal->id}");
                    $processedCount++;
                }
            }
        }
        
        $this->info("Generated reminders for {$processedCount} renewals.");
    }
}
