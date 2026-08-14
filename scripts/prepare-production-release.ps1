param(
    [Parameter(Mandatory = $true)]
    [string] $ReleasePath
)

$ErrorActionPreference = 'Stop'

function New-RandomBase64([int] $bytes) {
    $buffer = New-Object byte[] $bytes
    $rng = [System.Security.Cryptography.RandomNumberGenerator]::Create()
    try { $rng.GetBytes($buffer) } finally { $rng.Dispose() }
    return [Convert]::ToBase64String($buffer)
}

function Quote-Env([string] $value) {
    $escaped = $value.Replace('\', '\\').Replace('"', '\"').Replace('$', '\$')
    return '"' + $escaped + '"'
}

function Read-LocalEnv {
    $values = @{}
    foreach ($line in Get-Content -LiteralPath (Join-Path $PSScriptRoot '..\.env')) {
        if ($line -match '^([A-Z][A-Z0-9_]*)=(.*)$') {
            $key = $matches[1]
            $value = $matches[2].Trim()
            if (($value.StartsWith('"') -and $value.EndsWith('"')) -or ($value.StartsWith("'") -and $value.EndsWith("'"))) {
                $value = $value.Substring(1, $value.Length - 2)
            }
            $values[$key] = $value
        }
    }
    return $values
}

$local = Read-LocalEnv
$databasePassword = [Environment]::GetEnvironmentVariable('KWIZZGO_DB_PASSWORD', 'User')
if ([string]::IsNullOrWhiteSpace($databasePassword)) {
    throw 'A KWIZZGO_DB_PASSWORD felhasználói környezeti változó nincs beállítva.'
}

# A Vite fejlesztői jelzőfájl productionben nem maradhat bent, mert a
# látogató böngészőjét a saját localhost:5173 címére irányítaná.
$viteHotPath = Join-Path $ReleasePath 'public\hot'
if (Test-Path -LiteralPath $viteHotPath) {
    Remove-Item -LiteralPath $viteHotPath -Force
}

$appKey = 'base64:' + (New-RandomBase64 32)
$deployToken = New-RandomBase64 48
$envLines = @(
    'APP_NAME="KwizzGo"',
    'APP_ENV=production',
    "APP_KEY=$(Quote-Env $appKey)",
    'APP_DEBUG=false',
    'APP_URL=https://kwizzgo.com',
    'COMING_SOON_MODE=true',
    '',
    'APP_LOCALE=hu',
    'APP_FALLBACK_LOCALE=hu',
    'APP_FAKER_LOCALE=hu_HU',
    'APP_MAINTENANCE_DRIVER=file',
    'BCRYPT_ROUNDS=12',
    '',
    'LOG_CHANNEL=stack',
    'LOG_STACK=single',
    'LOG_LEVEL=error',
    '',
    'DB_CONNECTION=mysql',
    'DB_HOST=localhost',
    'DB_PORT=3306',
    'DB_DATABASE=sikerdij_kw1zzG0',
    'DB_USERNAME=sikerdij_0Gzz1wK',
    "DB_PASSWORD=$(Quote-Env $databasePassword)",
    '',
    'SESSION_DRIVER=file',
    'SESSION_LIFETIME=120',
    'SESSION_ENCRYPT=true',
    'SESSION_PATH=/',
    'SESSION_DOMAIN=.kwizzgo.com',
    'CACHE_STORE=file',
    'QUEUE_CONNECTION=sync',
    'BROADCAST_CONNECTION=log',
    'FILESYSTEM_DISK=local',
    '',
    "MAIL_MAILER=$(Quote-Env $local['MAIL_MAILER'])",
    "MAIL_SCHEME=$(Quote-Env $local['MAIL_SCHEME'])",
    "MAIL_HOST=$(Quote-Env $local['MAIL_HOST'])",
    "MAIL_PORT=$(Quote-Env $local['MAIL_PORT'])",
    "MAIL_USERNAME=$(Quote-Env $local['MAIL_USERNAME'])",
    "MAIL_PASSWORD=$(Quote-Env $local['MAIL_PASSWORD'])",
    "MAIL_FROM_ADDRESS=$(Quote-Env $local['MAIL_FROM_ADDRESS'])",
    'MAIL_FROM_NAME="KwizzGo"',
    '',
    "GOOGLE_CLIENT_ID=$(Quote-Env $local['GOOGLE_CLIENT_ID'])",
    "GOOGLE_CLIENT_SECRET=$(Quote-Env $local['GOOGLE_CLIENT_SECRET'])",
    'GOOGLE_REDIRECT_URI="https://kwizzgo.com/auth/google/callback"',
    '',
    'RECAPTCHA_ENABLED=true',
    "RECAPTCHA_SITE_KEY=$(Quote-Env $local['RECAPTCHA_SITE_KEY'])",
    "RECAPTCHA_SECRET_KEY=$(Quote-Env $local['RECAPTCHA_SECRET_KEY'])",
    '',
    'EMERGENCY_ADMIN_ENABLED=true',
    "EMERGENCY_ADMIN_EMAIL=$(Quote-Env $local['EMERGENCY_ADMIN_EMAIL'])",
    "EMERGENCY_ADMIN_PASSWORD_HASH=$(Quote-Env $local['EMERGENCY_ADMIN_PASSWORD_HASH'])",
    '',
    "DEPLOY_TOKEN=$(Quote-Env $deployToken)"
)

[System.IO.File]::WriteAllLines((Join-Path $ReleasePath '.env'), $envLines, [System.Text.UTF8Encoding]::new($false))
$deploymentDirectory = Split-Path -Parent $ReleasePath
$legacyTokenPath = Join-Path $ReleasePath '.deploy-token'
if (Test-Path -LiteralPath $legacyTokenPath) {
    Remove-Item -LiteralPath $legacyTokenPath -Force
}
[System.IO.File]::WriteAllText((Join-Path $deploymentDirectory 'deploy-token.txt'), $deployToken, [System.Text.UTF8Encoding]::new($false))

$deployPhp = @'
<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || empty($_SERVER['HTTPS'])) {
    http_response_code(404);
    exit;
}

