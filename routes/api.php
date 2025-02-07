<?php

// use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TimeSheetController;
use App\Http\Controllers\ShiftController;
use App\Http\Middleware\CheckAuthToken;
use Illuminate\Http\Request;
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

Route::post('login',[AuthController::class,'login'])->name('companies.all');
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
        Route::get('{companyId}/employees', [CompanyController::class, 'companyEmployees'])->name('companies.employees.index');
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
            Route::patch('{scheduleId}/update', [ScheduleController::class, 'update'])->name('companies.schedules.update');
            Route::delete('{scheduleId}/delete', [ScheduleController::class, 'destroy'])->name('companies.schedules.delete');
        });


        Route::group(['prefix' => '{companyId}/emp_notificataions'], function () {
            Route::get('{employeeId}', [NotificationController::class, 'getUserNotifications'])->name('companies.user_notificataions'); 
        });
        Route::group(['prefix' => '{companyId}/mgr_notificataions'], function () {
            Route::get('{employeeId}', [NotificationController::class, 'getManagerNotifications'])->name('companies.mgr_notificataions'); 
        });

        Route::post('notification/{notifId}/mark_read', [NotificationController::class, 'markAsRead'])->name('companies.markAsRead'); 



        Route::group(['prefix' => '{companyId}/tasks'], function () {
            Route::get('/', [TaskController::class, 'index']);
            Route::post('/add', [TaskController::class, 'store']);
            Route::get('{taskid}', [TaskController::class, 'show']);
            Route::put('{taskid}/update', [TaskController::class, 'update']);
            Route::delete('{taskid}/delete', [TaskController::class, 'destroy']);
        });

     
        Route::group(['prefix' => '{companyId}/timesheets'], function () {
            Route::post('/', [TimeSheetController::class, 'index']); // List entries
            Route::post('/mytimesheet/{employeeId}', [TimeSheetController::class, 'getTimesheetForEmployee']); // List entries
            Route::post('/check-in', [TimeSheetController::class, 'checkIn']); // Check-in
            Route::post('/check-out', [TimeSheetController::class, 'checkOut']); // Check-out
            Route::post('/reset-check-out/{id}', [TimeSheetController::class, 'resetCheckOut']); // Reset check-out
            Route::get('/{id}', [TimeSheetController::class, 'show']); // Fetch single entry
        });

     
      
    
    });



    // Employee routes
    Route::get('employee/{employeeId}', [UserController::class, 'show'])->name('employee.show');
    Route::post('employee/add', [UserController::class, 'store'])->name('employee.store');
    Route::get('employee/update', [UserController::class, 'update'])->name('employee.update');
    // deparmtents routes
    // Route::get('department/{department}', [DepartmentController::class, 'show'])->name('department.show');
    
    // Schedule routes with shifts
    Route::resource('schedules', ScheduleController::class);
    
    // Leave  routes for future 
    // Route::resource('leaves', LeaveController::class);
    // Report routes for future 
    // Route::get('reports', [ReportController::class, 'index']);
});
