<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // This project historically used `publications`, but was renamed to `registers`.
        $table = Schema::hasTable('registers') ? 'registers' : (Schema::hasTable('publications') ? 'publications' : null);
        if (!$table) {
            return;
        }

        if (Schema::hasColumn($table, 'user_id')) {
            $constraintName = $table.'_user_id_foreign';
            $indexName = $table.'_user_id_index';

            // Ensure index exists (safe even if it already exists)
            DB::statement("CREATE INDEX IF NOT EXISTS {$indexName} ON public.{$table} (user_id)");

            // Add FK if it doesn't exist (PostgreSQL doesn't support IF NOT EXISTS for constraints)
            DB::statement(<<<SQL
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM pg_constraint
        WHERE conname = '{$constraintName}'
    ) THEN
        ALTER TABLE public.{$table}
            ADD CONSTRAINT {$constraintName}
            FOREIGN KEY (user_id)
            REFERENCES public.users(id)
            ON DELETE SET NULL;
    END IF;
END $$;
SQL);
        }

        // Optional: users.role -> roles.slug (only if data is consistent)
        if (Schema::hasTable('users') && Schema::hasTable('roles') && Schema::hasColumn('users', 'role') && Schema::hasColumn('roles', 'slug')) {
            DB::statement(<<<SQL
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM pg_constraint
        WHERE conname = 'users_role_slug_foreign'
    ) THEN
        -- Only create FK if there are no orphan role values.
        IF NOT EXISTS (
            SELECT 1
            FROM public.users u
            LEFT JOIN public.roles r ON r.slug = u.role
            WHERE u.role IS NOT NULL
              AND u.role <> ''
              AND r.slug IS NULL
            LIMIT 1
        ) THEN
            ALTER TABLE public.users
                ADD CONSTRAINT users_role_slug_foreign
                FOREIGN KEY (role)
                REFERENCES public.roles(slug)
                ON UPDATE CASCADE
                ON DELETE SET NULL;
        END IF;
    END IF;
END $$;
SQL);
        }
    }

    public function down(): void
    {
        $table = Schema::hasTable('registers') ? 'registers' : (Schema::hasTable('publications') ? 'publications' : null);
        if ($table) {
            $constraintName = $table.'_user_id_foreign';
            $indexName = $table.'_user_id_index';

            DB::statement(<<<SQL
DO $$
BEGIN
    IF EXISTS (
        SELECT 1
        FROM pg_constraint
        WHERE conname = '{$constraintName}'
    ) THEN
        ALTER TABLE public.{$table} DROP CONSTRAINT {$constraintName};
    END IF;
END $$;
SQL);
            DB::statement("DROP INDEX IF EXISTS {$indexName}");
        }

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
    }
};
