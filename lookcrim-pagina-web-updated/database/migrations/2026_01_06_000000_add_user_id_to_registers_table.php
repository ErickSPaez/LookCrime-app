<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $tableName = Schema::hasTable('registers') ? 'registers' : (Schema::hasTable('publications') ? 'publications' : null);
        if (!$tableName) {
            return;
        }

        if (Schema::hasColumn($tableName, 'user_id')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()->index();
        });
    }

    public function down(): void
    {
        $tableName = Schema::hasTable('registers') ? 'registers' : (Schema::hasTable('publications') ? 'publications' : null);
        if (!$tableName) {
            return;
        }

        if (!Schema::hasColumn($tableName, 'user_id')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
