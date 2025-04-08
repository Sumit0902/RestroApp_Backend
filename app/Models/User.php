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

        // Fetch timesheets for the user within the month
        $timesheets = TimeSheet::where('company_id', $this->company->id)
            ->where('user_id', $this->id)
            ->whereBetween('check_in', [$startOfMonth, $endOfMonth])
            ->get();

        // Fetch leaves for the user within the month
        $leaves = Leave::where('user_id', $this->id)
            ->where(function ($query) use ($startOfMonth, $endOfMonth) {
                $query->whereBetween('start_date', [$startOfMonth, $endOfMonth])
                      ->orWhereBetween('end_date', [$startOfMonth, $endOfMonth]);
            })
            ->get();

        $totalMinutes = 0;
        $otMinutes = 0;

        // Calculate total minutes worked and overtime minutes
        foreach ($timesheets as $timesheet) {
            $checkIn = $timesheet->check_in;
            $checkOut = $timesheet->check_out ?? $checkIn->copy()->addHours(8); // Default to 8 hours if no check-out

            $minutesWorked = $checkIn->diffInMinutes($checkOut);
            $totalMinutes += $minutesWorked;

            if ($minutesWorked > 480) { // 8 hours = 480 minutes
                $otMinutes += $minutesWorked - 480;
            }
        }

        // Count the number of leave days (holidays)
        $holidays = 0;
        foreach ($leaves as $leave) {
            $leaveStart = Carbon::parse($leave->start_date)->startOfDay();
            $leaveEnd = Carbon::parse($leave->end_date)->endOfDay();

            // Ensure the leave days are within the month range
            $leaveStart = $leaveStart->lt($startOfMonth) ? $startOfMonth : $leaveStart;
            $leaveEnd = $leaveEnd->gt($endOfMonth) ? $endOfMonth : $leaveEnd;

            $holidays += $leaveStart->diffInDays($leaveEnd) + 1; // Add 1 to include the start day
        }

        // Convert total minutes to hours and minutes
        $hoursWorked = round($totalMinutes / 60, 2); // Total hours in decimal format
        $otHours = round($otMinutes / 60, 2); // Overtime hours in decimal format

        // Format total minutes as HH:MM
        $hoursWorkedFormatted = sprintf('%02d:%02d', floor($totalMinutes / 60), $totalMinutes % 60); // Format as HH:MM
        $otHoursFormatted = sprintf('%02d:%02d', floor($otMinutes / 60), $otMinutes % 60); // Format as HH:MM

        return [
            'hoursWorked' => $hoursWorked, // Total hours in decimal format
            'otHours' => $otHours, // Overtime hours in decimal format
            'hoursWorkedFormatted' => $hoursWorkedFormatted, // Total hours formatted as HH:MM
            'otHoursFormatted' => $otHoursFormatted, // Overtime hours formatted as HH:MM
            'holidays' => $holidays, // Total leave days
        ];
    }

}
