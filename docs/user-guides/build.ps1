# Regenerates the PDF user guides in public/user-guides/ from the HTML
# sources in this folder, using headless Microsoft Edge (or Chrome):
#
#   powershell -ExecutionPolicy Bypass -File docs/user-guides/build.ps1
#
# Both guides share the same "Μέρος 1 · Γενικές Οδηγίες" section — keep the
# two copies in sync when editing it.
$here = Split-Path -Parent $MyInvocation.MyCommand.Path
$out = (Resolve-Path (Join-Path $here '..\..\public\user-guides')).Path
$browser = @(
    "${env:ProgramFiles(x86)}\Microsoft\Edge\Application\msedge.exe",
    "$env:ProgramFiles\Microsoft\Edge\Application\msedge.exe",
    "$env:ProgramFiles\Google\Chrome\Application\chrome.exe"
) | Where-Object { Test-Path $_ } | Select-Object -First 1
if (-not $browser) { throw 'Microsoft Edge or Google Chrome is required.' }

foreach ($name in 'odigos-kidemona', 'odigos-ekpaideftikou') {
    $src = (Resolve-Path (Join-Path $here "$name.html")).Path
    $pdf = Join-Path $out "$name.pdf"
    $url = 'file:///' + ($src -replace '\\', '/')
    Start-Process -FilePath $browser -Wait -NoNewWindow -ArgumentList @(
        '--headless=new', '--disable-gpu', '--no-pdf-header-footer',
        "--print-to-pdf=`"$pdf`"", "`"$url`""
    )
    Write-Host "Built $pdf"
}
