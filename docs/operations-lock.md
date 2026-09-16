# GameSentry deploy/backup operations lock

The deploy workflow and `/usr/local/bin/backup-gamesentry` both acquire `/srv/gamesentry/.ops.lock` with `flock -w 900`. The lock file must remain in place and be writable by `hugo`. Never delete, replace, or rotate it: advisory locks are associated with the open file, not its path. Caddy and other applications do not use this lock.

## Safe rollout (no deployment during transition)

1. Leave the PR unmerged while preparing the server. Check that no `deploy` workflow run is active or queued and that `gamesentry-backup.service` is not running. Temporarily stop only the GameSentry backup timer: `sudo systemctl stop gamesentry-backup.timer` (do not disable it).
2. As `hugo`, create the persistent lock with `cd /srv/gamesentry && umask 077 && touch .ops.lock && chmod 600 .ops.lock`. If ownership is wrong, use `sudo chown hugo:hugo /srv/gamesentry/.ops.lock`.
3. Install this branch's `ops/backup-gamesentry` into `/usr/local/bin/backup-gamesentry` with owner `root`, group `hugo`, mode `750` (e.g. `sudo install -o root -g hugo -m 750 ops/backup-gamesentry /usr/local/bin/backup-gamesentry`). Ensure `/srv/backups/gamesentry` is owned by `hugo` with mode `700`.
4. Add `TimeoutStartSec=30min` to the `[Service]` section of `gamesentry-backup.service`; `sudo systemctl daemon-reload`.
5. Test the backup service and restore its timer with `sudo systemctl start gamesentry-backup.service && sudo systemctl start gamesentry-backup.timer`. Preserve `OnCalendar=*-*-* 03:30:00` on a UTC server and `Persistent=true`.
6. Only after these steps, review and merge the PR. The next successful `docker` workflow on `main` can trigger the new deployment. The PR itself does not deploy.

The server must already contain `.env.production` (application settings), `.env.deploy` (verified `GAMESENTRY_IMAGE`) and `compose.prod.yaml`. The backup loads both environment files and unsets any inherited `GAMESENTRY_IMAGE`. The deployment uploads a unique Compose candidate before locking, then validates and activates it under the lock, keeps its SSH session open through migrations, container and HTTP checks, and publishes `.env.deploy` last. Migrations retain `--interactive=false` to avoid consuming the SSH script from stdin. GitHub's deployment job has a 45-minute timeout; the systemd backup service permits up to 30 minutes, including the 900-second lock wait.

The deployment stores a temporary copy of the previous Compose. Before container recreation, a failure restores that file. If recreation has begun, the previous Compose is left on the server for manual recovery; `.env.deploy` remains the last fully verified image. Database migrations are not automatically rolled back.

## Verification

```bash
# timer and service
systemctl list-timers gamesentry-backup.timer
sudo systemctl start gamesentry-backup.service
sudo systemctl status gamesentry-backup.service
sudo journalctl -u gamesentry-backup.service -n 60 --no-pager
ls -lh /srv/backups/gamesentry

# inspect a completed archive without changing the database
cd /srv/gamesentry
unset GAMESENTRY_IMAGE
latest="$(find /srv/backups/gamesentry -maxdepth 1 -type f -name 'gamesentry-*.dump' | sort | tail -n 1)"
test -n "$latest" && test -s "$latest"
docker compose --env-file .env.production --env-file .env.deploy -p gamesentry -f compose.prod.yaml \
  exec -T postgres pg_restore --list < "$latest" > /dev/null

# lock held: a second acquisition must fail immediately
flock -n /srv/gamesentry/.ops.lock true
```

`pg_restore --list` checks that the archive catalogue can be read, **not** that a complete restoration works. To validate recoverability, periodically restore a copy into a separate disposable database and run application-level checks. Never restore over production as part of this smoke test.

For an exclusion test, open one SSH terminal as `hugo` and run `flock /srv/gamesentry/.ops.lock -c 'echo locked; sleep 20'`; while it is held, a second terminal's `flock -n /srv/gamesentry/.ops.lock true` must fail. The backup and deployment wait up to 900 seconds rather than overlapping; if the wait expires they fail without proceeding. Do not manually run a backup or merge the PR during an exclusion test.

**Recovery note:** backups are local to the VPS, so they do not protect against losing the entire server. Offsite copies and periodic actual restore tests remain separate tasks.
