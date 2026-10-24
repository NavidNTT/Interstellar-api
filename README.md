# Interstellar API — Phase 5

Laravel 13 read-only catalog API. This isolated backend uses `database/database.sqlite` for development. It stores no player mission progress and provides no authentication or write API.

## Local setup

PHP 8.3+ with PDO SQLite and Composer are required. Dependencies and a development environment already exist in this workspace. Preserve `.env` and the database when updating.

For a clean checkout, install dependencies with `composer install`; create `.env` from `.env.example` only if missing, generate an application key only for a new environment, and create `database/database.sqlite` only if missing.

```dotenv
APP_ENV=local
APP_URL=http://localhost:8000
DB_CONNECTION=sqlite
DB_DATABASE=C:/laragon/www/interstellar-api/database/database.sqlite
FRONTEND_ORIGINS=http://localhost:3000,http://127.0.0.1:3000,http://localhost:3010,http://127.0.0.1:3010
```

Omit `DB_DATABASE` to use the application's own `database/database.sqlite`. No database credentials are needed. `FRONTEND_ORIGINS` is an exact-origin allowlist; the listed development origins are also its defaults. In production set exact deployed origins, `APP_ENV=production`, and `APP_DEBUG=false`. Never commit `.env`, SQLite data, or application keys.

```powershell
php artisan migrate --seed --no-interaction
php artisan serve --host=127.0.0.1 --port=8000 --no-interaction
```

Migrations are additive. Do not use `migrate:fresh` on data you need to retain or on unknown databases. Seeders upsert three curated bodies, five missions, and eight ordered objectives by stable slugs. They do not delete unrelated rows or create users.

## API

- `GET /api/celestial-bodies`
- `GET /api/celestial-bodies/{slug}`
- `GET /api/missions`
- `GET /api/missions/{slug}`

Collections and resources use `{ "data": ... }`. Only active records are exposed. Mission `destinations` contain `slug`, `body_slug`, `sequence`, `kind` (`visit` or `inspect`), and `objective`. Sequences start at 1. Database IDs, foreign keys, timestamps, and internal flags are not exposed.

Errors use `{ "error": { "code": "not_found", "message": "..." } }`, with 404 for unknown/invalid slugs, 405 for unsupported methods, 503 for failed database queries, and 500 for malformed catalog records or other internal errors. API exceptions never expose SQL, stack traces, or connection details, even with development debugging enabled.

Scientific measurements remain display strings to preserve units, notation, ranges, and unavailable-value labels. Numeric `scene_radius` and `scene_orbit_radius` retain representative geometry. Interesting facts are a JSON string array. Missions have many ordered destinations; each belongs to one mission and one body. Bodies have many destinations. No destination IDs are duplicated in the mission table.

## Validation

```powershell
php artisan route:list --path=api --no-interaction
php artisan migrate:status --no-interaction
php artisan test --compact
```

PHPUnit forces SQLite `:memory:`; its database lifecycle never touches the development SQLite file. Feature tests cover repeatable seeding, response shape, ordered destinations, empty data, unknown/inactive records, malformed content, database failure, and CORS.

Laravel Boost was installed because the starter's `AGENTS.md` required it. This directory has no Git repository, so Pint's `--dirty` option is unavailable; format selected changed PHP files with `php vendor/bin/pint --format agent <files>`.

The frontend's `PHASE_5.md` contains the complete implementation and validation report and both projects' file lists.
