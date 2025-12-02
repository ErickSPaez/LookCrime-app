$legacy = Get-Content (Join-Path $PSScriptRoot 'legacy_views.txt')
$updated = Get-Content (Join-Path $PSScriptRoot 'updated_views.txt')
$legacy_rel = $legacy | ForEach-Object { $i = $_.IndexOf('\resources\views\'); if($i -ge 0){ $_.Substring($i + 16)} else {$_} }
$updated_rel = $updated | ForEach-Object { $i = $_.IndexOf('\resources\views\'); if($i -ge 0){ $_.Substring($i + 16)} else {$_} }
$only = $legacy_rel | Where-Object { $updated_rel -notcontains $_ }
$only | Set-Content (Join-Path $PSScriptRoot 'only_legacy.txt') -Encoding utf8
$only | Select-Object -First 200
