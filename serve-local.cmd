@echo off
setlocal
cd /d "%~dp0public"
php -d extension=pdo_sqlite -d extension=sqlite3 -S 127.0.0.1:8001 "%~dp0vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php"
endlocal
