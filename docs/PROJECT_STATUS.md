# Project Status

## Current Milestone

Milestone 0: repository and safety-model discovery.

## Decisions

- Plugin identity is `em-nuclear-reset` / `EM Nuclear Reset`.
- The utility may run on any environment type, including production.
- Multisite is out of scope and must be refused.
- A reset targets only tables with the current installation's `$wpdb->prefix`.
- The reset plugin and active theme, including a parent theme where applicable, are preserved.
- Backup creation and verification are the operator's responsibility.
- Filesystem cleanup must not follow symlinks or affect paths outside an explicit allowlist.

## Not Implemented

- No reset, database, filesystem, admin UI, or destructive behavior exists.
- No dependencies have been installed.

## Next Task

Create the repository quality baseline, development environment configuration, and project decision records before implementing a read-only preflight.