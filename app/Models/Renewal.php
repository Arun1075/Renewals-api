<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Renewal extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'item_name',
        'category',
        'vendor',
        'start_date',
        'end_date',
        'reminder_days_before',
        'status',
        'notes',
        'cost',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'cost' => 'decimal:2',
        'reminder_days_before' => 'integer',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        // Log when a renewal is created
        static::created(function ($renewal) {
            $renewal->logs()->create([
                'action' => 'created',
                'date' => now(),
                'created_by' => Auth::id() ?? null,
                'notes' => 'Renewal created'
            ]);
        });

        // Log when a renewal is updated
        static::updated(function ($renewal) {
            $renewal->logs()->create([
                'action' => 'updated',
                'date' => now(),
                'created_by' => Auth::id() ?? null,
                'notes' => 'Renewal updated'
            ]);
        });

        // Log when a renewal is deleted
        static::deleted(function ($renewal) {
            $renewal->logs()->create([
                'action' => 'deleted',
                'date' => now(),
                'created_by' => Auth::id() ?? null,
                'notes' => 'Renewal deleted'
            ]);
        });
    }

    /**
     * Get the user that owns the renewal
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the logs for this renewal
     */
    public function logs()
    {
        return $this->hasMany(RenewalLog::class);
    }

    /**
     * Get the reminder logs for this renewal
     */
    public function reminderLogs()
    {
        return $this->hasMany(ReminderLog::class);
    }

    /**
     * Log a reminder event for this renewal
     * 
     * @param string $type The type of reminder
     * @param boolean $delivered Whether the reminder was delivered
     * @param string|null $notes Additional information about the reminder
     * @return ReminderLog
     */
    public function logReminder(string $type, bool $delivered = true, ?string $notes = null): ReminderLog
    {
        return $this->reminderLogs()->create([
            'type' => $type,
            'sent_at' => now(),
            'delivered' => $delivered,
            'notes' => $notes
        ]);
    }
}
