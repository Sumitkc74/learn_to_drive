param([string]$Python = 'python')
$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$moduleDirectory = Join-Path $projectRoot 'storage/app/private/ocr-python'
$modelDirectory = Join-Path $projectRoot 'storage/app/private/ocr-tessdata'
New-Item -ItemType Directory -Force -Path $moduleDirectory, $modelDirectory | Out-Null
& $Python -m pip install --target $moduleDirectory --disable-pip-version-check -r (Join-Path $PSScriptRoot 'requirements-ocr-windows.txt')
if ($LASTEXITCODE -ne 0) { throw 'OCR package installation failed. This Windows setup requires Python 3.12 x64.' }
$models = @{
    nep = '280BA9450B4F21AFBF5985E0DE87857B75A972577C50EF0603C141DDDE4F1CB8'
    eng = '7D4322BD2A7749724879683FC3912CB542F19906C83BCC1A52132556427170B2'
}
foreach ($language in $models.Keys) {
    $destination = Join-Path $modelDirectory ($language + '.traineddata')
    if ((Test-Path -LiteralPath $destination) -and (Get-FileHash -LiteralPath $destination -Algorithm SHA256).Hash -eq $models[$language]) { continue }
    $temporary = $destination + '.download'
    try {
        Invoke-WebRequest ('https://raw.githubusercontent.com/tesseract-ocr/tessdata_fast/main/' + $language + '.traineddata') -OutFile $temporary
        if ((Get-FileHash -LiteralPath $temporary -Algorithm SHA256).Hash -ne $models[$language]) { throw 'The downloaded language model checksum differs from the tested version.' }
        Move-Item -LiteralPath $temporary -Destination $destination -Force
    } finally {
        if (Test-Path -LiteralPath $temporary) { Remove-Item -LiteralPath $temporary }
    }
}
& $Python (Join-Path $PSScriptRoot 'pdf_tools.py') check --modules $moduleDirectory --tessdata $modelDirectory
if ($LASTEXITCODE -ne 0) { throw 'OCR readiness check failed.' }
