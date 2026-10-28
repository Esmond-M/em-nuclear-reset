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
	Compress-Archive -LiteralPath $stagedPluginDirectory -DestinationPath $archivePath -CompressionLevel Optimal -Force

	Write-Output "Created $archivePath"
} finally {
	Remove-Item -LiteralPath $stagingDirectory -Recurse -Force -ErrorAction SilentlyContinue
}