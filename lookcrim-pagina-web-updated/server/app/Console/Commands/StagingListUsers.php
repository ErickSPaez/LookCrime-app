<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StagingListUsers extends Command
{
    protected $signature = 'staging:list-users
        {--conn= : Conexion fuente (por defecto: la default)}
        {--limit=50 : Cantidad maxima a mostrar}';

    protected $description = 'Lista usuarios desde la DB fuente (PostGIS) para elegir cuales copiar a Supabase staging.';

    public function handle(): int
    {
        $conn = (string) ($this->option('conn') ?: config('database.default'));
        $limit = (int) $this->option('limit');
        if ($limit <= 0) {
            $limit = 50;
        }

        if (!Schema::connection($conn)->hasTable('users')) {
            $this->error("No existe la tabla users en la conexion [$conn].");
            return self::FAILURE;
        }

        $query = DB::connection($conn)
            ->table('users')
            ->select(['id', 'name', 'email', 'admin', 'banned', 'city_id', 'created_at'])
            ->orderByDesc('admin')
            ->orderBy('id')
            ->limit($limit);

        $rows = $query->get();

        if ($rows->isEmpty()) {
            $this->warn('No hay usuarios.');
            return self::SUCCESS;
        }

        $this->line("Fuente: [$conn]");
        $this->table(
            ['id', 'admin', 'banned', 'city_id', 'email', 'name', 'created_at'],
            $rows->map(fn ($r) => [
                $r->id,
                (int) ($r->admin ?? 0),
                (int) ($r->banned ?? 0),
                $r->city_id,
                $r->email,
                $r->name,
                $r->created_at,
            ])->all()
        );

        return self::SUCCESS;
    }
}
