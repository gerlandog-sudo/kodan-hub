# Manual Deploy Script for KODAN-HUB
# Follows the Antigravity Universal Deployment Protocol

$VersionFile = "VERSION"
if (-Not (Test-Path $VersionFile)) { "1.0.0" | Out-File -FilePath $VersionFile -Encoding utf8 }
$CurrentVersion = Get-Content $VersionFile
$Parts = $CurrentVersion.Split('.')
$NewVersion = "$($Parts[0]).$($Parts[1]).$([int]$Parts[2] + 1)"
$NewVersion | Out-File -FilePath $VersionFile -Encoding utf8

Write-Host "Iniciando Despliegue KODAN-HUB v$NewVersion..." -ForegroundColor Cyan

# 1. Limpieza
if (Test-Path "ready_for_deploy") { Remove-Item -Recurse -Force "ready_for_deploy" }
New-Item -ItemType Directory -Path "ready_for_deploy"

# 2. Copia de Archivos Raíz
$RootFiles = @(".htaccess", "index.php", "config.php", "logo_export.html", "LOGO V22.png", "VERSION")
foreach ($file in $RootFiles) {
    if (Test-Path $file) { Copy-Item $file "ready_for_deploy/" }
}

# 3. Copia de Directorios
$Dirs = @("admin", "src", "database")
foreach ($dir in $Dirs) {
    if (Test-Path $dir) { Copy-Item -Recurse $dir "ready_for_deploy/" }
}

# 4. Sello de Tiempo
$Timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
"<!-- Deployment: $Timestamp | Version: $NewVersion -->" | Out-File -Append -FilePath "ready_for_deploy/index.php"

Write-Host "Despliegue preparado en /ready_for_deploy" -ForegroundColor Green
