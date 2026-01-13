Param(
  [int]$Port = 8000
)

$ErrorActionPreference = 'Stop'

function Stop-PortListener([int]$LocalPort) {
  $conns = Get-NetTCPConnection -LocalPort $LocalPort -ErrorAction SilentlyContinue
  foreach ($c in $conns) {
    try {
      Stop-Process -Id $c.OwningProcess -Force -ErrorAction SilentlyContinue
    } catch {}
  }
}

Write-Output 'Deteniendo Cloudflared...'
Get-Process cloudflared -ErrorAction SilentlyContinue | ForEach-Object {
  try { Stop-Process -Id $_.Id -Force -ErrorAction SilentlyContinue } catch {}
}

Write-Output "Liberando puerto ${Port} (Laravel)..."
Stop-PortListener -LocalPort $Port

Write-Output 'Listo.'