require dirname(__DIR__).'/vendor/autoload.php';
$app = require_once dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$provided = (string) ($_SERVER['HTTP_X_DEPLOY_TOKEN'] ?? '');
$expected = (string) env('DEPLOY_TOKEN', '');
if ($expected === '' || ! hash_equals($expected, $provided)) {
    http_response_code(404);
    exit;
}

$commands = [
    ['migrate', ['--force' => true]],
    ['storage:link', []],
    ['optimize:clear', []],
    ['config:cache', []],
    ['route:cache', []],
    ['view:cache', []],
];

$results = [];
foreach ($commands as [$command, $arguments]) {
    $exitCode = Artisan::call($command, $arguments);
    $results[$command] = ['exit' => $exitCode, 'output' => trim(Artisan::output())];
    if ($exitCode !== 0 && $command !== 'storage:link') {
        http_response_code(500);
        echo json_encode(['ok' => false, 'results' => $results], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

echo json_encode(['ok' => true, 'results' => $results], JSON_UNESCAPED_UNICODE);
'@

[System.IO.File]::WriteAllText((Join-Path $ReleasePath 'public\kwizzgo-deploy.php'), $deployPhp, [System.Text.UTF8Encoding]::new($false))
$oldHiddenDeployPath = Join-Path $ReleasePath 'public\.kwizzgo-deploy.php'
if (Test-Path -LiteralPath $oldHiddenDeployPath) {
    Remove-Item -LiteralPath $oldHiddenDeployPath -Force
}

$deployTokenHash = [System.BitConverter]::ToString(
    [System.Security.Cryptography.SHA256]::Create().ComputeHash([System.Text.Encoding]::UTF8.GetBytes($deployToken))
).Replace('-', '').ToLowerInvariant()
$bootstrapPhp = @"
<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ((`$_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || empty(`$_SERVER['HTTPS'])) {
    http_response_code(404);
    exit;
}

`$provided = (string) (`$_SERVER['HTTP_X_DEPLOY_TOKEN'] ?? '');
if (! hash_equals('$deployTokenHash', hash('sha256', `$provided))) {
    http_response_code(404);
    exit;
}

if (! class_exists(ZipArchive::class)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'zip_extension_missing']);
    exit;
}

`$archivePath = dirname(__DIR__).'/release.zip';
`$zip = new ZipArchive();
if (`$zip->open(`$archivePath) !== true) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'archive_open_failed']);
    exit;
}

`$files = `$zip->numFiles;
`$ok = `$zip->extractTo(dirname(__DIR__));
`$zip->close();

if (! `$ok) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'archive_extract_failed']);
    exit;
}

echo json_encode(['ok' => true, 'files' => `$files]);
"@
[System.IO.File]::WriteAllText((Join-Path $deploymentDirectory 'kwizzgo-bootstrap.php'), $bootstrapPhp, [System.Text.UTF8Encoding]::new($false))
Write-Output 'production_env_ready=true'
Write-Output 'deployment_endpoint_ready=true'
Write-Output 'bootstrap_ready=true'
