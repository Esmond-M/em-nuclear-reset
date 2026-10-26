# Project Status

## Current Milestone

Milestone 1: read-only preflight foundation.

## Runtime Context

- The development site runs in Local by WP Engine.
- The Windows PHP executable is development tooling only and must not be treated as the Local runtime.
- The isolated `wp-env` environment is configured at `http://localhost:18080`.

## Decisions

- Plugin identity is `em-nuclear-reset` / `EM Nuclear Reset`.
- The utility may run on any environment type, including production.
- Multisite is out of scope and must be refused.
- A reset targets only tables with the current installation's `$wpdb->prefix`.
- The reset plugin and active theme, including a parent theme where applicable, are preserved.
- Backup creation and verification are the operator's responsibility.
- Filesystem cleanup must not follow symlinks or affect paths outside an explicit allowlist.

## Not Implemented

- No reset, filesystem cleanup, or destructive behavior exists.
- The preflight service only lists tables matching the current `$wpdb->prefix` and reports missing core tables.
- The admin-only preflight page displays collected facts and refuses multisite. It has no destructive controls.
- Filesystem discovery classifies immediate uploads, plugin, and theme paths as preserved, eligible, or blocked without recursion or mutation.

## Next Task

Add confirmation-state design and durable operation logging before any reset execution path is considered.