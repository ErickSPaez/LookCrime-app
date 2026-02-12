<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillRegisterAuthors extends Command
{
    protected $signature = 'lc:backfill-register-authors {--user-id= : User ID to assign as author} {--prefer-name=Admin 1 : Preferred admin user name to search first}';

    protected $description = 'Assigns a user_id to existing registers/publications rows where user_id is NULL.';

    public function handle(): int
    {
        $table = Schema::hasTable('registers') ? 'registers' : (Schema::hasTable('publications') ? 'publications' : null);
        if (!$table) {
            $this->error('No registers/publications table found.');
            return self::FAILURE;
        }

        $userIdOption = $this->option('user-id');
        $preferredName = (string) $this->option('prefer-name');

        $user = null;
        if (!empty($userIdOption)) {
            $user = User::query()->find($userIdOption);
        }

        if (!$user && $preferredName !== '') {
            $user = User::query()->where('name', $preferredName)->first();
        }

        if (!$user) {
            $user = User::query()->where('admin', 1)->orderBy('id')->first();
        }

        if (!$user) {
            $this->error('No admin user found to assign.');
            return self::FAILURE;
        }

        $nullBefore = DB::table($table)->whereNull('user_id')->count();
        $updated = DB::table($table)->whereNull('user_id')->update(['user_id' => $user->id]);
        $nullAfter = DB::table($table)->whereNull('user_id')->count();

        $this->info('Backfill complete.');
        $this->line('Table: '.$table);
        $this->line('Assigned user: #'.$user->id.' '.($user->name ?? $user->email ?? ''));
        $this->line('NULL before: '.$nullBefore);
        $this->line('Rows updated: '.$updated);
        $this->line('NULL after: '.$nullAfter);

        return self::SUCCESS;
    }
}
