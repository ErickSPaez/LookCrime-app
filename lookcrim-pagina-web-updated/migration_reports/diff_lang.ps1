$legacyRoot = 'C:\aaaaaa PROYECTOS PROVISORIO\LAB PROGRAMACION\LookCrime\3AppsLookCrim2\lookcrim-pagina-web\resources\lang'
$updatedRoot = (Join-Path $PSScriptRoot '..\resources\lang')
$legacyFiles = Get-ChildItem -Path $legacyRoot -Recurse -File | ForEach-Object { $_.FullName }
$updatedFiles = Get-ChildItem -Path $updatedRoot -Recurse -File | ForEach-Object { $_.FullName }
function RelPath([string]$fullPath, [string]$root) { return $fullPath.Substring($root.Length) -replace '^\\','' }
$legacyRel = $legacyFiles | ForEach-Object { RelPath $_ $legacyRoot }
$updatedRel = $updatedFiles | ForEach-Object { RelPath $_ $updatedRoot }
$onlyLegacy = $legacyRel | Where-Object { $updatedRel -notcontains $_ }
$onlyUpdated = $updatedRel | Where-Object { $legacyRel -notcontains $_ }
$onlyLegacy | Set-Content (Join-Path $PSScriptRoot 'only_lang_files_legacy.txt') -Encoding utf8
$onlyUpdated | Set-Content (Join-Path $PSScriptRoot 'only_lang_files_updated.txt') -Encoding utf8
Write-Host "Only in legacy (files):"; $onlyLegacy | ForEach-Object { Write-Host "  $_" }
Write-Host "`nOnly in updated (files):"; $onlyUpdated | ForEach-Object { Write-Host "  $_" }

# For files present in both, show top-level keys count for quick hint
Write-Host "`nCommon files key-counts (legacy vs updated):"
$common = $legacyRel | Where-Object { $updatedRel -contains $_ }
foreach($rel in $common){
    $lpath = Join-Path $legacyRoot $rel
    $upath = Join-Path $updatedRoot $rel
    function CountKeys($path){
        try{ $text = Get-Content $path -Raw; ($text -split "=>").Length }
        catch{ 0 }
    }
    $lk = CountKeys $lpath
    $uk = CountKeys $upath
    Write-Host "  $rel : $lk vs $uk"
}
