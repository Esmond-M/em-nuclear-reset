# Development

## Prerequisites

- PHP 8.2 is used by the local development site.
- Composer is used for PHP quality tools and unit tests.
- Node.js 20 or later is used for the encoding check and `wp-env`.
- Docker Desktop is required only for `wp-env`.

## Install Dependencies

```powershell
composer install
npm install
```

## Quality Checks

```powershell
npm run lint:encoding
composer run lint
composer run test
npm run check
```

## Isolated WordPress

```powershell
npm run env:start
npm run env:stop
npm run env:destroy
```

`env:clean` and `env:destroy` remove Docker-managed development data. They do not operate on the Local site, but use them deliberately.