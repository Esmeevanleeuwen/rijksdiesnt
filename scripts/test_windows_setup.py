"""Exercise the Windows startup flow without building images or changing real .env files.

Uses Windows PowerShell 5.1 on Windows CI, or pwsh where available elsewhere.
Docker and HTTP are mocked; every case runs in its own PowerShell process.
"""
from pathlib import Path
import tempfile, shutil, subprocess, json, os
source=Path(__file__).resolve().parent/'setup.ps1'
pwsh=os.environ.get('POWERSHELL_EXE') or shutil.which('powershell') or shutil.which('pwsh')
if not pwsh:
    raise SystemExit('Install PowerShell or set POWERSHELL_EXE to run these startup checks.')
base=Path(tempfile.mkdtemp(prefix='rijksdienst Windows checks '))
mock=base/'mock.ps1'
mock.write_text('''
function global:docker {
    $arguments = @($args)
    Add-Content -LiteralPath $env:CHECK_LOG -Value (ConvertTo-Json -InputObject $arguments -Compress)
    $global:LASTEXITCODE = 0
    $command = $arguments -join ' '
    if ($arguments[0] -eq 'info') {
        if ($env:CHECK_CASE -eq 'engine-off') { $global:LASTEXITCODE=1; return }
        if ($env:CHECK_CASE -eq 'windows-engine') { return 'windows' }
        return 'linux'
    }
    if ($env:CHECK_CASE -eq 'composer-failure' -and $command.Contains('composer install')) { $global:LASTEXITCODE=17 }
}
function global:Invoke-WebRequest {
    param([switch]$UseBasicParsing, [string]$Uri, [int]$TimeoutSec)
    if ($env:CHECK_CASE -eq 'readiness-failure') { throw 'Not ready' }
    return [pscustomobject]@{StatusCode=200; Content='{"data":[],"meta":{"data_mode":"demo"}}'}
}
function global:Start-Sleep { param([int]$Seconds) }
if ($env:CHECK_CASE -eq 'missing-docker') {
    function global:Get-Command { param([string]$Name, [string]$ErrorAction) return $null }
}
& (Join-Path $env:CHECK_PROJECT 'scripts/setup.ps1')
exit $LASTEXITCODE
''')
cases=['new-install','existing-key','quoted-empty-key','missing-key','composer-failure','engine-off','windows-engine','missing-docker','readiness-failure']
for name in cases:
    root=base/(name+' with spaces');(root/'scripts').mkdir(parents=True);(root/'backend').mkdir()
    shutil.copy2(source,root/'scripts/setup.ps1')
    (root/'.env.example').write_text('DB_PASSWORD=local-development-only\n')
    (root/'backend/.env.example').write_text('APP_NAME=Rijksdienst\nAPP_KEY=\n')
    if name=='existing-key':
        (root/'backend/.env').write_text('APP_KEY=example-key-preserved\nCUSTOM_VALUE=keep\n')
        (root/'.env').write_text('CUSTOM_ROOT_VALUE=keep\n')
    elif name=='quoted-empty-key':(root/'backend/.env').write_text('APP_KEY=""\n')
    elif name=='missing-key':(root/'backend/.env').write_text('APP_NAME=Rijksdienst\n')
    env=dict(os.environ,CHECK_LOG=str(root/'commands.jsonl'),CHECK_CASE=name,CHECK_PROJECT=str(root))
    result=subprocess.run([pwsh,'-NoLogo','-NoProfile','-File',str(mock)],env=env,text=True,capture_output=True,timeout=20)
    calls=[json.loads(s) for s in (root/'commands.jsonl').read_text(encoding='utf-8-sig').splitlines()] if (root/'commands.jsonl').exists() else []
    commands=[' '.join(call) for call in calls]
    success=name in ['new-install','existing-key','quoted-empty-key','missing-key']
    assert (result.returncode==0)==success,(name,result.returncode,result.stdout,result.stderr)
    assert ('Klaar.' in result.stdout)==success,(name,result.stdout)
    if success:
        assert any('migrate --force' in c for c in commands),name
        assert any('npm ci --no-audit --no-fund' in c for c in commands),name
        assert any('up -d app frontend' in c for c in commands),name
        assert any('key:generate' in c for c in commands)==(name!='existing-key'),name
        assert all(call[2]==str(root/'compose.yaml') for call in calls if call[0]=='compose'),(name,calls)
    if name=='existing-key':
        assert (root/'backend/.env').read_text()=='APP_KEY=example-key-preserved\nCUSTOM_VALUE=keep\n'
        assert (root/'.env').read_text()=='CUSTOM_ROOT_VALUE=keep\n'
    if name=='composer-failure':assert not any('migrate' in c or 'npm ci' in c for c in commands)
    if name in ['engine-off','windows-engine','missing-docker']:assert not (root/'.env').exists()
    if name=='readiness-failure':assert any('logs --tail=40 app frontend' in c for c in commands)
    print('PASS:',name)
print('9 PowerShell setup scenarios passed with mocked Docker and HTTP. Docker Desktop itself was not exercised.')
shutil.rmtree(base)
