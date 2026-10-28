# EM Nuclear Reset

EM Nuclear Reset is a developer-oriented utility for resetting one non-multisite WordPress installation to a clean core schema while preserving the reset plugin and active theme.

## Scope And Safety

The reset drops only tables with the current installation's `$wpdb->prefix`. It preserves `em-nuclear-reset`, the active theme and parent theme, WordPress core, `wp-config.php`, must-use plugins, host-managed files, and `wp-content/em-nuclear-reset-logs`.

The operator is responsible for backups and recovery readiness. Multisite is not supported. Filesystem cleanup uses an explicit allowlist, rejects symlinks, and refuses paths outside the approved WordPress content directories.

## Reset Execution

The reset executor runs from the WordPress Tools page and WP-CLI. Both require the exact `RESET <site-url>` confirmation phrase, an irreversible-action acknowledgement, a clean preflight with no blocked paths, and no active reset lock.

### WordPress Admin

Open **Tools -> EM Nuclear Reset** as an administrator. The form requires `manage_options`, a valid nonce, the confirmation phrase, and the acknowledgement checkbox. It restores the initiating administrator with the existing password and signs that user back in after reset.

### WP-CLI

Display the scoped plan without changes:

```powershell
wp em-nuclear-reset plan
```

Execute a reset:

```powershell
wp em-nuclear-reset execute --execute=true --confirm='RESET https://site.test' --acknowledge=true --site-title='Clean Site' --admin-user=admin --admin-email=admin@example.test --admin-password-env=EM_NUCLEAR_RESET_ADMIN_PASSWORD --yes
```

For WP-CLI, set the replacement administrator password in the environment or a secret manager, then pass only its uppercase variable name through `--admin-password-env`. Never place the password directly in a command, script, CI log, or WP-CLI argument.

## Recovery And Logging

The executor acquires an exclusive lock, records the approved table count, drops approved prefix-scoped tables, rebuilds WordPress with `wp_install()`, restores the theme and plugin, removes eligible files, and verifies core tables, auto-increment columns, administrator access, and migration-plugin readiness.

Operation records are JSONL files under `wp-content/em-nuclear-reset-logs`. The plugin creates Apache `.htaccess` and `index.php` protections; configure equivalent web-server rules for Nginx or another server before resetting a remote site.

## Development

Prerequisites:

- Composer for PHP quality tools and unit tests
- Node.js 20 or later for encoding checks and `wp-env`
- Docker Desktop only for isolated `wp-env` validation

Install dependencies:

```powershell
composer install
npm install
```

Run checks:

```powershell
npm run check
```

Run the Docker reset regression suite:

```powershell
npm run test:integration
```

Use the isolated WordPress environment:

```powershell
npm run env:start
npm run env:stop
npm run env:destroy
```

The isolated site runs at `http://localhost:18080`; test services use port `18081`. `env:clean` and `env:destroy` remove Docker-managed data only, but are destructive within that environment.

## Validation

The reset flow is validated in isolated Docker with WordPress 6.7. Coverage includes normal reset behavior, filesystem symlink refusal, interruption logging and lock release, loaded third-party plugin hooks during schema rebuild, the restored administrator session reaching `/wp-admin/`, reset-plugin reactivation, required core schema attributes, and denied direct HTTP access to operation logs.

## Package For Installation

On Windows, create the installable plugin ZIP with:

```powershell
npm run package:plugin
```

This writes `dist/em-nuclear-reset.zip`. The archive contains only the plugin bootstrap, `includes/`, and this README under an `em-nuclear-reset/` root directory. It excludes development dependencies, Composer tools, tests, Docker configuration, documentation, and local build artifacts.

Install the ZIP through WordPress, or extract it to `wp-content/plugins/`. Do not deploy the repository working tree or `node_modules/` to a remote server.

## License

GPL-2.0-or-later.