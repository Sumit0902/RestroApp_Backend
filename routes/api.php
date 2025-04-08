<?php

use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TimeSheetController;
use App\Http\Controllers\ShiftController;
use App\Http\Middleware\CheckAuthToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ScheduleController;
// use App\Http\Controllers\LeaveManagementController as LeaveController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AuthController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post('login',[AuthController::class,'login'])->name('authlogin');
Route::post('two-factor-challenge', [AuthController::class, 'verifyTwoFactor']);
Route::post('register-company',[CompanyController::class,'companyRegistration'])->name('companyRegistration');

Route::post('/reset-password-request', [AuthController::class, 'sendForgetPasswordMail'])->name('password.sendResetLink');
Route::post('/reset-password', [AuthController::class, 'verifyToken'])->name('password.resetpassword'); 

Route::post('logout',[AuthController::class,'logout'])
->middleware('auth:sanctum');

 

Route::get('/', function (Request $request) { 
    return view('welcome');
});


Route::middleware(['auth:sanctum', CheckAuthToken::class])->group(function () {
    // Company routes
    Route::group(['prefix' => 'companies'], function () {
        Route::get('/', [CompanyController::class, 'index'])->name('companies.all');
        Route::post('add', [CompanyController::class, 'store'])->name('companies.create');
        Route::post('{companyId}/update', [CompanyController::class, 'update'])->name('companies.update');
        Route::get('{companyId}', [CompanyController::class, 'show'])->name('companies.show');
        // Route::get('{companyId}/employees', [CompanyController::class, 'companyEmployees'])->name('companies.employees.index');
        // Route::get('{companyId}/departments', [DepartmentController::class, 'index'])->name('companies.departments.index');
        Route::group(['prefix' => '{companyId}/shifts'], function () {
            Route::get('/', [ShiftController::class, 'index'])->name('companies.shifts.index');
            Route::post('add', [ShiftController::class, 'store'])->name('companies.shifts.create');
            Route::get('{shiftId}', [ShiftController::class, 'show'])->name('companies.shifts.show');
            Route::patch('{shiftId}/update', [ShiftController::class, 'update'])->name('companies.shifts.update');
            Route::delete('{shiftId}/delete', [ShiftController::class, 'destroy'])->name('companies.shifts.delete');
        });
        Route::group(['prefix' => '{companyId}/schedules'], function () {
            Route::post('/', [ScheduleController::class, 'index'])->name('companies.schedules.index');
            Route::post('add', [ScheduleController::class, 'store'])->name('companies.schedules.create');
            Route::get('{scheduleId}', [ScheduleController::class, 'getSchedule'])->name('companies.schedules.show');
            Route::post('{employeeId}', [ScheduleController::class, 'getEmployeeSchedule'])->name('companies.schedules.employeeSchedule');
            Route::patch('{scheduleId}/update', [ScheduleController::class, 'update'])->name('companies.schedules.update');
            Route::delete('{scheduleId}/delete', [ScheduleController::class, 'destroy'])->name('companies.schedules.delete');
        });


        Route::group(['prefix' => '{companyId}/employees'], function () {
            Route::get('/', [EmployeeController::class, 'index'])->name('companies.employees.index');
            Route::post('add', [EmployeeController::class, 'store'])->name('companies.employees.create');
            Route::get('{employeeId}', [EmployeeController::class, 'show'])->name('companies.employees.show');
            Route::patch('{employeeId}/update', [EmployeeController::class, 'update'])->name('companies.employees.update');
            Route::post('{employeeId}/updateMyProfile', [EmployeeController::class, 'updateMyProfile'])->name('companies.employees.updateMyProfile');
            Route::post('{employeeId}/enable-2fa', [UserController::class, 'enableTwoFactor'])->name('companies.employees.enable2fa');
            Route::post('{employeeId}/disable-2fa', [UserController::class, 'disableTwoFactor'])->name('companies.employees.disable2fa');
            Route::post('{employeeId}/confirm-2fa', [UserController::class, 'confirmTwoFactor'])->name('companies.employees.confirm2Fa');
            Route::delete('{employeeId}/delete', [EmployeeController::class, 'destroy'])->name('companies.employees.delete');
        });



        Route::group(['prefix' => '{companyId}/tasks'], function () {
            Route::get('/', [TaskController::class, 'index'])->name('companies.tasks.index');
            Route::post('/add', [TaskController::class, 'store']);
            Route::get('{taskid}', [TaskController::class, 'show']);
            Route::get('{employeeId}', [TaskController::class, 'getEmployeeTasks']);
            Route::post('{employeeId}/weekly', [TaskController::class, 'getEmployeeWeeklyTasks']);
            Route::post('weekly', [TaskController::class, 'getCompanyWeeklyTasks']);
            Route::put('{taskid}/update', [TaskController::class, 'update']);
            Route::delete('{taskid}/delete', [TaskController::class, 'destroy']);
        });

     
        Route::group(['prefix' => '{companyId}/timesheets'], function () {
            Route::post('/', [TimeSheetController::class, 'index']); // List entries
            Route::post('/mytimesheet', [TimeSheetController::class, 'getTimesheetForEmployee']); // List entries
            Route::post('/check-in', [TimeSheetController::class, 'checkIn']); // Check-in
            Route::post('/check-out', [TimeSheetController::class, 'checkOut']); // Check-out
            Route::post('/reset-check-out/{id}', [TimeSheetController::class, 'resetCheckOut']); // Reset check-out
            Route::get('/{id}', [TimeSheetController::class, 'show']); // Fetch single entry
        });


        Route::group(['prefix' => '{companyId}/leave-management'], function () {
            Route::get('/', [LeaveController::class, 'index']);
            Route::get('myleaves/{employeeId}', [LeaveController::class, 'UserLeaves']);
            Route::post('myleaves/{employeeId}/request', [LeaveController::class, 'store']);
            Route::get('{leave_id}', [LeaveController::class, 'show']);
            Route::put('{leave_id}/approve', [LeaveController::class, 'approve']);
            Route::put('{leave_id}/reject', [LeaveController::class, 'reject']);
            Route::put('{leave_id}/cancel', [LeaveController::class, 'cancel']);
            Route::delete('{leave_id}/delete', [LeaveController::class, 'destroy']);
        });
     
        Route::group(['prefix' => '{companyId}/payroll'], function () {
            Route::post('/', [PayrollController::class, 'index']); // List entries
            Route::post('/generate/{employeeId}', [PayrollController::class, 'generatePayroll']); // Fetch single entry
            Route::get('/{employeeId}', [PayrollController::class, 'payrollByUserId']); // Fetch single entry
        });

        
        Route::group(['prefix' => '{companyId}/emp_notificataions'], function () {
            Route::get('{employeeId}', [NotificationController::class, 'getUserNotifications'])->name('companies.user_notificataions'); 
        });
        Route::group(['prefix' => '{companyId}/mgr_notificataions'], function () {
            Route::get('{employeeId}', [NotificationController::class, 'getManagerNotifications'])->name('companies.mgr_notificataions'); 
        });

        Route::post('notification/{notifId}/mark_read', [NotificationController::class, 'markAsRead'])->name('companies.markAsRead'); 



    
    });



    // Employee routes
    // Route::get('employee/{employeeId}', [UserController::class, 'show'])->name('employee.show');
    // Route::post('employee/add', [UserController::class, 'store'])->name('employee.store');
    // Route::get('employee/update', [UserController::class, 'update'])->name('employee.update');
    // deparmtents routes
    // Route::get('department/{department}', [DepartmentController::class, 'show'])->name('department.show');
    
    // Schedule routes with shifts
    // Route::resource('schedules', ScheduleController::class);
    
    // Leave  routes for future 
    // Route::resource('leaves', LeaveController::class);
    // Report routes for future 
    // Route::get('reports', [ReportController::class, 'index']);
});
