<#
PowerShell helper to enable PostGIS (if possible), run Laravel migrations and backfill `location` from latitude/longitude.

USAGE:
- Open PowerShell as Administrator (recommended when creating extensions).
- Run: .\scripts\windows_enable_postgis_and_migrate.ps1
- The script will prompt for DB connection details and for the Laravel project path.

NOTE: Creating the PostGIS extension requires superuser privileges in PostgreSQL. If your DB user is not a superuser, the CREATE EXTENSION step will fail; in that case run the same CREATE EXTENSION statement as the 'postgres' superuser or use pgAdmin/StackBuilder to install PostGIS binaries.
#>

function Prompt-SecureInput($prompt) {
    Write-Host -NoNewline ("{0}: " -f $prompt)
    $sec = Read-Host -AsSecureString
    return [Runtime.InteropServices.Marshal]::PtrToStringAuto([Runtime.InteropServices.Marshal]::SecureStringToBSTR($sec))
}

# Ask connection details
$dbHost = Read-Host "DB host (default: localhost)"
if ([string]::IsNullOrWhiteSpace($dbHost)) { $dbHost = 'localhost' }
$dbPort = Read-Host "DB port (default: 5432)"
if ([string]::IsNullOrWhiteSpace($dbPort)) { $dbPort = '5432' }
$dbName = Read-Host "Database name (e.g. lookcrim)"
$dbUser = Read-Host "DB user (e.g. postgres)"
$dbPass = Prompt-SecureInput "DB password (input hidden)"

# Laravel project path (default to current folder)
$projectPath = Read-Host "Laravel project path (default: current folder)"
if ([string]::IsNullOrWhiteSpace($projectPath)) { $projectPath = (Get-Location).Path }

Write-Host "\nUsing: host=$dbHost port=$dbPort db=$dbName user=$dbUser" -ForegroundColor Cyan

# Helper to run psql commands
function Run-PSQLCommand([string]$sql) {
    $env:PGPASSWORD = $dbPass
    Write-Host "-> Ejecutando: $sql" -ForegroundColor Yellow
    $psql = Get-Command psql -ErrorAction SilentlyContinue
    if (-not $psql) {
        Remove-Item env:PGPASSWORD -ErrorAction SilentlyContinue
        return @{ Output = "ERROR: 'psql' no encontrado en PATH."; ExitCode = 127 }
    }
    $psqlArgs = @('-h', $dbHost, '-p', $dbPort, '-U', $dbUser, '-d', $dbName, '-Atc', $sql)
    $output = & psql @psqlArgs 2>&1
    $exitCode = $LASTEXITCODE
    Remove-Item env:PGPASSWORD -ErrorAction SilentlyContinue
    return @{ Output = $output; ExitCode = $exitCode }
}

# 1) Check psql availability
Write-Host "\n1) Verificando que 'psql' esté disponible en PATH..." -ForegroundColor Cyan
$psqlPath = Get-Command psql -ErrorAction SilentlyContinue
if (-not $psqlPath) {
    Write-Host "ERROR: 'psql' no se encuentra en PATH. Asegúrate de tener PostgreSQL cliente instalado y 'psql' accesible." -ForegroundColor Red
    Write-Host "Si instalaste PostgreSQL con EnterpriseDB, agrega la carpeta 'bin' (p.ej. C:\Program Files\PostgreSQL\13\bin) al PATH o ejecuta este script desde ese folder." -ForegroundColor Yellow
    exit 1
}
Write-Host "psql encontrado: $($psqlPath.Path)" -ForegroundColor Green

# 2) Show current user and server version
$res = Run-PSQLCommand("SELECT current_user, version();")
Write-Host "psql response:"; Write-Host $res.Output

# 3) Try to get PostGIS version (if extension exists)
$res = Run-PSQLCommand("SELECT PostGIS_full_version();")
if ($res.ExitCode -eq 0 -and $res.Output -and -not $res.Output -match "ERROR") {
    Write-Host "PostGIS ya está habilitado:" -ForegroundColor Green
    Write-Host $res.Output
} else {
    Write-Host "PostGIS no parece estar habilitado. Intentando crear la extensión (puede requerir superuser)..." -ForegroundColor Yellow
    $res2 = Run-PSQLCommand("CREATE EXTENSION IF NOT EXISTS postgis;")
    if ($res2.ExitCode -eq 0) {
        Write-Host "CREATE EXTENSION ejecutado." -ForegroundColor Green
        $res3 = Run-PSQLCommand("SELECT PostGIS_full_version();")
        Write-Host $res3.Output
    } else {
        Write-Host "ERROR creando extensión PostGIS (probablemente falta privilegios o no están instalados los binarios del servidor)." -ForegroundColor Red
        Write-Host "Salida: $($res2.Output)" -ForegroundColor Red
        Write-Host "Si ves un error de permisos, ejecuta la sentencia CREATE EXTENSION como superuser (usuario 'postgres') o usa pgAdmin/StackBuilder para instalar PostGIS en Windows." -ForegroundColor Yellow
        $shouldContinue = Read-Host "¿Continuar con migraciones Laravel aunque PostGIS no esté habilitado? (y/N)"
        if ($shouldContinue -ne 'y' -and $shouldContinue -ne 'Y') { exit 1 }
    }
}

# 4) Run Laravel migrations
Write-Host "\n4) Ejecutando migraciones de Laravel..." -ForegroundColor Cyan
Set-Location -Path $projectPath
# Ensure composer/vendor/autoload exists? assume environment prepared
$php = Get-Command php -ErrorAction SilentlyContinue
if (-not $php) {
    Write-Host "ERROR: 'php' no está disponible en PATH. Instala PHP o ejecuta este script desde un entorno donde PHP esté en PATH." -ForegroundColor Red
    exit 1
}
Write-Host "Ejecutando: php artisan migrate --force" -ForegroundColor Yellow
$env:COMPOSER_HOME = "$env:USERPROFILE\AppData\Roaming\Composer"
$proc = Start-Process -FilePath php -ArgumentList 'artisan','migrate','--force' -NoNewWindow -Wait -PassThru -WorkingDirectory $projectPath
if ($proc.ExitCode -ne 0) {
    Write-Host "php artisan migrate falló con código $($proc.ExitCode). Revisa errores." -ForegroundColor Red
    exit 1
}
Write-Host "Migraciones ejecutadas." -ForegroundColor Green

# 5) Backfill existing publications location using latitude/longitude
$doBackfill = Read-Host "¿Quieres rellenar 'location' para publicaciones existentes desde latitude/longitude? (y/N)"
if ($doBackfill -eq 'y' -or $doBackfill -eq 'Y') {
    Write-Host "Ejecutando backfill..." -ForegroundColor Cyan

    $sql = "UPDATE registers SET location = ST_SetSRID(ST_MakePoint(longitude, latitude), 4326) WHERE latitude IS NOT NULL AND longitude IS NOT NULL AND location IS NULL;"
    $res = Run-PSQLCommand($sql)
}

# 6) Verify a sample
Write-Host "\n6) Verificando algunos registros (id, latitude, longitude, ST_AsText(location))..." -ForegroundColor Cyan
$res = Run-PSQLCommand("SELECT id, latitude, longitude, ST_AsText(location) FROM registers LIMIT 10;")
Write-Host $res.Output
Write-Host $res.Output

Write-Host "\nHecho. Si aparece algún error, copia aquí la salida (todo el texto) y lo reviso." -ForegroundColor Green

# End of script
