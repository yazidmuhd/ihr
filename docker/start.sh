#!/bin/sh
# Container entrypoint. The same image runs either role, chosen per service (Railway, Render):
#   CONTAINER_ROLE=web     (default) migrate, cache config, serve HTTP
#   CONTAINER_ROLE=worker  queued jobs (résumé parsing, e-mails) + scheduled tasks (routes/console.php)
set -e

case "${CONTAINER_ROLE:-web}" in
  worker)
    php artisan optimize

    php artisan schedule:work &
    SCHEDULER=$!

    # queue:work exits normally every hour (--max-time) to keep memory flat;
    # the loop starts it again and revives the scheduler if it ever stopped.
    while true; do
      php artisan queue:work --queue=high,default --tries=3 --timeout=150 --sleep=3 --max-time=3600 || sleep 5
      if ! kill -0 "$SCHEDULER" 2>/dev/null; then
        php artisan schedule:work &
        SCHEDULER=$!
      fi
    done
    ;;
  web)
    php artisan optimize

    if [ "${DEMO_MODE:-false}" = "true" ]; then
      # Public demo (docs/LIVE_DEMO.md): fresh fictional data on every start. The free
      # host's disk is wiped on each restart or wake-up, so the database must be too.
      php artisan demo:reset
    else
      # Only the web service migrates, so two containers never migrate at once.
      # A failed migration stops the deploy instead of serving a half-updated schema.
      php artisan migrate --force
    fi
    php artisan storage:link --force

    # PHP's built-in server with several worker processes: one container process,
    # no Nginx, and the host's environment variables reach the app unchanged.
    cd public
    export PHP_CLI_SERVER_WORKERS="${PHP_CLI_SERVER_WORKERS:-4}"
    exec php -S "0.0.0.0:${PORT:-8080}" ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php
    ;;
  *)
    echo "Unknown CONTAINER_ROLE '${CONTAINER_ROLE}' (expected web or worker)" >&2
    exit 1
    ;;
esac
