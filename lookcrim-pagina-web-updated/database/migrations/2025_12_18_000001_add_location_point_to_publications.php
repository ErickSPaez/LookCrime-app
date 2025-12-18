<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Add a geometry Point column (SRID 4326) to publications
        Schema::table('publications', function (Blueprint $table) {
            // Use raw statement to create geometry column type with SRID
            // We create as nullable to avoid breaking existing rows
        });

        DB::statement("ALTER TABLE publications ADD COLUMN IF NOT EXISTS location geometry(POINT,4326)");
        // Create GIST index for spatial queries
        DB::statement("CREATE INDEX IF NOT EXISTS publications_location_gist ON publications USING GIST (location)");
    }

    public function down(): void
    {
        Schema::table('publications', function (Blueprint $table) {
            // drop index and column if exists
        });
        DB::statement("DROP INDEX IF EXISTS publications_location_gist");
        DB::statement("ALTER TABLE publications DROP COLUMN IF EXISTS location");
    }
};
