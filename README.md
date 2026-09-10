# GameSentry

Discord-powered Riot match tracking with AI-generated notifications.

## Local development

Install PHP 8.4+ (including PDO SQLite, pcntl, mbstring, XML, curl, bcmath and zip), Composer 2, and Node 22. Docker is not required.

```bash
composer run setup
composer run dev
```

Setup installs dependencies, copies `.env.example` if needed, generates an application key, creates `database/database.sqlite` if missing, runs migrations and builds assets. Run setup for initial bootstrap; it regenerates the application key if run again. Existing environment files and databases are not replaced.

`composer run dev` delegates to Laravel 13's `php artisan dev`. Laravel manages the application server, Vite, database queue listener, Pail logs, and `schedule:work` in one terminal. Stop them with Ctrl+C. The scheduler dispatches watched-player polls at the configured interval; jobs are asynchronous, with database-backed cache locks and sessions. SQLite uses WAL and a five-second busy timeout to accommodate these concurrent processes.

For an existing Docker-based checkout, update `.env` to `DB_CONNECTION=sqlite` and remove the old `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` and any `DB_URL` override. Keep `QUEUE_CONNECTION=database`, `CACHE_STORE=database`, `SESSION_DRIVER=database` and `DB_QUEUE_RETRY_AFTER=180`. This starts a separate local database; it does not copy PostgreSQL data.

Configure Riot, Discord, Groq and other integration credentials in `.env` when exercising those features. Set `APP_URL` to the address printed by the dev server and update the Discord redirect URI accordingly. Without credentials, the application can boot, but external integrations cannot complete.

## Checks

```bash
composer test
npm run lint:check
npm run format:check
npm run types:check
```

Tests default to in-memory SQLite. CI runs the same suite on SQLite and PostgreSQL 17 with PHP 8.4 and 8.5. To test against a disposable PostgreSQL database locally:

```bash
DB_CONNECTION=pgsql DB_HOST=127.0.0.1 DB_PORT=5432 \
DB_DATABASE=gamesentry_test DB_USERNAME=gamesentry DB_PASSWORD=gamesentry \
php artisan test --compact
```

Use only a dedicated test database: the test suite recreates tables. PostgreSQL coverage remains necessary for production transactions, row locks, constraints and JSON semantics; SQLite does not provide identical concurrency guarantees.

## Production

Production uses Docker Compose and PostgreSQL 17, with one application image and four separate services:

| Service | Role | Networks |
| --- | --- | --- |
| web | FrankenPHP HTTP server | internal, egress, external proxy |
| worker | Database queue worker | internal, egress |
| scheduler | Dispatch polls, update heartbeat, prune records | internal |
| postgres | Persistent database with healthcheck | internal only, no published port |

The external Caddy proxy reaches `gamesentry-web:80` on the existing `proxy` network. Web and worker retain outbound access for external APIs. The scheduler only dispatches work and accesses the database. PostgreSQL retains its named volume and the existing backup arrangement.

Stable PHP production settings are baked into the image from `docker/php/php.prod.ini`; changing them requires an image build. No host PHP configuration mount is needed.

The server uses `/srv/gamesentry/compose.prod.yaml`, `.env.production`, a small generated `.env.deploy` containing the last verified image reference, and the existing `backups/` directory. Keep application settings and secrets in `.env.production`, including `APP_ENV=production`, `APP_DEBUG=false`, `DB_CONNECTION=pgsql`, `DB_HOST=postgres`, database credentials, `QUEUE_CONNECTION=database`, and `DB_QUEUE_RETRY_AFTER=180` (greater than the worker's 120-second timeout). Provision the application key and integration secrets on the server; Actions does not upload them.

On a push to `main`, the `docker` workflow builds and publishes the application image to GHCR under the commit SHA (and `latest`). After a successful build, `deploy` checks out that exact SHA and uploads only its Compose file. It then:

1. Selects and pulls the commit-SHA image.
2. Runs migrations in a one-off container before recreating application services.
3. Recreates web, worker and scheduler using that same image, waiting for readiness.
4. Verifies web and PostgreSQL health, worker and scheduler running without early restarts, image agreement, and public `/up` through Caddy.
5. Records the verified image in `.env.deploy`, without editing `.env.production`.

Deployments remain serialized. A failed migration prevents application recreation; a failed verification fails the deployment and leaves the last verified image record intact. There is no automatic rollback. Migrations must remain compatible with the previous running application during deployment.

For routine server commands after the first successful deployment:

```bash
docker compose --env-file .env.production --env-file .env.deploy \
  -p gamesentry -f compose.prod.yaml ps
```

For manual deployment or recovery, export `GAMESENTRY_IMAGE=ghcr.io/hvgochr/gamesentry:<commit-sha>` and follow the same pull → migrate → recreate → verify order. Shell variables take precedence over env files. Recovering an image does not reverse migrations.

On the first deployment of this refactor, existing `GAMESENTRY_IMAGE` entries in `.env.production` are harmless because the workflow exports the chosen image. After success, remove that stale entry and the unused server-side `docker/php/php.prod.ini` file. Keep the repository's PHP configuration file: it is an image build input. No database, network, proxy or backup migration is required.
