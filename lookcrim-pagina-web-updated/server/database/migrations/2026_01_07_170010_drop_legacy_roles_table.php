<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('legacy_roles')) {
            Schema::drop('legacy_roles');
        }
    }

    public function down(): void
    {
        // No-op (legacy table intentionally removed).
    }
};
