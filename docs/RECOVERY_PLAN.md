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

## Planned Execution Sequence

1. Create a durable operation record and acquire the single-operation lock.
2. Record the approved prefixed table list and filesystem preview in the operation log.
3. Remove only eligible filesystem entries, reporting each result to the operation log.
4. Drop only the approved tables whose names begin with the active `$wpdb->prefix`.
5. Recreate the WordPress core schema using the same prefix.
6. Create the administrator identity supplied during confirmation.
7. Verify core tables, required auto-increment columns, preserved components, administrator access, and migration-plugin readiness.
8. Mark the log as complete only after every verification succeeds; otherwise record the failed phase and recovery guidance.

## Test Requirements

- Run destructive integration tests only in the isolated Docker environment.
- Seed a custom prefixed table, normal plugin/theme entries, uploads, a symlink candidate, and an unrelated-prefix table.
- Verify that only approved-prefix tables and eligible paths are changed.
- Simulate interruption after each execution phase and confirm the operation log reports the incomplete phase.
- Confirm the recreated administrator can access `/wp-admin` and required core schema attributes exist.