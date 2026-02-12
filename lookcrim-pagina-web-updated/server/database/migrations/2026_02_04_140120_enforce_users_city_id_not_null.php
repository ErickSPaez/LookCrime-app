<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Intentionally no-op.
        // City is required by application rules for normal users, but admins
        // and future roles with city-bypass permissions may legitimately have
        // city_id = NULL.
    }

    public function down(): void
    {
        // no-op
    }
};
