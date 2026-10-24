# Interstellar API — Phase 6

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

- `GET /api/v1/celestial-bodies`
- `GET /api/v1/celestial-bodies/{slug}`
- `GET /api/v1/missions`
- `GET /api/v1/missions/{slug}`

The original four `/api/...` routes remain compatibility aliases with unchanged names and behavior. Versioned route names have the `v1.` prefix. Both groups share controllers and API Resources. The frontend environment base still ends in `/api`; its clients append `/v1`. Do not put `/v1` into `NEXT_PUBLIC_API_BASE_URL`.

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

Format changed PHP with `php vendor/bin/pint --dirty --format agent`. Both legacy and v1 routes run through contract, empty/error, ordered itinerary, and CORS tests. Public keys/types are pinned independently in tests while API Resources remain the only public serializers.

The frontend's `PHASE_5.md` contains the complete implementation and validation report and both projects' file lists.

## Production deployment

Installed and validated: Laravel 13.38.0 on PHP 8.3.30. Provision PHP >=8.3 with required Laravel extensions and PDO SQLite, Composer, and a TLS web server/PHP-FPM. Set the web root to this repository's `public/` only. Do not use `artisan serve` for production or expose .env, storage, vendor, or database files.

Example configuration (replace hosts and path; inject a real secret key securely):

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.example.com
APP_KEY=<existing securely provisioned key>
DB_CONNECTION=sqlite
DB_DATABASE=/srv/interstellar-data/catalog.sqlite
FRONTEND_ORIGINS=https://explore.example.com
LOG_LEVEL=warning
CACHE_STORE=database
SESSION_DRIVER=database
QUEUE_CONNECTION=sync
```

Keep backend keys and private configuration exclusively on the server. No backend secret belongs in the frontend's NEXT_PUBLIC variables. Clear any conflicting DB_URL. Set CORS to exact production origins (scheme/host/port, no path or trailing slash); development localhost defaults must be replaced. CORS allows GET/HEAD/OPTIONS and Accept/Content-Type, with no credentials; it applies to both API route groups. The public catalog requires no authentication.

1. Install locked dependencies with `composer install --no-dev --optimize-autoloader --no-interaction` and verify `composer check-platform-reqs --no-dev`. Avoid the starter `composer setup` shortcut: it generates a key and migrates automatically. Preserve existing APP_KEY and .env; generate a key only for a new environment.
2. Mount persistent SQLite storage outside the release directory. Create the file only if absent. Grant the service user access to its directory for SQLite journals and to storage/bootstrap/cache; avoid world-writable permissions. Do not truncate existing files.
3. Before any migration or seed, inspect `php artisan config:show database.default` and `php artisan config:show database.connections.sqlite`. Verify the exact intended absolute persistent path and absence of a DB_URL override. Resolve stale config caches first, then recheck. Back up existing data before migration.
4. Run `php artisan migrate:status --no-interaction`, then `php artisan migrate --force --no-interaction`. Seed first provisioning deliberately using `php artisan db:seed --class=ExplorationSeeder --force --no-interaction`. The seed upserts curated slugs, preserving unrelated rows but overwriting curated values; do not seed blindly on every release. Never use migrate:fresh, db:wipe, or destructive reset/rollback during normal deployment.
5. Cache final configuration and routes with `php artisan config:cache --no-interaction`, `php artisan route:cache --no-interaction`, and `php artisan view:cache --no-interaction`. Recheck config and `php artisan route:list --path=api --no-interaction`. Serve under the supervised production web server, rotate private logs, and configure TLS/proxy trust for the actual infrastructure.
6. Deploy this backward-compatible backend before the v1 frontend. Check /up for liveness and both v1 collections/details for real catalog readiness, CORS preflight, rejected origins, and safe errors. Frontend production uses a public HTTPS base such as `https://api.example.com/api` set before its build. No queue worker is required for these read routes.

Persistent SQLite volume and backups are operator responsibilities. Ephemeral containers lose unmounted files, and separate replicas with separate files diverge. Use SQLite's online backup facility or a consistent stopped-service snapshot; copying just a live database file may miss WAL/journal state. Define retention and test restores to a separate file. No production provider, persistent volume, or backup restore was verified in this phase.

Backend PHPUnit uses guarded in-memory SQLite. For real-browser checks run `node tests/run-browser.mjs` in the separate frontend repository; it verifies and migrates only a newly created temporary SQLite file, uses a disposable Chrome profile, and stops its own servers. It does not touch this development database. The frontend's PHASE_6.md records the exact v1 contract, full deployment sequence, both repositories' change inventory, and validation outcomes.
