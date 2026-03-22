Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  Sistema Trazabilidad - Iniciando servicios" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

$baseDir = Split-Path -Parent $MyInvocation.MyCommand.Path

Write-Host "[1/4] Warmup de cache Symfony..." -ForegroundColor Yellow
Push-Location "$baseDir\backend\apps\security"
php bin/console cache:warmup --quiet 2>$null
Pop-Location
Push-Location "$baseDir\backend\apps\core"
php bin/console cache:warmup --quiet 2>$null
Pop-Location
Write-Host "        Cache OK" -ForegroundColor Green

Write-Host "[2/4] Iniciando Backend Security (port 8000)..." -ForegroundColor Yellow
Start-Process -FilePath "cmd" -ArgumentList "/k", "cd /d $baseDir\backend\apps\security && symfony serve --port=8000" -WindowStyle Normal

Write-Host "[3/4] Iniciando Backend Core (port 8001)..." -ForegroundColor Yellow
Start-Process -FilePath "cmd" -ArgumentList "/k", "cd /d $baseDir\backend\apps\core && symfony serve --port=8001" -WindowStyle Normal

Write-Host "[4/4] Iniciando Frontend Angular (port 4200)..." -ForegroundColor Yellow
Start-Process -FilePath "cmd" -ArgumentList "/k", "cd /d $baseDir\frontend && npm start" -WindowStyle Normal

Write-Host ""
Write-Host "Esperando a que los servidores esten listos..." -ForegroundColor Gray
Start-Sleep -Seconds 5

Write-Host "Haciendo warmup de OPcache (primer request)..." -ForegroundColor Yellow
try { Invoke-WebRequest -Uri "https://127.0.0.1:8000/api" -SkipCertificateCheck -TimeoutSec 30 -ErrorAction SilentlyContinue | Out-Null } catch {}
try { Invoke-WebRequest -Uri "https://127.0.0.1:8001/api" -SkipCertificateCheck -TimeoutSec 30 -ErrorAction SilentlyContinue | Out-Null } catch {}
Write-Host "        Warmup OK" -ForegroundColor Green

Write-Host ""
Write-Host "============================================" -ForegroundColor Green
Write-Host "  Servicios listos:" -ForegroundColor Green
Write-Host "    - Security:  https://127.0.0.1:8000" -ForegroundColor White
Write-Host "    - Core:      https://127.0.0.1:8001" -ForegroundColor White
Write-Host "    - Frontend:  http://localhost:4200" -ForegroundColor White
Write-Host "============================================" -ForegroundColor Green
Write-Host ""
Write-Host "Cada servicio corre en su propia ventana." -ForegroundColor Gray
Write-Host "Cierra las ventanas individuales para detenerlos." -ForegroundColor Gray
