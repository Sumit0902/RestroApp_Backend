<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Shift;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'company_about',
        'company_address1',
        'company_address2',
        'company_city',
        'company_state',
        'company_zip',
        'phone',
        'email',
        'logo',
        'ot_type',
        'ot_rate',
        'workingDays',
    ];

    public function employees() {
        return $this->hasMany(User::class)
                    ->where('role', 'employee');
    }

    public function managers() {
        return $this->hasMany(User::class)
                    ->where('role', 'manager');
    }

    public function shifts() {
        return $this->hasMany(Shift::class);
    }
}
