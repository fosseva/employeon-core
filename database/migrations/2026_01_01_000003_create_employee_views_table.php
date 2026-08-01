<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('employeon.employees.views_table', 'employee_views'), function (Blueprint $table): void {
            $table->id();
            $table->string('owner_email')->nullable()->index();
            $table->string('key');
            $table->string('name');
            $table->string('icon')->default('eye');
            $table->text('filter_query')->nullable();
            $table->string('sort_column')->default('employee');
            $table->string('sort_direction')->default('asc');
            $table->json('columns');
            $table->timestamps();

            $table->unique(['owner_email', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('employeon.employees.views_table', 'employee_views'));
    }
};
