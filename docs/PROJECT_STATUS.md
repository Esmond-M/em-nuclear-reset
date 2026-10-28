# Project Status

## Current Milestone

Milestone 2: reset executor implemented and verified in isolated Docker.

## Runtime Context

- The development site runs in Local by WP Engine.
- The Windows PHP executable is development tooling only and must not be treated as the Local runtime.
- The isolated `wp-env` environment is verified with WordPress 6.7 and the plugin active at `http://localhost:18080`.

## Decisions

- Plugin identity is `em-nuclear-reset` / `EM Nuclear Reset`.
- The utility may run on any environment type, including production.
- Multisite is out of scope and must be refused.
- A reset targets only tables with the current installation's `$wpdb->prefix`.
- The reset plugin and active theme, including a parent theme where applicable, are preserved.
- Backup creation and verification are the operator's responsibility.
- Filesystem cleanup must not follow symlinks or affect paths outside an explicit allowlist.

## Completed

- Read-only database and filesystem preflight, including prefix-scoped table discovery and symlink protection.
- Admin preflight page, confirmation contract, operation log, and single-operation lock.
- WP-CLI-only reset executor with guarded confirmation, approved filesystem cleanup, prefix-scoped table dropping, `wp_install()` recovery, preserved theme/plugin restoration, and post-reset verification.
- Isolated `wp-env` destructive integration run: the prefixed fixture table, fixture post, and upload file were removed; the unrelated-prefix table and marker were retained; the rebuilt administrator has `manage_options`; `em-nuclear-reset` and `twentytwentyfive` were restored; all 10 required auto-increment columns were present; all completion log phases were written; and the lock was released.
- Docker failure-path coverage: a symlinked plugin entry blocked execution before either an eligible file or a prefixed fixture table changed; deliberate interruptions after `started`, `filesystem_complete`, and `database_dropped` each wrote a `failed` record and released the lock.
- Browser verification: the recreated `emresetadmin` account successfully logged in to the Docker `/wp-admin/` dashboard.