$ErrorActionPreference = 'Stop'
Set-Location -LiteralPath (Split-Path -Parent $PSScriptRoot)
$taskIniPath = Join-Path (Get-Location) 'storage\app\private\php-scheduler'
if (Test-Path -LiteralPath $taskIniPath) { $env:PHP_INI_SCAN_DIR = $taskIniPath }
php artisan schedule:run >> storage/logs/content-scheduler.log 2>&1
php artisan queue:work pdf-extraction --queue=pdf-extraction --stop-when-empty --max-time=50 --timeout=150 --tries=1 >> storage/logs/content-scheduler.log 2>&1
