# GameSentry

GameSentry monitors League of Legends and Teamfight Tactics players for new matches. When a match finishes, it posts a structured summary to Discord with an AI-generated commentary or roast based on the player's match data.

The project combines a Laravel/Inertia web application with a scheduler-driven, database-backed queue pipeline for polling Riot APIs and delivering notifications.

## Tech stack

| Area                 | Technologies                                                                                |
| -------------------- | ------------------------------------------------------------------------------------------- |
| Backend              | PHP 8.4, Laravel 13, Laravel Fortify, Laravel Cashier                                       |
| Frontend             | Inertia 3, React 19, TypeScript, Tailwind CSS 4, Vite                                       |
| Data                 | SQLite for local development, PostgreSQL 17 in production, database-backed queues and cache |
| Integrations         | Riot Account, Match and Summoner APIs; Data Dragon; Discord API; Groq API; Stripe           |
| Runtime and delivery | Docker, Docker Compose, FrankenPHP, Caddy, GHCR, GitHub Actions                             |

## Main features

- Links Discord servers through the bot installation flow and selects a destination text channel.
- Tracks League of Legends and Teamfight Tactics players by Riot ID and Discord user ID.
- Polls recent Riot match history asynchronously and processes newly discovered matches in chronological order.
- Builds game-specific Discord embeds with match statistics, Riot profile icons and League champion artwork from Data Dragon.
- Generates short French commentary with Groq, subject to per-plan daily usage limits.
- Prevents duplicate work with queue uniqueness, overlap locks, database constraints and Discord delivery nonces.
- Adapts polling intervals to player volume and applies per-game cooldowns after Riot rate-limit responses.
- Provides Free and Pro plan limits, Stripe subscription handling, and an admin dashboard for operational status and retries.
- Separates web, worker, scheduler and PostgreSQL services in production and deploys immutable images from GHCR.

## Architecture

Laravel owns routing, authentication, authorization, persistence and the integration boundary. Inertia connects the Laravel controllers to the React/TypeScript pages without a separate API layer. Scheduled commands place work on the database queue; workers call Riot, Groq and Discord outside the request lifecycle.

Production runs one application image in separate `web`, `worker` and `scheduler` containers. PostgreSQL stores application records as well as queue, cache and session state. See [Architecture](docs/architecture.md) for component boundaries, runtime topology and design decisions.

## Polling and notification flow

```text
Laravel scheduler
    ↓
gamesentry:dispatch-watched-player-polls
    ↓
PollWatchedPlayerJob
    ↓
Riot match history API
    ↓
MatchNotification + ProcessMatchNotificationJob
    ↓
Riot match API → match summary → Groq commentary
    ↓
Discord message
```

The scheduler periodically selects active players whose `next_poll_at` value is due and dispatches a bounded number of polling jobs per game. A poll compares Riot's recent match IDs with `last_seen_match_id`; new IDs are stored oldest-first as `MatchNotification` records and handed to a separate notification job.

The `(watched_player_id, riot_match_id)` database constraint prevents the same match from creating multiple notifications. Queue uniqueness and overlap middleware prevent concurrent work for one player or notification, while a persisted Discord nonce protects delivery retries. Riot history lookup happens in `PollWatchedPlayerJob`; match-detail, Groq and Discord calls happen in `ProcessMatchNotificationJob`.

See [Polling pipeline](docs/polling.md) for initial baseline behavior, retries, ordering and rate-limit cooldowns.

## Local setup

Install:

- PHP 8.4 or newer with PDO SQLite, `pcntl`, `mbstring`, XML, cURL, BCMath and ZIP extensions
- Composer 2
- Node.js 22 with npm

Docker is not required for local development.

```bash
composer run setup
composer run dev
```

`composer run setup` installs PHP and Node dependencies, copies `.env.example` when `.env` is absent, generates the application key, creates `database/database.sqlite`, runs migrations and builds the frontend. It preserves existing `.env` and SQLite files, but generates a new application key each time it runs.

`composer run dev` starts Laravel's development server, Vite, the database queue listener, Pail logs and `schedule:work` in one terminal. The SQLite connection uses WAL mode and a five-second busy timeout for these concurrent processes.

The default `.env.example` configures SQLite plus database-backed queues, cache and sessions. Before exercising external flows, set:

- `RIOT_LOL_API_KEY` and/or `RIOT_TFT_API_KEY`
- `DISCORD_CLIENT_ID`, `DISCORD_BOT_TOKEN` and `DISCORD_REDIRECT_URI`
- `GROQ_API_KEY` and, if needed, `GROQ_MODEL`
- Stripe and Resend credentials when testing billing or email flows

Set `APP_URL` to the local application URL and make the Discord redirect URI match it. The application can boot without integration credentials, but the corresponding external flows will report configuration errors.

## Production and deployment

Production uses Docker Compose with four services:

| Service     | Responsibility                                                                  |
| ----------- | ------------------------------------------------------------------------------- |
| `web`       | Serves Laravel through FrankenPHP and joins the external Caddy proxy network.   |
| `worker`    | Processes database queue jobs and makes outbound Riot, Groq and Discord calls.  |
| `scheduler` | Runs Laravel's scheduler, dispatches polling work and prunes old notifications. |
| `postgres`  | Stores persistent application, queue, cache and session data in a named volume. |

Pushes to `main` build the production target and publish commit-SHA and `latest` tags to GHCR. After a successful image workflow, the deployment workflow selects the immutable commit-SHA image, uploads `compose.prod.yaml`, runs migrations before recreating application services, then verifies container state, health, image identity and the public `/up` endpoint through Caddy.

The VPS keeps application configuration in `.env.production` and the last verified image reference in `.env.deploy`. See [Deployment](docs/deployment.md) for the network layout, deployment sequence, manual Compose commands, persistence and backup boundary.

## Tests and quality

Run the complete local check pipeline with:

```bash
composer ci:check
```

This runs ESLint, Prettier validation, TypeScript type checking, Pint validation, Larastan/PHPStan and PHPUnit. Useful narrower commands are:

```bash
composer test          # Pint, Larastan and PHPUnit
composer types:check   # Larastan/PHPStan only
npm run lint:check
npm run format:check
npm run types:check
```

PHPUnit uses in-memory SQLite by default. GitHub Actions also runs Larastan and the PHPUnit suite on PostgreSQL 17, across PHP 8.4 and 8.5. A separate workflow runs Pint, Prettier and ESLint formatting/lint tasks.

## Further documentation

- [Architecture](docs/architecture.md)
- [Polling pipeline](docs/polling.md)
- [Production deployment](docs/deployment.md)

## Possible next steps

- Add direct tests around polling dispatch, notification retries and idempotent delivery.
- Add longer-term metrics and alerting beyond the current admin status views and scheduler heartbeat.
- Add more granular notification preferences per Discord server or watched player.
- Evaluate Valorant support if the required Riot API access becomes available.
