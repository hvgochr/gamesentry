# Production deployment

GameSentry runs on a VPS with Docker Compose. The repository builds one immutable application image and starts it with separate web, queue-worker and scheduler commands alongside PostgreSQL. A Caddy instance managed outside this Compose project provides the public reverse proxy.

## VPS layout

The deployment workflow operates in `/srv/gamesentry`. The directory contains:

- `compose.prod.yaml`, uploaded from the deployed commit;
- `.env.production`, provisioned on the VPS with application settings and secrets;
- `.env.deploy`, generated after a successful deployment with the last verified image reference.

The workflow uploads only `compose.prod.yaml`. It does not replace `.env.production`, upload application source, or copy secrets from GitHub Actions.

The Compose project name is `gamesentry`. Keeping the project name stable preserves resource names, including the PostgreSQL volume, across deployments.

## Runtime services

| Service     | Process                                                    | Networks                      | Persistent state                                    |
| ----------- | ---------------------------------------------------------- | ----------------------------- | --------------------------------------------------- |
| `web`       | FrankenPHP serving Laravel from `public/`                  | `internal`, `egress`, `proxy` | None outside PostgreSQL                             |
| `worker`    | `php artisan queue:work --sleep=3 --tries=3 --timeout=120` | `internal`, `egress`          | Database queue state                                |
| `scheduler` | `php artisan schedule:work`                                | `internal`                    | Scheduler locks and heartbeat in the database cache |
| `postgres`  | PostgreSQL 17                                              | `internal`                    | `postgres_data` named volume                        |

All services use `restart: unless-stopped`. The application containers wait for PostgreSQL's `pg_isready` health check. The web health check requests `http://127.0.0.1/up` inside its container; worker and scheduler container health checks are disabled and deployment verifies their process state instead.

## Network boundaries

`internal` is an internal Compose network. PostgreSQL is attached only to this network and publishes no host port.

`egress` gives `web` and `worker` outbound access. The web process needs it for interactive Discord, Riot, Stripe and email operations. The worker needs it for Riot match requests, Groq generation and Discord delivery. The scheduler only queries the database and dispatches jobs, so it has no egress network.

`proxy` is an external network that must already exist and be shared with the separately managed Caddy service. Compose gives the web container the `gamesentry-web` alias on that network. Caddy forwards public traffic to `gamesentry-web:80` and manages TLS outside this repository.

## Production image

The multi-stage `Dockerfile` uses FrankenPHP 1 with PHP 8.4 on Debian Bookworm. It:

1. Installs the PHP extensions needed by the application and PostgreSQL.
2. Installs production Composer dependencies without development packages.
3. Uses Node.js 22 to install locked frontend dependencies and build Vite assets.
4. Copies application code, production dependencies and built assets into the final image.
5. Bakes in `docker/php/php.prod.ini` and prepares Laravel's writable directories.

The final image contains the application code and frontend build. Runtime PHP settings are image inputs rather than mutable VPS mounts.

## Environment files

`.env.production` is the Laravel runtime environment loaded into each application container. It should include production values for:

- `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` and a provisioned `APP_KEY`;
- PostgreSQL with `DB_CONNECTION=pgsql` and `DB_HOST=postgres`;
- `DB_DATABASE`, `DB_USERNAME` and `DB_PASSWORD` matching the Compose PostgreSQL service;
- database-backed queues, cache and sessions;
- `DB_QUEUE_RETRY_AFTER=180`, which is longer than the worker's 120-second timeout;
- Riot, Discord and Groq credentials;
- Stripe, Resend and any other enabled integration settings;
- GameSentry polling, cooldown, generation and retention settings.

`.env.deploy` has one responsibility:

```dotenv
GAMESENTRY_IMAGE=ghcr.io/hvgochr/gamesentry:<verified-commit-sha>
```

It is used for Compose variable interpolation during routine manual commands. It is not an application environment file and is not mounted into the containers.

Neither file belongs in version control. Do not put real values in documentation, shell history or workflow output.

## GitHub Actions flow

### Build and publish

The `docker` workflow runs on pushes to `main`. It:

1. Checks out the pushed commit.
2. Authenticates to GitHub Container Registry with the workflow token.
3. Builds the `production` Docker target with Buildx and GitHub Actions layer caching.
4. Publishes `ghcr.io/hvgochr/gamesentry:<commit-sha>` and `ghcr.io/hvgochr/gamesentry:latest`.

The SHA tag is the deployment input. The mutable `latest` tag is published for convenience but is not selected by the automated deployment.

