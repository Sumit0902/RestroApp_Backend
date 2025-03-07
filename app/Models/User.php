<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Company;
use App\Models\TimeSheet;
use Laravel\Sanctum\HasApiTokens;
use Carbon\Carbon;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

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
     * @return float
     */
    public function hoursWorked($month)
    {
        [$year, $month] = explode('-', $month);

        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        $timesheets = TimeSheet::where('user_id', $this->id)
            ->whereBetween('check_in', [$startOfMonth, $endOfMonth])
            ->get();

        $totalHours = 0;

        foreach ($timesheets as $timesheet) {
            if ($timesheet->check_in && $timesheet->check_out) {
                $totalHours += $timesheet->check_in->diffInHours($timesheet->check_out);
            }
        }

        return $totalHours;
    }
}
