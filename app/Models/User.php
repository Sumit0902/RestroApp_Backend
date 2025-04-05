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
        $endOfMonth = $startOfMonth->copy()->endOfMonth();
        $today = Carbon::today();
        
        if ($startOfMonth->isSameMonth($today)) {
            $endOfMonth = $today;
        }
        $company = $this->company;

        $timesheets = TimeSheet::where('company_id', $company->id)->where('user_id', $this->id)
            ->whereBetween('check_in', [$startOfMonth, $endOfMonth])
            ->get();

        $leaves = Leave::where('user_id', $this->id)
            ->whereBetween('start_date', [$startOfMonth, $endOfMonth])
            ->orWhereBetween('end_date', [$startOfMonth, $endOfMonth])
            ->get();
        
        $operationalDays = explode(',', $company->workingDays);

       
        $totalMinutes = 0;
        $otMinutes = 0;
        $holidays = 0;
        $ddd = array();
        for ($day = 1; $day <= $endOfMonth->day; $day++) {
            $currentDate = Carbon::create($year, $month, $day);
            $dayOfWeek = $currentDate->dayOfWeek;

            if (!in_array((string) $dayOfWeek, $operationalDays)) {
                  $ddd[$day] = [$dayOfWeek , $operationalDays];
                continue; // Skip non-operational days
            }

            $timesheet = $timesheets->first(function ($ts) use ($currentDate) {
                return $ts->check_in->between($currentDate->startOfDay(), $currentDate->endOfDay()) && ($ts->check_out ? $ts->check_out->between($currentDate->startOfDay(), $currentDate->endOfDay()) : true);
            });

            
            $timesheet = $timesheet ?? false;

            if ($timesheet) {
                $checkIn = $timesheet->check_in;
                $checkOut = $timesheet->check_out ?? $checkIn->copy()->addHours(8); // Assume 8 hours if no check-out

                $minutesWorked = $checkIn->diffInMinutes($checkOut);
                $totalMinutes += $minutesWorked;

                if ($minutesWorked > 480) { // 8 hours in minutes
                    $otMinutes += $minutesWorked - 480;
                }
                $ddd[$day] = $minutesWorked;
            } else {
                $leave = $leaves->filter(function ($l) use ($currentDate) {
                    return Carbon::parse($l->start_date)->lte($currentDate) &&
                           Carbon::parse($l->end_date)->gte($currentDate);
                })->first();

                $ddd[$day] = $leave;
            }
        }

        return [
            'hoursWorked' => round($totalMinutes / 60, 2),
            'otHours' => round($otMinutes / 60, 2),
            'holidays' => $holidays,
        ];
    }
}
