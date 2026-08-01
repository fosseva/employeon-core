<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('employeon.employees.views_table', 'employee_views');

        if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'filter_query')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table): void {
            $table->text('filter_query')->nullable()->after('icon');
        });
    }

    public function down(): void
    {
        $tableName = config('employeon.employees.views_table', 'employee_views');

        if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'filter_query')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table): void {
            $table->dropColumn('filter_query');
        });
    }
};
