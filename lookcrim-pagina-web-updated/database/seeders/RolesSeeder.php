<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $defs = config('roles.definitions', []);
        foreach ($defs as $slug => $perms) {
            Role::updateOrCreate(
                ['slug' => $slug],
                [
                    'name_en' => ucfirst(str_replace('_',' ', $slug)),
                    'name_pt' => ucfirst(str_replace('_',' ', $slug)),
                    'permissions' => $perms,
                ]
            );
        }
    }
}
