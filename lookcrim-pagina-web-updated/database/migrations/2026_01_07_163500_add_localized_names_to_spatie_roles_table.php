<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $rolesTable = $tableNames['roles'] ?? 'roles';

        if (!Schema::hasTable($rolesTable)) {
            return;
        }

        Schema::table($rolesTable, function (Blueprint $table) use ($rolesTable) {
            if (!Schema::hasColumn($rolesTable, 'name_en')) {
                $table->string('name_en')->nullable();
            }
            if (!Schema::hasColumn($rolesTable, 'name_pt')) {
                $table->string('name_pt')->nullable();
            }
        });
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names');
        $rolesTable = $tableNames['roles'] ?? 'roles';

        if (!Schema::hasTable($rolesTable)) {
            return;
        }

        Schema::table($rolesTable, function (Blueprint $table) use ($rolesTable) {
            if (Schema::hasColumn($rolesTable, 'name_en')) {
                $table->dropColumn('name_en');
            }
            if (Schema::hasColumn($rolesTable, 'name_pt')) {
                $table->dropColumn('name_pt');
            }
        });
    }
};
