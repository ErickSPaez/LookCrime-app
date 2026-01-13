Param(
  [int]$Port = 8000,
  [string]$HostAddress = '127.0.0.1'
)

$ErrorActionPreference = 'Stop'

function Stop-PortListener([int]$LocalPort) {
  $conn = Get-NetTCPConnection -LocalPort $LocalPort -ErrorAction SilentlyContinue | Select-Object -First 1
  if ($null -ne $conn) {
    try {
      Stop-Process -Id $conn.OwningProcess -Force -ErrorAction SilentlyContinue
    } catch {}
  }
}

function Get-CloudflaredExe() {
  $root = Join-Path $env:LOCALAPPDATA 'Microsoft\WinGet\Packages'
  $dirs = Get-ChildItem -Path $root -Directory -Filter 'Cloudflare.cloudflared_*' -ErrorAction SilentlyContinue
  foreach ($d in $dirs) {
    $exe = Join-Path $d.FullName 'cloudflared.exe'
    if (Test-Path $exe) { return $exe }
  }

  $cmd = Get-Command cloudflared -ErrorAction SilentlyContinue
  if ($null -ne $cmd) { return $cmd.Source }

  throw 'No se encontró cloudflared.exe. Instalalo con: winget install --id Cloudflare.cloudflared -e --source winget'
}

$project = Split-Path -Parent $PSScriptRoot
Set-Location $project

Write-Output "Proyecto: $project"

# Para demo público: NO usar Vite dev server. Si existe public/hot, Laravel inyecta assets desde localhost:5173.
$hot = Join-Path $project 'public\hot'
if (Test-Path $hot) {
  Remove-Item $hot -Force
  Write-Output 'Eliminado public/hot (modo producción para Vite).'
}

# Build de assets
if (!(Test-Path (Join-Path $project 'node_modules'))) {
  Write-Output 'node_modules no existe -> ejecutando npm install...'
  npm install
}
Write-Output 'Compilando assets (npm run build)...'
npm run build

Write-Output 'Limpiando caches Laravel...'
php artisan optimize:clear

# Asegurar puertos libres
Stop-PortListener -LocalPort $Port

# Arrancar Laravel
Write-Output "Arrancando Laravel en http://${HostAddress}:${Port} ..."
Start-Process -FilePath 'php' -WorkingDirectory $project -ArgumentList @('artisan','serve','--host', $HostAddress, '--port', $Port) -WindowStyle Minimized | Out-Null

# Esperar a que responda
$ready = $false
for ($i=0; $i -lt 25; $i++) {
  $ok = (Test-NetConnection -ComputerName $HostAddress -Port $Port -WarningAction SilentlyContinue).TcpTestSucceeded
  if ($ok) { $ready = $true; break }
  Start-Sleep -Seconds 1
}
if (-not $ready) {
  throw "Laravel no respondió en http://${HostAddress}:${Port}."
}

# Arrancar cloudflared
$exe = Get-CloudflaredExe
$out = Join-Path $project 'storage\logs\cloudflared.out.log'
$err = Join-Path $project 'storage\logs\cloudflared.err.log'
if (Test-Path $out) { Remove-Item $out -Force }
if (Test-Path $err) { Remove-Item $err -Force }

Write-Output 'Arrancando Cloudflare Tunnel (quick tunnel)...'
Start-Process -FilePath $exe -WorkingDirectory $project -ArgumentList @('tunnel','--url',"http://${HostAddress}:${Port}") -RedirectStandardOutput $out -RedirectStandardError $err -WindowStyle Minimized | Out-Null

# Esperar URL
$url = $null
for ($i=0; $i -lt 60; $i++) {
  foreach ($path in @($err, $out)) {
    if (!(Test-Path $path)) { continue }
    $content = Get-Content $path -Raw -ErrorAction SilentlyContinue
    if ([string]::IsNullOrWhiteSpace($content)) { continue }
    # En Windows, algunos logs pueden envolver la URL con saltos de línea.
    $normalized = $content -replace '\s+', ''
    $m = [regex]::Match($normalized, 'https://[a-z0-9-]+\.trycloudflare\.com')
    if ($m.Success) { $url = $m.Value; break }
  }
  if ($null -ne $url) { break }
  Start-Sleep -Seconds 1
}

if ($null -eq $url) {
  Write-Output 'Tunnel iniciado, pero todavía no pude leer la URL.'
  Write-Output "Revisá: $err"
  Write-Output "Revisá: $out"
  if (Test-Path $err) {
    Write-Output '--- cloudflared.err.log (tail) ---'
    Get-Content $err -Tail 80
  }
  if (Test-Path $out) {
    Write-Output '--- cloudflared.out.log (tail) ---'
    Get-Content $out -Tail 80
  }
  exit 2
}

Write-Output ''
Write-Output 'LINK PUBLICO:'
Write-Output $url
Write-Output ''
Write-Output 'Nota: este link cambia cada vez que reiniciás cloudflared.'
Write-Output 'Para detener todo: ejecutá scripts\public-demo-stop.ps1'
