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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('company_name');
            $table->string('company_about');
            $table->string('company_address1');
            $table->string('company_address2')->nullable();
            $table->string('company_city');
            $table->string('company_state');
            $table->string('company_zip');
            $table->string('phone')->nullable();
            $table->string('logo')->nullable();
            $table->string('email');
            $table->string('workingDays')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
