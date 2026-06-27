@echo off
cd /d "%~dp0"
if "%SERIAL_PORT%"=="" set SERIAL_PORT=COM3
npm run bridge
