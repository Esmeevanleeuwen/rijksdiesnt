"""Check the PowerShell Govern connection flow without touching Docker or real profiles.

Windows CI uses Windows PowerShell 5.1; other machines can select POWERSHELL_EXE.
The parent integration test separately exercises real Govern and PostgreSQL.
"""
from pathlib import Path
import json
import os
import shutil
import subprocess
import tempfile

source = Path(__file__).resolve().parent / 'connect-govern.ps1'
pwsh = os.environ.get('POWERSHELL_EXE') or shutil.which('powershell') or shutil.which('pwsh')
if not pwsh:
    raise SystemExit('Install PowerShell or set POWERSHELL_EXE to run these checks.')
base = Path(tempfile.mkdtemp(prefix='Rijksdienst Govern connection '))
mock = base / 'mock.ps1'
mock.write_text('''
function global:docker {
    $arguments = @($args)
    $stdinText = $input | Out-String
    Add-Content -LiteralPath $env:CHECK_LOG -Value (ConvertTo-Json -InputObject $arguments -Compress)
    $global:LASTEXITCODE = 0
    $command = $arguments -join ' '
    if ($arguments[0] -eq 'info') {
        if ($env:CHECK_CASE -eq 'engine-off') { $global:LASTEXITCODE=1; return }
        return 'linux'
    }
    if ($command.Contains('ps --status running --services')) {
        if ($env:CHECK_CASE -eq 'govern-stopped') { return 'database' }
        return @('database', 'gateway', 'app')
    }
    if ($command.Contains('migrate --force') -and $env:CHECK_CASE -eq 'migration-failure') { $global:LASTEXITCODE=13; return }
    if ($command.Contains('ps -q db')) { return ('f' * 64) }
    if ($arguments[0] -eq 'inspect') {
        if ($arguments[2] -eq '{{.Name}}') { return '/rijksdienst-db-1' }
        if ($env:CHECK_CASE -eq 'ambiguous-network') { return '{"rijksdienst_default":{},"other_default":{}}' }
        return '{"rijksdienst_default":{}}'
    }
    if ($arguments[0] -eq 'network') { return @('rijksdienst_default', 'other_default', 'govern_default') }
    if ($command.Contains('govern:prepare-reader')) {
        if ($env:CHECK_CASE -eq 'reader-failure') { $global:LASTEXITCODE=23; return }
        $path=Join-Path $env:CHECK_RIJKS 'backend/storage/app/private/govern'
        New-Item -ItemType Directory -Path $path -Force | Out-Null
        [IO.File]::WriteAllText((Join-Path $path 'reader.env'), 'RIJKSDIENST_GOVERN_READ_PASSWORD=synthetic-reader-secret', (New-Object Text.UTF8Encoding($false)))
    }
    if ($command.Contains('build app') -and $env:CHECK_CASE -eq 'build-failure') { $global:LASTEXITCODE=19; return }
    if ($command.Contains('exec -T app php')) {
        if (-not $stdinText.Contains('<?php')) { $global:LASTEXITCODE=20; return }
        if ($env:CHECK_CASE -eq 'verification-failure') { $global:LASTEXITCODE=27; return }
        return '{"status":"connected","database":"rijksdienst","tables":8}'
    }
}
& (Join-Path $env:CHECK_RIJKS 'scripts/connect-govern.ps1') -GovernPath $env:CHECK_GOVERN
exit $LASTEXITCODE
''')
cases = ['success', 'missing-postgres-adapter', 'govern-stopped', 'migration-failure',
         'ambiguous-network', 'reader-failure', 'build-failure', 'verification-failure', 'engine-off']
try:
    for case in cases:
        folder = base / case
        root, govern = folder / 'Rijksdienst with spaces', folder / 'Govern with spaces'
        (root / 'scripts').mkdir(parents=True)
        (root / 'backend/vendor').mkdir(parents=True)
        (govern / 'app/src').mkdir(parents=True)
        shutil.copy2(source, root / 'scripts/connect-govern.ps1')
        (root / 'compose.yaml').write_text('name: rijksdienst\n')
        (root / 'backend/.env').write_text('APP_KEY=preserve-existing-test-key\n')
        (root / 'backend/vendor/autoload.php').write_text('<?php\n')
        (root / 'scripts/check-govern-connection.php').write_text('<?php echo "test";\n')
        (govern / 'compose.yaml').write_text('name: govern\n')
        existing = 'DB_PASSWORD=synthetic-govern-password\nCOMPOSE_PROJECT_NAME=isolated_govern\n'
        (govern / '.env').write_text(existing)
        if case != 'missing-postgres-adapter':
            (govern / 'app/src/PostgresAdapter.php').write_text('<?php\n')
        log = folder / 'commands.jsonl'
        environment = dict(os.environ, CHECK_LOG=str(log), CHECK_CASE=case, CHECK_RIJKS=str(root), CHECK_GOVERN=str(govern))
        # User host settings must not influence these mocked fixtures.
        environment.pop('CODEX_PROXY_CERT', None)
        result = subprocess.run([pwsh, '-NoLogo', '-NoProfile', '-File', str(mock)], env=environment,
                                capture_output=True, text=True, timeout=20)
        calls = [json.loads(line) for line in log.read_text(encoding='utf-8-sig').splitlines()] if log.exists() else []
        commands = [' '.join(call) for call in calls]
        success = case == 'success'
        assert (result.returncode == 0) == success, (case, result.stdout, result.stderr)
        assert ('Klaar.' in result.stdout) == success, (case, result.stdout)
        assert 'synthetic-reader-secret' not in result.stdout + result.stderr
        assert (govern / '.env').read_text() == existing
        assert (root / 'backend/.env').read_text() == 'APP_KEY=preserve-existing-test-key\n'
        assert not any(command in ['down', 'rm'] for call in calls for command in call)
        assert not any(argument in ['postgres', 'postgres-access'] for call in calls for argument in call)
        if success:
            network = (root / 'backend/storage/app/private/govern/network.env').read_text()
            assert 'RIJKSDIENST_GOVERN_HOST=rijksdienst-db-1' in network
            assert 'RIJKSDIENST_GOVERN_NETWORK=rijksdienst_default' in network
            assert len(network.splitlines()) == 2
            connected = [call for call in calls if str(root / 'compose.govern.yaml') in call]
            assert len(connected) == 3
            for call in connected:
                assert call.count('--env-file') == 3
                assert call[call.index('--project-directory')+1] == str(govern)
                assert str(govern / '.env') in call
            assert any('up -d --no-deps --wait --wait-timeout 120 app' in command for command in commands)
            assert any('exec -T app php' in command for command in commands)
        if case in ['missing-postgres-adapter', 'govern-stopped', 'engine-off']:
            assert not any('migrate' in command or 'govern:prepare-reader' in command for command in commands)
        if case in ['migration-failure', 'ambiguous-network']:
            assert not any('govern:prepare-reader' in command for command in commands)
        if case in ['reader-failure', 'build-failure']:
            assert not any('up -d --no-deps' in command for command in commands)
        print('PASS:', case)
    print('9 Govern connection script scenarios passed with mocked Docker. Windows/Docker Desktop itself was not exercised.')
finally:
    shutil.rmtree(base)
