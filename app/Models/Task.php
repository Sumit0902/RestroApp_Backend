// app/Models/Task.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_id',
        'name',
        'description',
        'is_recurring',
        'weekdays',
    ];

    protected $casts = [
        'weekdays' => 'array', // Automatically cast JSON to array and vice versa
        'is_recurring' => 'boolean',
    ];
}
