<?php

namespace App\Console\Commands;

use App\Models\Renewal;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class TestReminderSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-reminder-system 
                            {renewal_id? : The ID of the renewal to test with}
                            {--days=7 : Days before expiry to simulate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the reminder system by generating test reminders for a renewal';

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
        $renewalId = $this->argument('renewal_id');
        $daysBeforeExpiry = $this->option('days');
        
        // If no renewal ID provided, list available renewals and let user select one
        if (!$renewalId) {
            $this->info('No renewal ID provided. Listing available renewals:');
            $renewals = Renewal::where('status', '!=', 'cancelled')
                ->whereDate('end_date', '>', now())
                ->get(['id', 'user_id', 'item_name', 'end_date']);
                
            if ($renewals->isEmpty()) {
                $this->error('No active renewals found in the system.');
                return 1;
            }
            
            $this->table(
                ['ID', 'User ID', 'Item Name', 'End Date'],
                $renewals->map(function ($renewal) {
                    return [
                        'id' => $renewal->id,
                        'user_id' => $renewal->user_id,
                        'item_name' => $renewal->item_name,
                        'end_date' => $renewal->end_date->format('Y-m-d')
                    ];
                })
            );
            
            $renewalId = $this->ask('Enter the ID of the renewal to test with:');
        }
        
        // Try to find the renewal
        try {
            $renewal = Renewal::findOrFail($renewalId);
        } catch (\Exception $e) {
            $this->error("Renewal with ID {$renewalId} not found.");
            return 1;
        }
        
        $this->info("Testing reminder system for renewal: {$renewal->item_name} (ID: {$renewal->id})");
        $this->info("Simulating notification {$daysBeforeExpiry} days before expiry.");
        
        // Send test reminders
        $reminderLogs = $this->notificationService->sendRenewalReminders($renewal, $daysBeforeExpiry);
        
        // Display the results
        $this->info("Created " . count($reminderLogs) . " reminder logs:");
        
        $this->table(
            ['ID', 'Type', 'Sent At', 'Delivered', 'Notes'],
            collect($reminderLogs)->map(function ($log) {
                return [
                    'id' => $log->id,
                    'type' => $log->type,
                    'sent_at' => $log->sent_at->format('Y-m-d H:i:s'),
                    'delivered' => $log->delivered ? 'Yes' : 'No',
                    'notes' => substr($log->notes, 0, 50) . (strlen($log->notes) > 50 ? '...' : '')
                ];
            })
        );
        
        $this->info("Reminder test completed successfully.");
        return 0;
    }
}
