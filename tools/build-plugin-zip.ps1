[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'

$pluginSlug = 'em-nuclear-reset'
$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$distributionDirectory = Join-Path $projectRoot 'dist'
$archivePath = Join-Path $distributionDirectory "$pluginSlug.zip"
$stagingDirectory = Join-Path ([System.IO.Path]::GetTempPath()) "$pluginSlug-package-$PID"
$stagedPluginDirectory = Join-Path $stagingDirectory $pluginSlug
$runtimeEntries = @(
	'em-nuclear-reset.php',
	'includes',
	'README.md'
)

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

try {
	New-Item -ItemType Directory -Path $stagedPluginDirectory -Force | Out-Null

	foreach ($entry in $runtimeEntries) {
		$sourcePath = Join-Path $projectRoot $entry

		if (-not (Test-Path -LiteralPath $sourcePath)) {
			throw "Required runtime entry is missing: $entry"
		}

		Copy-Item -LiteralPath $sourcePath -Destination $stagedPluginDirectory -Recurse -Force
	}

	New-Item -ItemType Directory -Path $distributionDirectory -Force | Out-Null
	Remove-Item -LiteralPath $archivePath -Force -ErrorAction SilentlyContinue
	$archive = [System.IO.Compression.ZipFile]::Open(
		$archivePath,
		[System.IO.Compression.ZipArchiveMode]::Create
	)

	try {
		Get-ChildItem -LiteralPath $stagedPluginDirectory -File -Recurse | ForEach-Object {
			$relativePath = $_.FullName.Substring( $stagedPluginDirectory.Length ).TrimStart( '\', '/' )
			$entryName    = ( $pluginSlug + '/' + $relativePath ).Replace( '\', '/' )

			[System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
				$archive,
				$_.FullName,
				$entryName,
				[System.IO.Compression.CompressionLevel]::Optimal
			) | Out-Null
		}
	} finally {
		$archive.Dispose()
	}

	$verificationArchive = [System.IO.Compression.ZipFile]::OpenRead( $archivePath )

	try {
		$entryNames = @( $verificationArchive.Entries | ForEach-Object { $_.FullName } )

		if ( $entryNames | Where-Object { $_.Contains( '\' ) } ) {
			throw 'ZIP entries must use forward-slash path separators.'
		}

		if ( $entryNames -notcontains "$pluginSlug/em-nuclear-reset.php" ) {
			throw 'ZIP archive does not contain the plugin bootstrap file.'
		}
	} finally {
		$verificationArchive.Dispose()
	}

	Write-Output "Created $archivePath"
} finally {
	Remove-Item -LiteralPath $stagingDirectory -Recurse -Force -ErrorAction SilentlyContinue
}