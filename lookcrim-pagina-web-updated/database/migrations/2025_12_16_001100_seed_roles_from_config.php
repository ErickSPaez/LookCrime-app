<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $existing = DB::table('roles')->count();
        if ($existing > 0) return;
        $defs = config('roles.definitions', []);
        $now = now();
        foreach ($defs as $slug => $perms) {
            DB::table('roles')->insert([
                'slug' => $slug,
                'name_en' => ucfirst(str_replace('_',' ', $slug)),
                'name_pt' => ucfirst(str_replace('_',' ', $slug)),
                'permissions' => json_encode($perms),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // No-op
    }
};