### Deploy

The `deploy` workflow is triggered only when the `docker` workflow completes successfully for `main`. Production runs are serialized with the `production` concurrency group and are not cancelled when a newer run starts.

The workflow checks out the Docker workflow's exact commit and uploads its `compose.prod.yaml`. Over SSH it exports:

```text
GAMESENTRY_IMAGE=ghcr.io/hvgochr/gamesentry:<workflow-head-sha>
```

That exported value takes precedence during the automated Compose commands. The workflow then:

1. Pulls the selected application image and other referenced images.
2. Runs `php artisan migrate --force` in a one-off `web` container.
3. Recreates `web`, `worker` and `scheduler` from the selected image and removes orphaned services.
4. Waits for `web` and `postgres` readiness.
5. Verifies all four containers are running.
6. Verifies web and PostgreSQL health.
7. Confirms worker and scheduler have not restarted during the initial ten-second observation period.
8. Confirms every application container uses the selected SHA-tagged image.
9. Requests the public `/up` endpoint through Caddy.
10. Atomically replaces `.env.deploy` with the verified image reference.

Migrations run before the application services are recreated. A migration failure therefore stops the deployment before the running web, worker and scheduler containers are replaced.

The final image record is written only after internal and public verification succeed. A later verification failure leaves the previous `.env.deploy` value intact, but the workflow does not automatically roll back containers or database migrations. Schema changes should remain compatible with the previously running application during this deployment window.

The deployment workflow is directly gated by the image build workflow. Repository tests run in a separate workflow; any requirement for those checks to pass before code reaches `main` is configured through GitHub branch protection rather than these workflow files.

## Post-deploy verification

The automated checks cover:

- the internal web health endpoint;
- PostgreSQL readiness;
- worker and scheduler process state and early restart count;
- consistent application image references;
- the public health endpoint through Caddy.

The scheduler also updates `gamesentry:scheduler:last-run-at` in the database cache. The admin dashboard reads this heartbeat and reports queue, failed-job and Riot cooldown state for operational inspection after deployment.

## Manual Compose commands

Run commands from `/srv/gamesentry`. This shell function applies both server-side environment files and the same project and Compose names used by Actions:

```bash
compose() {
  docker compose \
    --env-file .env.production \
    --env-file .env.deploy \
    -p gamesentry \
    -f compose.prod.yaml \
    "$@"
}
```

Common inspection commands:

```bash
compose ps
compose logs --tail=200 web worker scheduler postgres
compose exec web php artisan migrate:status
compose exec web php artisan queue:failed
```

To restart an application process without replacing PostgreSQL:

```bash
compose restart web
compose restart worker
compose restart scheduler
```

For a manual deployment or recovery, select an immutable SHA tag explicitly. An exported shell value overrides the image stored in `.env.deploy`:

```bash
export GAMESENTRY_IMAGE=ghcr.io/hvgochr/gamesentry:<commit-sha>

compose pull
compose run --rm --interactive=false -T web php artisan migrate --force
compose up -d --force-recreate --remove-orphans web worker scheduler
compose up -d --no-recreate --wait --wait-timeout 120 web postgres
compose ps
curl --fail --silent --show-error https://gamesentry.charradehugo.com/up
```

Repeat the container state, restart-count and image checks from the workflow before recording the selected image in `.env.deploy`. Changing the image reference does not reverse migrations.

Avoid `docker compose down -v` in normal operations because `-v` removes the PostgreSQL volume.

## Database persistence and backups

PostgreSQL stores its data in the `postgres_data` named volume. Recreating application containers, updating `compose.prod.yaml` and running `compose up` preserve that volume.

The repository does not define a backup container, backup schedule or restore command. Database backups remain a VPS responsibility outside the application Compose project. The existing host backup process should:

- create PostgreSQL-consistent backups rather than copying a live volume directly;
- store copies outside the Docker volume and protect their credentials and access;
- apply an explicit retention policy;
- test restoration periodically.

Deployments should preserve the existing host backup location and schedule. A successful container health check does not verify backup freshness or restorability.

## First-time prerequisites

Before the first automated deployment:

- install Docker Engine with the Compose plugin on the VPS;
- create `/srv/gamesentry` with permissions for the deployment user;
- create and attach the external `proxy` network to Caddy;
- configure Caddy to proxy the public hostname to `gamesentry-web:80`;
- provision `.env.production`;
- ensure the VPS can pull the GHCR image;
- configure the GitHub production environment, SSH secrets and deployment host/user variables;
- establish and test the PostgreSQL backup process.
