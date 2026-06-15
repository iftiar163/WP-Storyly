$sourceDir = $PSScriptRoot
$destZip = Join-Path -Path (Split-Path $sourceDir -Parent) -ChildPath "storyly-release.zip"

if (Test-Path $destZip) {
    Remove-Item $destZip -Force
}

Write-Host "Packaging the plugin..."
Get-ChildItem -Path $sourceDir -Exclude ".git", "build.ps1", ".gitignore" | Compress-Archive -DestinationPath $destZip -Force

Write-Host "Build complete! Clean plugin ZIP created at: $destZip"
