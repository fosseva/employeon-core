<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('employeon.attendance.table', 'attendance_entries'), function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->index();
            $table->date('attendance_date');
            $table->string('status')->default('present')->index();
            $table->dateTime('check_in_at')->nullable();
            $table->dateTime('check_out_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'attendance_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('employeon.attendance.table', 'attendance_entries'));
    }
};
