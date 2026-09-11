param(
    [ValidateRange(1024, 65535)]
    [int] $Port = 8001
)

$ErrorActionPreference = 'Stop'
$projectRoot = $PSScriptRoot
$publicRoot = Join-Path $projectRoot 'public'
$router = Join-Path $projectRoot 'vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php'
$phpCommand = Get-Command php -ErrorAction Stop

Push-Location $publicRoot

try {
    & $phpCommand.Source -d extension=pdo_sqlite -d extension=sqlite3 -S "127.0.0.1:$Port" $router
} finally {
    Pop-Location
}
