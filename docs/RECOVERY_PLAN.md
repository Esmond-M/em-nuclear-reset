# Reset Recovery Plan

## Preconditions

- The current user has `manage_options`.
- The installation is not multisite.
- The typed confirmation is exactly `RESET <site-url>` and the irreversible-action acknowledgement is checked.
- A valid nonce is present and no other reset operation holds the execution lock.
- The preflight preview has completed and contains no blocked database or filesystem scope.

## Preserved Components

- `em-nuclear-reset`
- Active theme and parent theme, when applicable
- WordPress core, `wp-config.php`, must-use plugins, host-managed files, and `wp-content/em-nuclear-reset-logs`

## Execution Sequence

1. Create a durable operation record and acquire the single-operation lock.
2. Record the approved scoped table count in the operation log.
3. Remove only eligible filesystem entries after symlink checks pass.
4. Drop only the approved tables whose names begin with the active `$wpdb->prefix`.
5. Recreate the WordPress core schema using the same prefix.
6. Create the administrator identity supplied during confirmation.
7. Verify core tables, required auto-increment columns, preserved components, administrator access, and migration-plugin readiness.
8. Mark the log as complete only after every verification succeeds; otherwise record the failed phase and recovery guidance.

## Current Coordinator Boundary

`ResetCoordinator` validates multisite, capability, nonce, typed confirmation, and concurrent-operation lock conditions, then builds an immutable operation plan. `ResetExecutor` is callable only from `wp em-nuclear-reset execute`; it acquires a filesystem lock, appends operation records, removes eligible paths, drops fresh prefix-scoped tables, calls `wp_install()`, restores the preserved theme/plugin, and verifies the recovered site.

## WP-CLI Dry Run

`wp em-nuclear-reset plan` prints the current scoped plan without changing files or database tables. `wp em-nuclear-reset execute` requires `--execute=true`, the exact typed phrase, `--acknowledge=true`, administrator replacement details, and WP-CLI's `--yes` confirmation. The command is the only supported execution surface; a browser request will not reset a site.

## Test Requirements

- Run destructive integration tests only in the isolated Docker environment.
- Seed a custom prefixed table, normal plugin/theme entries, uploads, a symlink candidate, and an unrelated-prefix table.
- Verify that only approved-prefix tables and eligible paths are changed.
- Simulate interruption after each recoverable execution phase and confirm the operation log reports the incomplete phase.
- Confirm the recreated administrator can access `/wp-admin` and required core schema attributes exist.

## Completed Docker Integration Run

The isolated `wp-env` environment completed a destructive reset at `http://localhost:18080`. The test seeded a `wp_`-prefixed fixture table, an unrelated-prefix fixture table, a published post, and an upload file. The reset removed the prefixed table, post, and upload; retained the unrelated-prefix table and marker; recreated an administrator with `manage_options`; restored `em-nuclear-reset` and `twentytwentyfive`; restored all 10 required auto-increment columns; wrote `started`, `filesystem_complete`, `database_dropped`, and `complete` log phases; and released the operation lock.

## Completed Docker Failure-Path Tests

An immediate plugin-directory symlink blocked execution before either an eligible plugin file or a prefixed fixture table was changed. Docker-only must-use hooks then interrupted execution after `started`, `filesystem_complete`, and `database_dropped`. Each operation log recorded all completed phases followed by `failed`, and each lock was released. The rebuilt `emresetadmin` account also logged in successfully to the Docker `/wp-admin/` dashboard.