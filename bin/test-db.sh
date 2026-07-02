#!/usr/bin/env bash
#
# Throwaway test database + PHPUnit harness. See docs/testing.md.
#
# Spins up MariaDB in Docker, loads the uniCenta test schema
# (database/testing/unicenta-test-schema.sql) plus the Laravel migrations,
# then runs the PHPUnit suite in a php:8.4-cli container (no local PHP needed).
#
# Usage:
#   bin/test-db.sh            # full cycle: up -> schema -> migrate -> phpunit -> down
#   KEEP_DB=1 bin/test-db.sh  # leave the DB container running for inspection
#   bin/test-db.sh down       # just remove the DB container
#
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
NETWORK=mt-test
DB_CONTAINER=mt-test-db
PHP_IMAGE=mt-test-php          # php:8.4-cli + pdo_mysql, built on first run
DB_PASSWORD=secret
DB_DATABASE=testing

# Docker Desktop on Windows (Git Bash) mangles /app style paths; disable that.
export MSYS_NO_PATHCONV=1

down() {
    docker rm -f "$DB_CONTAINER" >/dev/null 2>&1 || true
    echo "[test-db] removed $DB_CONTAINER (network '$NETWORK' left in place)"
}

if [ "${1:-}" = "down" ]; then
    down
    exit 0
fi

echo "[test-db] preparing docker network + database container"
docker network create "$NETWORK" >/dev/null 2>&1 || true
docker rm -f "$DB_CONTAINER" >/dev/null 2>&1 || true
docker run -d --name "$DB_CONTAINER" --network "$NETWORK" \
    -e MYSQL_ROOT_PASSWORD="$DB_PASSWORD" -e MYSQL_DATABASE="$DB_DATABASE" \
    mariadb:10 >/dev/null

echo -n "[test-db] waiting for MariaDB"
for i in $(seq 1 60); do
    if docker exec "$DB_CONTAINER" mysqladmin ping -uroot -p"$DB_PASSWORD" --silent >/dev/null 2>&1; then
        break
    fi
    echo -n "."
    sleep 1
    if [ "$i" = 60 ]; then echo " timed out"; exit 1; fi
done
echo " up"

# php:8.4-cli ships without pdo_mysql; bake it into a local image once.
if ! docker image inspect "$PHP_IMAGE" >/dev/null 2>&1; then
    echo "[test-db] building $PHP_IMAGE (php:8.4-cli + pdo_mysql)"
    docker run --name mt-test-php-build php:8.4-cli \
        docker-php-ext-install pdo_mysql >/dev/null
    docker commit mt-test-php-build "$PHP_IMAGE" >/dev/null
    docker rm mt-test-php-build >/dev/null
fi

APP_KEY=""

# NOTE: strict=false in the DATABASE_URL query string overrides the hardcoded
# 'strict' => true in config/database.php (Laravel's ConfigurationUrlParser
# merges query params into the connection config). The unit tests pass table
# numbers as PHP ints (e.g. TABLENUMBER = 111); with STRICT_TRANS_TABLES the
# resulting int-vs-varchar comparison against UUID sharedtickets ids escalates
# a coercion warning into an error. Production uniCenta DBs run non-strict.
run_php() {
    docker run --rm --network "$NETWORK" -v "$REPO_ROOT:/app" -w /app \
        -e APP_ENV=testing -e APP_KEY="$APP_KEY" -e APP_DEBUG=true \
        -e DB_CONNECTION=mysql \
        -e DATABASE_URL="mysql://root:$DB_PASSWORD@$DB_CONTAINER:3306/$DB_DATABASE?strict=false" \
        -e CACHE_DRIVER=array -e SESSION_DRIVER=array -e QUEUE_CONNECTION=sync \
        -e MAIL_MAILER=array -e TELESCOPE_ENABLED=false \
        "$PHP_IMAGE" "$@"
}

APP_KEY="$(run_php php artisan key:generate --show)"
echo "[test-db] generated APP_KEY"

echo "[test-db] loading uniCenta test schema (must precede migrate)"
docker exec -i "$DB_CONTAINER" mariadb -uroot -p"$DB_PASSWORD" "$DB_DATABASE" \
    < "$REPO_ROOT/database/testing/unicenta-test-schema.sql"

echo "[test-db] running laravel migrations"
run_php php artisan migrate --force

echo "[test-db] running phpunit"
set +e
run_php php -d memory_limit=512M vendor/bin/phpunit "$@"
STATUS=$?
set -e

if [ "${KEEP_DB:-0}" != "1" ]; then
    down
else
    echo "[test-db] KEEP_DB=1 - leaving $DB_CONTAINER running"
fi

exit $STATUS
