# EM Nuclear Reset

EM Nuclear Reset is a developer-oriented utility for resetting one non-multisite WordPress installation to a clean core schema while preserving the reset plugin and active theme.

## Status

The reset executor runs from both the WordPress Tools page and WP-CLI. The wp-admin flow requires an administrator session, a valid nonce, the exact typed confirmation phrase, and an irreversible-action acknowledgement; it restores the initiating administrator with its existing password. The WP-CLI flow creates the administrator supplied to the command.

## Package For Installation

On Windows, create the installable plugin ZIP with:

```powershell
npm run package:plugin
```

This writes `dist/em-nuclear-reset.zip`. The archive contains only the plugin bootstrap, `includes/`, and this README under an `em-nuclear-reset/` root directory. It excludes development dependencies, Composer tools, tests, Docker configuration, documentation, and local build artifacts.

Install the ZIP through WordPress, or extract it to `wp-content/plugins/`. Do not deploy the repository working tree or `node_modules/` to a remote server.

## Documentation

- `docs/PROJECT_STATUS.md` records current progress and validation results.
- `docs/DECISIONS.md` records the agreed safety and reset boundaries.
- `docs/DEVELOPMENT.md` covers local quality checks and the optional Docker environment.

## License

GPL-2.0-or-later.