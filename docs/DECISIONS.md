# Decisions

## Reset Scope

The utility resets one non-multisite WordPress installation. It drops only the database tables whose names begin with the active installation's `$wpdb->prefix`; it never targets every table in the database.

The reset preserves the `em-nuclear-reset` plugin, the active theme, and its parent theme when the active theme is a child theme. Core WordPress files, `wp-config.php`, must-use plugins, and paths outside an explicit filesystem allowlist are also preserved.

## Operator Responsibility

The operator is responsible for backups and recovery readiness. The plugin will show irreversible-action warnings and require explicit confirmations, but will not verify a host backup or restrict execution by environment type.

## Safety Boundaries

Multisite is refused. Filesystem cleanup must resolve and inspect every candidate path, reject symlinks, and refuse paths outside the approved WordPress content directories.

## Confirmation and Logging

Future reset execution requires a typed `RESET <site-url>` phrase, a separate irreversible-action acknowledgement, a valid nonce, `manage_options`, and a single-operation lock. Operation records will be JSON files under `wp-content/em-nuclear-reset-logs`, which is preserved from cleanup. This milestone defines the record schema and recovery plan only; it does not write logs or execute resets.

## Development Environments

The primary validation targets are Local and WP Engine development/staging environments. `wp-env` is included for isolated Docker-based development; it is optional and does not replace host-specific validation.