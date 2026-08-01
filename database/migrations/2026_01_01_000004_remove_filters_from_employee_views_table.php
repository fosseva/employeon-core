<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('employeon.employees.views_table', 'employee_views');

        if (! Schema::hasTable($tableName)) {
            return;
        }

        if (! Schema::hasColumn($tableName, 'filter_query')) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->text('filter_query')->nullable()->after('icon');
            });
        }

        $hasSearch = Schema::hasColumn($tableName, 'search');
        $hasEmploymentStatus = Schema::hasColumn($tableName, 'employment_status');
        $hasAccessStatus = Schema::hasColumn($tableName, 'access_status');

        if ($hasSearch || $hasEmploymentStatus || $hasAccessStatus) {
            $columns = array_values(array_filter([
                'id',
                $hasSearch ? 'search' : null,
                $hasEmploymentStatus ? 'employment_status' : null,
                $hasAccessStatus ? 'access_status' : null,
            ]));

            DB::table($tableName)
                ->select($columns)
                ->orderBy('id')
                ->each(function (object $view) use ($tableName): void {
                    $query = implode(' ', array_filter([
                        is_string($view->search ?? null) ? trim($view->search) : '',
                        is_string($view->employment_status ?? null) && $view->employment_status !== 'all' ? 'employment:'.$view->employment_status : '',
                        is_string($view->access_status ?? null) && $view->access_status !== 'all' ? 'access:'.$view->access_status : '',
                    ]));

                    DB::table($tableName)
                        ->where('id', $view->id)
                        ->update(['filter_query' => $query]);
                });
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
            foreach (['search', 'employment_status', 'access_status'] as $column) {
                if (Schema::hasColumn($tableName, $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        $tableName = config('employeon.employees.views_table', 'employee_views');

        if (! Schema::hasTable($tableName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
            if (! Schema::hasColumn($tableName, 'search')) {
                $table->string('search')->default('')->after('icon');
            }

            if (! Schema::hasColumn($tableName, 'employment_status')) {
                $table->string('employment_status')->default('all')->after('search');
            }

            if (! Schema::hasColumn($tableName, 'access_status')) {
                $table->string('access_status')->default('all')->after('employment_status');
            }
        });
    }
};
