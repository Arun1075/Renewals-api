<?php

namespace App\Console\Commands;

use App\Models\Renewal;
use App\Models\ReminderLog;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CheckRenewalReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'renewals:check-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for renewals that need reminders based on their reminder_days_before setting';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for renewals that need reminders...');
        
        $today = Carbon::now()->toDateString();
        $count = 0;
        
        // Find renewals where today is exactly reminder_days_before days before the end_date
        Renewal::whereRaw("DATE_SUB(end_date, INTERVAL reminder_days_before DAY) = ?", [$today])
            ->where('status', 'active')
            ->get()
            ->each(function ($renewal) use (&$count) {
                // In a real application, you would send an email/SMS/notification here
                $this->info("Sending reminder for: {$renewal->item_name} (ID: {$renewal->id}) - Due on: {$renewal->end_date}");
                
                // Log the reminder
                ReminderLog::create([
                    'renewal_id' => $renewal->id,
                    'type' => 'email', // Could be configurable per user
                    'sent_at' => now(),
                    'delivered' => true,
                    'notes' => "Automatic reminder sent {$renewal->reminder_days_before} days before expiration"
                ]);
                
                $count++;
            });
            
        $this->info("Reminder process complete. {$count} reminders were sent.");
        
        return Command::SUCCESS;
    }
}
