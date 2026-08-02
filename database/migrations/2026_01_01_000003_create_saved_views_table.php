<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('employeon.saved_views.table', 'saved_views'), function (Blueprint $table): void {
            $table->id();
            $table->string('owner_type');
            $table->unsignedBigInteger('owner_id');
            $table->string('viewable_type');
            $table->string('key');
            $table->string('name');
            $table->string('icon')->default('eye');
            $table->text('filter_query')->nullable();
            $table->string('sort_column')->default('employee');
            $table->string('sort_direction')->default('asc');
            $table->json('sorts')->nullable();
            $table->json('columns');
            $table->timestamps();

            $table->index(['owner_type', 'owner_id']);
            $table->index('viewable_type');
            $table->unique(['owner_type', 'owner_id', 'viewable_type', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('employeon.saved_views.table', 'saved_views'));
    }
};
