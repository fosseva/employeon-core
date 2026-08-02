<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('employeon.employees.table', 'employees'), function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('access_status')->default('not_invited')->index();
            $table->string('invite_token')->nullable()->index();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('invite_accepted_at')->nullable();
            $table->timestamp('access_disabled_at')->nullable();
            $table->string('employee_number')->nullable()->unique();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('display_name')->nullable();
            $table->string('work_email')->nullable()->unique();
            $table->string('personal_email')->nullable();
            $table->string('employment_status')->default('active')->index();
            $table->date('joined_on')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('employeon.employees.table', 'employees'));
    }
};
