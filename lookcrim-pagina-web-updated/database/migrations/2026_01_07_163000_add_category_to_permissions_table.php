<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $permissionsTable = $tableNames['permissions'] ?? 'permissions';

        if (!Schema::hasTable($permissionsTable)) {
            return;
        }

        if (!Schema::hasColumn($permissionsTable, 'category')) {
            Schema::table($permissionsTable, function (Blueprint $table) {
                $table->string('category')->default('General')->index();
            });
        }
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names');
        $permissionsTable = $tableNames['permissions'] ?? 'permissions';

        if (Schema::hasTable($permissionsTable) && Schema::hasColumn($permissionsTable, 'category')) {
            Schema::table($permissionsTable, function (Blueprint $table) {
                $table->dropColumn('category');
            });
        }
    }
};
