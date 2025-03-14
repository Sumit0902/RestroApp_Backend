<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payroll', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id'); // Relation to users/employees table
            $table->decimal('basic_salary', 10, 2);
            $table->decimal('bonus', 10, 2)->nullable();
            $table->decimal('deduction', 10, 2)->nullable();
            $table->decimal('overtime_hours', 10, 2)->nullable();
            $table->decimal('overtime_pay', 10, 2)->nullable();
            $table->decimal('total_salary', 10, 2);
            $table->string('payslip_url')->nullable(); // For payslip generation URL
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('employee_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll');
    }
};
