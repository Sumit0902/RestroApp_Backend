<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'payroll';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'employee_id',
        'basic_salary',
        'bonus',
        'deduction',
        'overtime_hours',
        'overtime_pay',
        'total_salary',
        'payslip_url',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'basic_salary' => 'decimal:2',
        'bonus' => 'decimal:2',
        'deduction' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'overtime_pay' => 'decimal:2',
        'total_salary' => 'decimal:2',
    ];

    /**
     * Get the employee associated with this payroll.
     */
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}
