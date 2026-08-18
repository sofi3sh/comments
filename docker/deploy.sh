#!/bin/sh
###############################################################################
# Deploys the web and console images to the stage server.
#
# Replaces the single-service deploy that shipped the old nginx+php-fpm image.
# Three compose services are now involved, all defined in /opt/docker on the
# server:
#
#   $APP_SERVICE        FrankenPHP + Octane, the only one serving HTTP
#   $CONSOLE_SERVICE      php artisan queue:work      (RUN_MIGRATIONS=1 here)
#
# Migrations run from the queue service's entrypoint, so it is started first
# and allowed to finish before the web tier is replaced.
###############################################################################
set -e

if [ -z "$SERVER_USER" ] || [ -z "$SERVER_IP" ] || [ -z "$SERVER_ID_RSA" ] || [ -z "$APP_VERSION" ]; then
  echo "Usage: SERVER_USER, SERVER_IP, SERVER_ID_RSA and APP_VERSION must be set"
  exit 1
fi

APP_SERVICE="${APP_SERVICE:-platform-app}"
CONSOLE_SERVICE="${CONSOLE_SERVICE:-platform-console}"
SCHEDULER_SERVICE="${SCHEDULER_SERVICE:-platform-scheduler}"

chmod og= "$SERVER_ID_RSA"

apk add --no-cache openssh-client

echo "Using .env file from GitLab File variable..."
cp "$LARAVEL_ENV_STAGE" .env.miss-api

echo "Copy .env file to remote server..."
scp -i "$SERVER_ID_RSA" -o StrictHostKeyChecking=no .env.miss-api "$SERVER_USER@$SERVER_IP:/opt/docker/env/.env.miss-api"

echo "Creating temporary deploy script [apply.sh]..."
cat <<EOF > apply.sh
#!/bin/bash
set -e
cd /opt/docker

echo "Verify .env file exists..."
ls -la env/.env.miss-api

echo "Logging in to Docker registry [$CI_REGISTRY]..."
docker login -u gitlab-ci-token -p $CI_JOB_TOKEN $CI_REGISTRY

# The scheduler runs the same console image with a different command.
SCHEDULER_PRESENT=""
if docker compose config --services 2>/dev/null | grep -qx "$SCHEDULER_SERVICE"; then
  SCHEDULER_PRESENT="$SCHEDULER_SERVICE"
else
  echo "Note: no '$SCHEDULER_SERVICE' service in compose — skipping it."
fi

echo "Pulling images..."
docker compose pull $APP_SERVICE $CONSOLE_SERVICE \$SCHEDULER_PRESENT

# The queue service owns migrations (RUN_MIGRATIONS=1). Bring it up first so
# the schema is current before the new web image starts serving against it.
echo "Starting console services (runs migrations + seeders)..."
docker compose up -d $CONSOLE_SERVICE

# Readiness is taken from the container healthcheck, not from the logs.
# The compose healthcheck goes healthy once the entrypoint is past migrate/seed
# and has exec'd the worker, which is exactly the condition the web tier needs.
echo "Waiting for migrations to complete..."
CONSOLE_CID=\$(docker compose ps -q $CONSOLE_SERVICE)
if [ -z "\$CONSOLE_CID" ]; then
  echo "$CONSOLE_SERVICE has no container; it did not start."
  docker compose ps
  exit 1
fi

for i in \$(seq 1 60); do
  STATE=\$(docker inspect -f '{{.State.Status}}' "\$CONSOLE_CID" 2>/dev/null || echo missing)
  HEALTH=\$(docker inspect -f '{{if .State.Health}}{{.State.Health.Status}}{{else}}none{{end}}' "\$CONSOLE_CID" 2>/dev/null || echo none)

  if [ "\$HEALTH" = "healthy" ]; then
    echo "Console is healthy; migrations finished."
    break
  fi

  # A crash-looping entrypoint (bad .env, unreachable DB, failed migration)
  # would otherwise burn the whole timeout before reporting anything.
  if [ "\$STATE" = "exited" ] || [ "\$STATE" = "dead" ] || [ "\$STATE" = "missing" ]; then
    echo "Console container is '\$STATE' — it failed during migrate/seed. Recent logs:"
    docker compose logs --tail=100 $CONSOLE_SERVICE
    exit 1
  fi

  # No healthcheck defined in compose: fall back to "the process is up" so a
  # server whose compose predates the healthcheck still deploys.
  if [ "\$HEALTH" = "none" ] && [ "\$STATE" = "running" ] && [ "\$i" -gt 15 ]; then
    echo "Console has no healthcheck; it has stayed running for 30s. Continuing."
    break
  fi

  if [ "\$i" = "60" ]; then
    echo "Console did not become ready in time (state=\$STATE health=\$HEALTH). Recent logs:"
    docker compose logs --tail=100 $CONSOLE_SERVICE
    exit 1
  fi
  sleep 2
done

echo "Starting web service..."
docker compose up -d $APP_SERVICE

# After the schema is current — the scheduler dispatches work against it.
if [ -n "\$SCHEDULER_PRESENT" ]; then
  echo "Starting scheduler..."
  docker compose up -d $SCHEDULER_SERVICE
fi

echo "Checking container status..."
docker compose ps $APP_SERVICE $CONSOLE_SERVICE \$SCHEDULER_PRESENT
docker compose logs --tail=50 $APP_SERVICE

echo "Remove unused images..."
docker image prune -a -f

echo "Deployment completed"
EOF

echo "Copy temporary deploy script [apply.sh] to remote server /tmp/apply.sh..."
scp -i "$SERVER_ID_RSA" -o StrictHostKeyChecking=no apply.sh "$SERVER_USER@$SERVER_IP:/tmp/apply.sh"

echo "Starting deployment process..."
ssh -i "$SERVER_ID_RSA" -o StrictHostKeyChecking=no "$SERVER_USER@$SERVER_IP" "bash /tmp/apply.sh"

echo "Cleanup local files..."
rm apply.sh
rm .env.miss-api

echo "Cleanup remote files..."
ssh -i "$SERVER_ID_RSA" -o StrictHostKeyChecking=no "$SERVER_USER@$SERVER_IP" "rm /tmp/apply.sh"
