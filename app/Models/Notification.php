<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'message', 'notifier_id', 'recipient_id', 'is_read', 'company_id'
    ];

    // Define relationships if necessary
    public function notifier()
    {
        return $this->belongsTo(User::class, 'notifier_id');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }
}
