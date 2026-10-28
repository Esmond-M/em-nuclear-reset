# Decisions

## Reset Scope

The utility resets one non-multisite WordPress installation. It drops only the database tables whose names begin with the active installation's `$wpdb->prefix`; it never targets every table in the database.

The reset preserves the `em-nuclear-reset` plugin, the active theme, and its parent theme when the active theme is a child theme. Core WordPress files, `wp-config.php`, must-use plugins, and paths outside an explicit filesystem allowlist are also preserved.

## Operator Responsibility

The operator is responsible for backups and recovery readiness. The plugin will show irreversible-action warnings and require explicit confirmations, but will not verify a host backup or restrict execution by environment type.

## Safety Boundaries

Multisite is refused. Filesystem cleanup must resolve and inspect every candidate path, reject symlinks, and refuse paths outside the approved WordPress content directories.

## Confirmation and Logging

The admin screen is read-only. Reset execution requires WP-CLI, a typed `RESET <site-url>` phrase, a separate irreversible-action acknowledgement, a password supplied through an environment variable, and a single-operation lock. Shell access is the authorization boundary for WP-CLI execution; browser nonces and `manage_options` checks do not apply to the command. Operation records are JSONL files under `wp-content/em-nuclear-reset-logs`, which is preserved from cleanup and must be protected from direct web access by the host configuration.

## Remote Execution

The operator must use an account with deliberately restricted shell access. Set the replacement administrator password through the remote environment or secret manager, then pass only its uppercase variable name with `--admin-password-env`; never place the password itself in a shell command, script, CI log, or WP-CLI argument. Verify that the web server denies direct access to `wp-content/em-nuclear-reset-logs` before running a reset.

## Development Environments

The primary validation targets are Local and WP Engine development/staging environments. `wp-env` is included for isolated Docker-based development; it is optional and does not replace host-specific validation.