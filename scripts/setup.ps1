# Windows PowerShell 5.1 and PowerShell 7. No WSL or local PHP/Node required.
[CmdletBinding()]
param()

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot

function Invoke-Compose {
    param([Parameter(Mandatory = $true)][string[]]$Arguments)

    & docker compose -f (Join-Path $projectRoot 'compose.yaml') @Arguments
    if ($LASTEXITCODE -ne 0) {
        throw "Docker Compose is gestopt met foutcode $LASTEXITCODE. Bekijk de melding hierboven."
    }
}

Push-Location -LiteralPath $projectRoot
try {
    if (-not (Get-Command docker -ErrorAction SilentlyContinue)) {
        throw 'Docker is niet gevonden. Installeer Docker Desktop, start het en open daarna een nieuwe PowerShell-terminal.'
    }

    Write-Host 'Docker Desktop controleren...'
    try {
        $engineType = & docker info --format '{{.OSType}}'
        if ($LASTEXITCODE -ne 0) { throw 'Docker engine is niet bereikbaar.' }
    }
    catch {
        throw 'Docker Desktop is niet bereikbaar. Start Docker Desktop en wacht tot de engine draait. Probeer daarna opnieuw.'
    }
    if (($engineType -join '').Trim() -ne 'linux') {
        throw 'Dit project gebruikt Linux-containers. Schakel Docker Desktop over naar Linux containers en probeer opnieuw.'
    }
    Invoke-Compose -Arguments @('version')

    if (-not (Test-Path -LiteralPath '.env')) {
        Copy-Item -LiteralPath '.env.example' -Destination '.env'
    }
    if (-not (Test-Path -LiteralPath 'backend/.env')) {
        Copy-Item -LiteralPath 'backend/.env.example' -Destination 'backend/.env'
    }

    Write-Host 'PHP en afhankelijkheden voorbereiden (de eerste keer kan dit enkele minuten duren)...'
    Invoke-Compose -Arguments @('build', 'app')
    Invoke-Compose -Arguments @('run', '--rm', '--no-deps', 'app', 'composer', 'install', '--prefer-dist', '--no-interaction', '--no-progress')

    # Inspect presence only; never display the application key or replace an existing key.
    $keyLine = Get-Content -LiteralPath 'backend/.env' | Where-Object { $_ -match '^\s*APP_KEY\s*=' } | Select-Object -First 1
    $keyValue = ''
    if ($null -ne $keyLine) {
        $keyValue = ($keyLine -replace '^\s*APP_KEY\s*=\s*', '').Trim().Trim('"').Trim("'")
    }
    if ([string]::IsNullOrWhiteSpace($keyValue)) {
        Invoke-Compose -Arguments @('run', '--rm', '--no-deps', 'app', 'php', 'artisan', 'key:generate', '--ansi')
    }

    Write-Host 'Database en voorbeeldgegevens voorbereiden...'
    Invoke-Compose -Arguments @('up', '-d', '--wait', 'db')
    Invoke-Compose -Arguments @('run', '--rm', '--no-deps', 'app', 'php', 'artisan', 'migrate', '--force')
    Invoke-Compose -Arguments @('run', '--rm', '--no-deps', 'app', 'php', 'artisan', 'db:seed', '--force')

    Write-Host 'Frontend installeren en de applicatie starten...'
    Invoke-Compose -Arguments @('run', '--rm', '--no-deps', 'frontend', 'npm', 'ci', '--no-audit', '--no-fund')
    Invoke-Compose -Arguments @('up', '-d', 'app', 'frontend')

    Write-Host 'Wachten tot de website en API bereikbaar zijn...'
    $ready = $false
    for ($attempt = 0; $attempt -lt 30; $attempt++) {
        try {
            $response = Invoke-WebRequest -UseBasicParsing -Uri 'http://localhost:5173/api/v1/organizations' -TimeoutSec 3
            $payload = $response.Content | ConvertFrom-Json
            if ($response.StatusCode -eq 200 -and $payload.PSObject.Properties.Name -contains 'data') {
                $ready = $true
                break
            }
        }
        catch {
            # Containers may need a moment to finish starting.
        }
        Start-Sleep -Seconds 2
    }
    if (-not $ready) {
        Invoke-Compose -Arguments @('logs', '--tail=40', 'app', 'frontend')
        throw 'De website is nog niet bereikbaar. Bekijk de containerlogs hierboven. Probeer daarna opnieuw met .\start.cmd.'
    }

    Write-Host ''
    Write-Host 'Klaar. Open http://localhost:5173 in uw browser.'
    Write-Host 'Stoppen: docker compose down'
    Write-Host 'Later opnieuw starten: docker compose up -d'
}
catch {
    Write-Error -Message $_.Exception.Message -ErrorAction Continue
    exit 1
}
finally {
    Pop-Location
}
