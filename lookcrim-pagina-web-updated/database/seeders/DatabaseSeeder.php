<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Legacy initial data (port from old project)
        \DB::table('homepage')->insertIfNotExists = function() {
            // noop placeholder to keep structure (we'll insert directly below)
        };

        // Homepage
        if (!\DB::table('homepage')->exists()) {
            \DB::table('homepage')->insert([
                'center_text_en' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.',
                'center_text_pt' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.',
            ]);
        }

        // Users (legacy)
        if (!\DB::table('users')->where('email', 'admin1@ufp.edu.pt')->exists()) {
            \DB::table('users')->insert([
                'name' => 'Admnistrador(a) 1',
                'nickname' => 'OPVC',
                'institution' => 'UFP',
                'email' => 'admin1@ufp.edu.pt',
                'password' => bcrypt('opvc321'),
                'banned' => 0,
                'admin' => 1,
            ]);
        }

        if (!\DB::table('users')->where('email', 'user1@ufp.edu.pt')->exists()) {
            \DB::table('users')->insert([
                'name' => 'Utilizador',
                'nickname' => 'Teste 1',
                'institution' => 'UFP',
                'email' => 'user1@ufp.edu.pt',
                'password' => bcrypt('opvc321'),
                'banned' => 0,
                'admin' => 0,
            ]);
        }
    }
}
