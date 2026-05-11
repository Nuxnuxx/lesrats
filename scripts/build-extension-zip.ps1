# Build the LesRats Chrome extension zip for beta distribution.
#
# Produces dist/lesrats-extension-v{version}.zip with:
#   - The full browser-extension/ tree
#   - A beta-friendly README.md at the root (replaces the dev INSTALLATION.md)
#   - Dev-only files excluded (generate-icons.html, icons/README.md, etc.)
#
# Usage from the repo root:
#   pwsh scripts/build-extension-zip.ps1
#   or:  powershell -ExecutionPolicy Bypass -File scripts/build-extension-zip.ps1

$ErrorActionPreference = 'Stop'

$repoRoot   = Split-Path -Parent $PSScriptRoot
$srcDir     = Join-Path $repoRoot 'browser-extension'
$distDir    = Join-Path $repoRoot 'dist'
$stagingDir = Join-Path $repoRoot 'dist\_staging'

if (-not (Test-Path $srcDir)) {
    throw "Source folder not found: $srcDir"
}

# Read version from manifest
$manifestPath = Join-Path $srcDir 'manifest.json'
$manifest     = Get-Content $manifestPath -Raw | ConvertFrom-Json
$version      = $manifest.version
$outZip       = Join-Path $distDir "lesrats-extension-v$version.zip"

Write-Host "Build LesRats extension v$version" -ForegroundColor Cyan

# Clean / recreate staging
if (Test-Path $stagingDir) {
    Remove-Item -Recurse -Force $stagingDir
}
New-Item -ItemType Directory -Force -Path $stagingDir | Out-Null
New-Item -ItemType Directory -Force -Path $distDir    | Out-Null

# Copy extension into staging, excluding dev-only files
$excludePatterns = @(
    'INSTALLATION.md',            # replaced by our beta README
    'BETA-README.md',             # copied separately as README.md
    'icons\generate-icons.html',  # dev helper
    'icons\README.md'             # dev notes
)

Get-ChildItem -Path $srcDir -Recurse | ForEach-Object {
    $rel = $_.FullName.Substring($srcDir.Length + 1)

    foreach ($pattern in $excludePatterns) {
        if ($rel -ieq $pattern) { return }
    }

    $dest = Join-Path $stagingDir $rel
    if ($_.PSIsContainer) {
        New-Item -ItemType Directory -Force -Path $dest | Out-Null
    } else {
        $destDir = Split-Path -Parent $dest
        if (-not (Test-Path $destDir)) {
            New-Item -ItemType Directory -Force -Path $destDir | Out-Null
        }
        Copy-Item -Path $_.FullName -Destination $dest -Force
    }
}

# Add beta README as README.md at the zip root
$betaReadme = Join-Path $srcDir 'BETA-README.md'
if (Test-Path $betaReadme) {
    Copy-Item -Path $betaReadme -Destination (Join-Path $stagingDir 'README.md') -Force
} else {
    Write-Warning "BETA-README.md not found - the zip will ship without an install guide."
}

# Delete existing zip if present
if (Test-Path $outZip) {
    Remove-Item -Force $outZip
}

# Build the zip (contents at the root, not inside a _staging subfolder)
Compress-Archive -Path (Join-Path $stagingDir '*') -DestinationPath $outZip -Force

# Cleanup
Remove-Item -Recurse -Force $stagingDir

# Summary
$size = (Get-Item $outZip).Length
$sizeKb = [Math]::Round($size / 1KB, 1)
Write-Host ""
Write-Host "OK: $outZip ($sizeKb KB)" -ForegroundColor Green
Write-Host ""
Write-Host "Send this zip to beta testers. The README.md inside the zip" -ForegroundColor Yellow
Write-Host "explains how to install it." -ForegroundColor Yellow
