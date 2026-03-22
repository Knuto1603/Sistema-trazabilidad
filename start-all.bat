@echo off
title Sistema Trazabilidad - All Services
echo ============================================
echo   Sistema Trazabilidad - Iniciando servicios
echo ============================================
echo.

set BASE_DIR=%~dp0

echo [1/4] Warmup de cache Symfony...
cd /d %BASE_DIR%sistema-trazabilidad\apps\security && php bin/console cache:warmup --quiet 2>nul
cd /d %BASE_DIR%sistema-trazabilidad\apps\core && php bin/console cache:warmup --quiet 2>nul
echo         Cache OK

echo [2/4] Iniciando Backend Security (port 8000)...
start "Backend Security - 8000" cmd /k "cd /d %BASE_DIR%sistema-trazabilidad\apps\security && symfony serve --port=8000"

echo [3/4] Iniciando Backend Core (port 8001)...
start "Backend Core - 8001" cmd /k "cd /d %BASE_DIR%sistema-trazabilidad\apps\core && symfony serve --port=8001"

echo [4/4] Iniciando Frontend Angular (port 4200)...
start "Frontend Angular - 4200" cmd /k "cd /d %BASE_DIR%frontend && npm start"

echo.
echo Esperando a que los servidores esten listos...
timeout /t 5 /nobreak >nul

echo Haciendo warmup de OPcache (primer request)...
curl -k -s -o nul "https://127.0.0.1:8000/api" 2>nul
curl -k -s -o nul "https://127.0.0.1:8001/api" 2>nul
echo         Warmup OK

echo.
echo ============================================
echo   Servicios listos:
echo     - Security:  https://127.0.0.1:8000
echo     - Core:      https://127.0.0.1:8001
echo     - Frontend:  http://localhost:4200
echo ============================================
echo.
echo Cada servicio corre en su propia ventana.
echo Cierra las ventanas individuales para detenerlos.
echo.
pause
