<#
    Builds dist/db_maintenance.zip, ready to upload at Plugins > Add New > Upload.

    The zip always has a single top-level "db_maintenance" folder, which is what
    makes WordPress replace the installed copy instead of adding a second one.

    Run from anywhere:  powershell -ExecutionPolicy Bypass -File build-zip.ps1
#>

$ErrorActionPreference = 'Stop'

$root    = $PSScriptRoot
$folder  = 'db_maintenance'
$dist    = Join-Path $root 'dist'
$staging = Join-Path ([System.IO.Path]::GetTempPath()) ("dbcern_build_" + [guid]::NewGuid().ToString('N'))
$target  = Join-Path $staging $folder
$zipPath = Join-Path $dist "$folder.zip"

# Everything the plugin needs at runtime, and nothing else.
$include = @(
    'db_maintenance.php',
    'includes',
    'template',
    'img'
)

New-Item -ItemType Directory -Path $target -Force | Out-Null
New-Item -ItemType Directory -Path $dist -Force | Out-Null

foreach ($item in $include) {
    $source = Join-Path $root $item
    if (-not (Test-Path $source)) {
        throw "Missing from the plugin: $item"
    }
    Copy-Item -Path $source -Destination $target -Recurse -Force
}

if (Test-Path $zipPath) {
    Remove-Item $zipPath -Force
}

# Written entry by entry rather than with Compress-Archive: Windows PowerShell
# writes "a\b.php" into the zip, and the ZIP format requires "a/b.php". Linux
# hosts unpack the backslash version as one oddly named file instead of a
# folder, so the plugin fails to install.
Add-Type -AssemblyName System.IO.Compression | Out-Null
Add-Type -AssemblyName System.IO.Compression.FileSystem | Out-Null

$stream  = [System.IO.File]::Open($zipPath, [System.IO.FileMode]::Create)
$archive = New-Object System.IO.Compression.ZipArchive($stream, [System.IO.Compression.ZipArchiveMode]::Create)

try {
    $prefix = (Resolve-Path $staging).Path.TrimEnd('\') + '\'
    foreach ($file in Get-ChildItem -Path $target -Recurse -File | Sort-Object FullName) {
        $relative  = $file.FullName.Substring($prefix.Length)
        $entryName = $relative -replace '\\', '/'
        $entry     = $archive.CreateEntry($entryName, [System.IO.Compression.CompressionLevel]::Optimal)

        $entryStream = $entry.Open()
        $fileStream  = [System.IO.File]::OpenRead($file.FullName)
        try   { $fileStream.CopyTo($entryStream) }
        finally { $fileStream.Dispose(); $entryStream.Dispose() }
    }
}
finally {
    $archive.Dispose()
    $stream.Dispose()
}

Remove-Item $staging -Recurse -Force

# Report what was built.
$version = (Select-String -Path (Join-Path $root 'db_maintenance.php') -Pattern '^\s*\*\s*Version:\s*(.+)$').Matches[0].Groups[1].Value.Trim()
$size    = [math]::Round((Get-Item $zipPath).Length / 1KB, 1)

Write-Output "Built $zipPath"
Write-Output "Plugin version $version, $size KB"
