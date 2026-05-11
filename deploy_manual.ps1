# Manual Deploy Script for KODAN-HUB v4.0
# Follows the Antigravity Universal Deployment Protocol

$VersionFile = "VERSION"
if (-Not (Test-Path $VersionFile)) { "1.0.0" | Out-File -FilePath $VersionFile -Encoding utf8 }
$CurrentVersion = (Get-Content $VersionFile).Trim()
$Parts = $CurrentVersion.Split('.')
$NewVersion = "$($Parts[0]).$($Parts[1]).$([int]$Parts[2] + 1)"
$NewVersion | Out-File -FilePath $VersionFile -Encoding utf8

Write-Host "Iniciando Despliegue KODAN-HUB v$NewVersion..." -ForegroundColor Cyan

# 1. Limpieza
if (Test-Path "ready_for_deploy") { Remove-Item -Recurse -Force "ready_for_deploy" }
New-Item -ItemType Directory -Path "ready_for_deploy"

# 2. Copia de Archivos Raíz
$RootFiles = @(".htaccess", "index.php", "config.php", "VERSION")
foreach ($file in $RootFiles) {
    if (Test-Path $file) { Copy-Item $file "ready_for_deploy/" }
}

# 3. Copia de Directorios (EXCLUYENDO 'data' para no pisar la DB del servidor)
$Dirs = @("admin", "src")
foreach ($dir in $Dirs) {
    if (Test-Path $dir) { Copy-Item -Recurse $dir "ready_for_deploy/" }
}

# 4. Finalizar
Write-Host "Despliegue preparado en /ready_for_deploy" -ForegroundColor Green
Write-Host "IMPORTANTE: La carpeta 'data/' ha sido excluida para evitar pérdida de datos en el servidor." -ForegroundColor Yellow
