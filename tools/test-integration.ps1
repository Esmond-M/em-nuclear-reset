[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'

$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$fixtureSource = '/var/www/html/wp-content/plugins/em-nuclear-reset/tests/integration/fixtures/wsal-like-hook'
$fixtureTarget = '/var/www/html/wp-content/plugins/wsal-like-hook'

function Invoke-WpEnvCli {
	param(
		[Parameter(Mandatory = $true)]
		[string[]]$Arguments
	)

	& npx wp-env run cli -- @Arguments

	if ($LASTEXITCODE -ne 0) {
		throw "wp-env CLI command failed: $($Arguments -join ' ')"
	}
}

Push-Location $projectRoot

try {
	& npm run env:start

	if ($LASTEXITCODE -ne 0) {
		throw 'Unable to start the isolated wp-env environment.'
	}

	Invoke-WpEnvCli -Arguments @(
		'sh',
		'-c',
		"rm -rf $fixtureTarget; mkdir -p $fixtureTarget; cp -R $fixtureSource/. $fixtureTarget/"
	)
	Invoke-WpEnvCli -Arguments @( 'wp', 'plugin', 'activate', 'wsal-like-hook' )
	Invoke-WpEnvCli -Arguments @(
		'sh',
		'-c',
		"EM_NUCLEAR_RESET_ADMIN_PASSWORD='IntegrationPass!123' wp em-nuclear-reset execute --execute=true --confirm='RESET http://localhost:18080' --acknowledge=true --site-title='Integration Reset' --admin-user=emresetintegration --admin-email=emresetintegration@example.test --admin-password-env=EM_NUCLEAR_RESET_ADMIN_PASSWORD --yes"
	)
	Invoke-WpEnvCli -Arguments @( 'sh', '-c', "test ! -e $fixtureTarget" )
	Invoke-WpEnvCli -Arguments @( 'wp', 'plugin', 'is-active', 'em-nuclear-reset' )
	Invoke-WpEnvCli -Arguments @(
		'wp',
		'eval',
		"exit( user_can( get_user_by( 'login', 'emresetintegration' ), 'manage_options' ) ? 0 : 1 );"
	)

	Write-Output 'Integration reset regression test passed.'
} finally {
	Pop-Location
}