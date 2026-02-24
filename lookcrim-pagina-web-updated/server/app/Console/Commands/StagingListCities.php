<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StagingListCities extends Command
{
    protected $signature = 'staging:list-cities
        {--conn= : Conexion fuente (por defecto: la default)}
        {--limit=50 : Cantidad maxima a mostrar}';

    protected $description = 'Lista ciudades desde la DB fuente (PostGIS) para elegir cuales copiar a Supabase staging.';

    public function handle(): int
    {
        $conn = (string) ($this->option('conn') ?: config('database.default'));
        $limit = (int) $this->option('limit');
        if ($limit <= 0) {
            $limit = 50;
        }

        if (!Schema::connection($conn)->hasTable('cities')) {
            $this->error("No existe la tabla cities en la conexion [$conn].");
            return self::FAILURE;
        }

        $rows = DB::connection($conn)
            ->table('cities')
            ->select(['id', 'name', 'slug', 'center_lat', 'center_lng', 'radius_m'])
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($rows->isEmpty()) {
            $this->warn('No hay ciudades.');
            return self::SUCCESS;
        }

        $this->line("Fuente: [$conn]");
        $this->table(
            ['id', 'slug', 'name', 'center_lat', 'center_lng', 'radius_m'],
            $rows->map(fn ($r) => [
                $r->id,
                $r->slug,
                $r->name,
                $r->center_lat,
                $r->center_lng,
                $r->radius_m,
            ])->all()
        );

        return self::SUCCESS;
    }
}
