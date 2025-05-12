<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RenewalLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'renewal_id',
        'action',
        'date',
        'created_by',
        'notes'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'datetime',
    ];

    /**
     * Get the renewal that this log belongs to
     */
    public function renewal()
    {
        return $this->belongsTo(Renewal::class);
    }

    /**
     * Get the user who created this log
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
