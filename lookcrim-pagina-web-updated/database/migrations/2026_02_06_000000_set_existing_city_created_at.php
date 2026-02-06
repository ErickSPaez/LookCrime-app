<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Set the already-created default city (e.g. porto) to a known date for display purposes.
        // Scoped to slug=porto to avoid overwriting real timestamps for other cities.
        DB::table('cities')
            ->where('slug', 'porto')
            ->update([
                'created_at' => '2026-01-26 00:00:00',
            ]);
    }

    public function down(): void
    {
        // No safe automatic rollback for timestamps.
    }
};
