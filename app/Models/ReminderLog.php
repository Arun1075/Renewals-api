<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReminderLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'renewal_id',
        'type',
        'sent_at',
        'delivered',
        'notes'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'sent_at' => 'datetime',
        'delivered' => 'boolean',
    ];

    /**
     * Get the renewal that this reminder belongs to
     */
    public function renewal()
    {
        return $this->belongsTo(Renewal::class);
    }
}
