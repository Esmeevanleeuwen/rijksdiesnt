# Windows PowerShell 5.1 and PowerShell 7. Existing Govern files stay unchanged.
[CmdletBinding()]
param([string]$GovernPath = '', [string]$GovernProjectName = '')

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$previousOutputEncoding = $OutputEncoding

function Invoke-Docker {
    param([Parameter(Mandatory = $true)][string[]]$Arguments, [switch]$Capture, [AllowEmptyString()][string]$InputText)
    if ($PSBoundParameters.ContainsKey('InputText')) {
        $output = $InputText | & docker @Arguments
    }
    else { $output = & docker @Arguments }
    $exitCode = $LASTEXITCODE
    if ($exitCode -ne 0) { throw "Docker is gestopt met foutcode $exitCode. Bekijk de melding hierboven; bestaande gegevens zijn niet verwijderd." }
    if ($Capture) { return $output }
    foreach ($line in @($output)) { Write-Host $line }
}

Push-Location -LiteralPath $projectRoot
try {
    if (-not (Get-Command docker -ErrorAction SilentlyContinue)) {
        throw 'Docker is niet gevonden. Installeer en start Docker Desktop en open een nieuwe PowerShell-terminal.'
    }
    try { $engine = Invoke-Docker -Arguments @('info', '--format', '{{.OSType}}') -Capture }
    catch { throw 'Docker Desktop is niet bereikbaar. Start Docker Desktop en wacht tot de engine draait.' }
    if (($engine -join '').Trim() -ne 'linux') { throw 'Schakel Docker Desktop over naar Linux-containers en probeer opnieuw.' }
    Invoke-Docker -Arguments @('compose', 'version')

    if ([string]::IsNullOrWhiteSpace($GovernPath)) { $GovernPath = Join-Path (Split-Path -Parent $projectRoot) 'govern' }
    if (-not (Test-Path -LiteralPath $GovernPath -PathType Container)) {
        throw 'De Govern-map is niet gevonden. Geef het pad mee, bijvoorbeeld: .\connect-govern.cmd -GovernPath "C:\Users\esmee\govern"'
    }
    $GovernPath = (Resolve-Path -LiteralPath $GovernPath).Path
    foreach ($relative in @('compose.yaml', '.env', 'app/src/PostgresAdapter.php')) {
        if (-not (Test-Path -LiteralPath (Join-Path $GovernPath $relative) -PathType Leaf)) {
            throw "Govern mist $relative. Gebruik de PostgreSQL-versie van Govern en start die eerst vanuit de Govern-map met start.cmd. De koppeling wisselt geen Git-branch en wijzigt Govern-bestanden niet."
        }
    }
    foreach ($relative in @('backend/.env', 'backend/vendor/autoload.php', 'scripts/check-govern-connection.php')) {
        if (-not (Test-Path -LiteralPath (Join-Path $projectRoot $relative) -PathType Leaf)) {
            throw 'De Rijksdienst-installatie is nog niet compleet. Voer eerst .\start.cmd uit in de Rijksdienst-map en probeer daarna opnieuw.'
        }
    }

    $governBase = @('compose', '--project-directory', $GovernPath, '--env-file', (Join-Path $GovernPath '.env'), '-f', (Join-Path $GovernPath 'compose.yaml'))
    if (-not [string]::IsNullOrWhiteSpace($GovernProjectName)) {
        if ($GovernProjectName -notmatch '^[a-z0-9][a-z0-9_-]*$') { throw 'GovernProjectName mag alleen kleine letters, cijfers, underscores en koppeltekens bevatten.' }
        $governBase += @('--project-name', $GovernProjectName)
    }
    $running = @(Invoke-Docker -Arguments ($governBase + @('ps', '--status', 'running', '--services')) -Capture)
    if ($running -notcontains 'database' -or $running -notcontains 'gateway') {
        throw 'Govern draait nog niet volledig. Start Govern eerst met start.cmd vanuit de Govern-map; de bestaande MariaDB en gateway moeten draaien.'
    }

    $rijksCompose = @('compose', '--project-directory', $projectRoot, '-f', (Join-Path $projectRoot 'compose.yaml'))
    if (-not [string]::IsNullOrWhiteSpace($env:CODEX_PROXY_CERT)) { $rijksCompose += @('-f', (Join-Path $projectRoot 'compose.cloud.yaml')) }
    Write-Host 'De bestaande Rijksdienst-database en migraties controleren...'
    Invoke-Docker -Arguments ($rijksCompose + @('up', '-d', '--wait', 'db'))
    Invoke-Docker -Arguments ($rijksCompose + @('run', '--rm', '--no-deps', 'app', 'php', 'artisan', 'migrate', '--force'))

    $containers = @(Invoke-Docker -Arguments ($rijksCompose + @('ps', '-q', 'db')) -Capture)
    if ($containers.Count -ne 1 -or $containers[0] -notmatch '^[a-f0-9]{12,64}$') { throw 'De Rijksdienst PostgreSQL-container kon niet eenduidig worden gevonden.' }
    $containerId = $containers[0]
    $containerName = ((Invoke-Docker -Arguments @('inspect', '--format', '{{.Name}}', $containerId) -Capture) -join '').Trim().TrimStart('/')
    $networkJson = (Invoke-Docker -Arguments @('inspect', '--format', '{{json .NetworkSettings.Networks}}', $containerId) -Capture) -join ''
    $networks = $networkJson | ConvertFrom-Json
    # Quote-free Go templates work with both PS5.1 and PS7 native argument parsing.
    # Intersect actual memberships with Compose's explicitly labelled default networks.
    $defaultNetworks = @(Invoke-Docker -Arguments @('network', 'ls', '--filter', 'label=com.docker.compose.network=default', '--format', '{{.Name}}') -Capture)
    $matchedNetworks = @($networks.PSObject.Properties.Name | Where-Object { $defaultNetworks -contains $_ })
    if ($matchedNetworks.Count -ne 1) { throw 'Het standaardnetwerk van de Rijksdienst-database kon niet eenduidig worden gevonden.' }
    $networkName = $matchedNetworks[0]
    foreach ($value in @($containerName, $networkName)) {
        if ($value -notmatch '^[A-Za-z0-9][A-Za-z0-9_.-]*$') { throw 'Docker gaf een onverwachte container- of netwerknaam; de koppeling is gestopt.' }
    }

    Write-Host 'Een afzonderlijke PostgreSQL-leesverbinding voor Govern voorbereiden...'
    Invoke-Docker -Arguments ($rijksCompose + @('run', '--rm', '--no-deps', 'app', 'php', 'artisan', 'govern:prepare-reader'))
    $privateDirectory = Join-Path $projectRoot 'backend/storage/app/private/govern'
    $readerFile = Join-Path $privateDirectory 'reader.env'
    if (-not (Test-Path -LiteralPath $readerFile -PathType Leaf)) { throw 'Het private leesprofiel ontbreekt. De bestaande Govern-instellingen zijn niet gewijzigd.' }
    $networkFile = Join-Path $privateDirectory 'network.env'
    $metadata = @(('RIJKSDIENST_GOVERN_HOST=' + $containerName), ('RIJKSDIENST_GOVERN_NETWORK=' + $networkName))
    [System.IO.File]::WriteAllLines($networkFile, $metadata, (New-Object System.Text.UTF8Encoding($false)))

    $governConnected = $governBase + @('--env-file', $readerFile, '--env-file', $networkFile, '-f', (Join-Path $projectRoot 'compose.govern.yaml'))
    Write-Host 'Alleen de Govern-webapp met PostgreSQL-ondersteuning bouwen en verbinden...'
    Invoke-Docker -Arguments ($governConnected + @('build', 'app'))
    Invoke-Docker -Arguments ($governConnected + @('up', '-d', '--no-deps', '--wait', '--wait-timeout', '120', 'app'))

    Write-Host 'De leesverbinding via de echte Govern-adapter controleren...'
    $checkCode = [System.IO.File]::ReadAllText((Join-Path $projectRoot 'scripts/check-govern-connection.php'))
    $OutputEncoding = New-Object System.Text.UTF8Encoding($false)
    Invoke-Docker -Arguments ($governConnected + @('exec', '-T', 'app', 'php')) -InputText $checkCode

    Write-Host ''
    Write-Host 'Klaar. Open uw bestaande Govern-venster, ververs en kies rijksdienst - public - PostgreSQL.'
    Write-Host 'Laravel schrijft de datasets; Govern gebruikt uitsluitend de afzonderlijke leesverbinding.'
    Write-Host 'Na een gewone Govern-start met start.cmd: voer deze koppeling opnieuw uit om de Rijksdienst-verbinding te activeren.'
}
catch {
    Write-Error -Message $_.Exception.Message -ErrorAction Continue
    exit 1
}
finally {
    $OutputEncoding = $previousOutputEncoding
    Pop-Location
}
