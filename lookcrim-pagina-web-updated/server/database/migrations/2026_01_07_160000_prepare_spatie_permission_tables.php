<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Drop legacy FK users.role -> roles.slug (created previously) to allow renaming.
        DB::statement(<<<SQL
DO $$
BEGIN
    IF EXISTS (
        SELECT 1
        FROM pg_constraint
        WHERE conname = 'users_role_slug_foreign'
    ) THEN
        ALTER TABLE public.users DROP CONSTRAINT users_role_slug_foreign;
    END IF;
END $$;
SQL);

        // If a legacy roles table exists (with slug/name_en/name_pt), rename it away
        // so Spatie can create its own roles table.
        if (Schema::hasTable('roles') && !Schema::hasTable('legacy_roles')) {
            Schema::rename('roles', 'legacy_roles');
        }
    }

    public function down(): void
    {
        // Best-effort revert: rename back if Spatie roles table is not present.
        if (Schema::hasTable('legacy_roles') && !Schema::hasTable('roles')) {
            Schema::rename('legacy_roles', 'roles');
        }
    }
};
