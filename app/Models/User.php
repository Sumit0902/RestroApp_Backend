<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Company;
use App\Models\TimeSheet;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Carbon\Carbon;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'firstname',
        'lastname',
        'email',
        'password',
        'phone',
        'role',
        'company_id',
        'company_role',
        'wage',
        'wage_rate',
        'avatar'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Calculate total hours worked in a given month.
     *
     * @param string $month Format: 'YYYY-MM'
     * @return array
     */
    public function hoursWorked($month)
    {
        [$year, $month] = explode('-', $month);

        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $endOfMonth = Carbon::now()->isSameMonth($startOfMonth) ? Carbon::today() : $startOfMonth->copy()->endOfMonth();
        
        $timesheets = TimeSheet::where('company_id', $this->company?->id)
            ->where('user_id', $this->id)
            ->whereBetween('check_in', [$startOfMonth, $endOfMonth])
            ->get();

        $leaves = Leave::where('user_id', $this->id)
            ->where('status', 'approved')
            ->where(function ($query) use ($startOfMonth, $endOfMonth) {
                $query->whereBetween('start_date', [$startOfMonth, $endOfMonth])
                    ->orWhereBetween('end_date', [$startOfMonth, $endOfMonth]);
            })
            ->get();

        $totalMinutes = 0;
        $otMinutes = 0;
        // if($timesheets) {
            foreach ($timesheets as $timesheet) {
                $checkIn = $timesheet->check_in;
                $checkOut = $timesheet->check_out ?? $checkIn->copy()->addHours(8);
    
                $minutesWorked = $checkIn->diffInMinutes($checkOut);
                $totalMinutes += $minutesWorked;
    
                if ($minutesWorked > 480) {
                    $otMinutes += $minutesWorked - 480;
                }
            }
        // }
            
        $holidays = $leaves->count();

        $hoursWorked = round($totalMinutes / 60, 2);
        $otHours = round($otMinutes / 60, 2);
        $hoursWorkedFormatted = sprintf('%02d:%02d', floor($totalMinutes / 60), $totalMinutes % 60);
        $otHoursFormatted = sprintf('%02d:%02d', floor($otMinutes / 60), $otMinutes % 60);

        return [
            'hoursWorked' => $hoursWorked,
            'otHours' => $otHours,
            'hoursWorkedFormatted' => $hoursWorkedFormatted,
            'otHoursFormatted' => $otHoursFormatted,
            'holidays' => $holidays,
        ];
    }


}
