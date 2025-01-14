<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_id',
        'week_number',
        'year',
        'days_schedule',
        'shift_id',
        'notes',
    ];

    /**
     * Get the user associated with the schedule.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the company associated with the schedule.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the shift associated with the schedule.
     */
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }
}
