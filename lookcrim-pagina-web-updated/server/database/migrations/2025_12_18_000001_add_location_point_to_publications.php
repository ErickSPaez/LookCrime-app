<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // The app historically used `publications` but was renamed to `registers`.
        $tableName = Schema::hasTable('registers') ? 'registers' : (Schema::hasTable('publications') ? 'publications' : null);
        if (!$tableName) {
            return;
        }

        DB::statement("ALTER TABLE {$tableName} ADD COLUMN IF NOT EXISTS location geometry(POINT,4326)");
        // Create GIST index for spatial queries
        DB::statement("CREATE INDEX IF NOT EXISTS {$tableName}_location_gist ON {$tableName} USING GIST (location)");
    }

    public function down(): void
    {
        $tableName = Schema::hasTable('registers') ? 'registers' : (Schema::hasTable('publications') ? 'publications' : null);
        if (!$tableName) {
            return;
        }

        DB::statement("DROP INDEX IF EXISTS {$tableName}_location_gist");
        DB::statement("ALTER TABLE {$tableName} DROP COLUMN IF EXISTS location");
    }
};
