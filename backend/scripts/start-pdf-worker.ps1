$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$phpExecutable = (Get-Command php -ErrorAction Stop).Source
$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$worker = Start-Process -FilePath $phpExecutable -ArgumentList @('artisan','queue:work','pdf-extraction','--queue=pdf-extraction','--sleep=2','--tries=3','--timeout=150','--memory=256') -WorkingDirectory $projectRoot -WindowStyle Hidden -RedirectStandardOutput (Join-Path $projectRoot ('storage/logs/pdf-worker-' + $stamp + '.log')) -RedirectStandardError (Join-Path $projectRoot ('storage/logs/pdf-worker-' + $stamp + '.error.log')) -PassThru
Write-Output ('PDF extraction worker started. PID: ' + $worker.Id)
