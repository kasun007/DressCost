#!/bin/sh
# Prepares and starts DressCost inside the container:
# app key, wait for MySQL, migrations, demo data on an empty database, server.
set -e

cd /var/www/html

# Settings come from the container's environment (compose.yaml); the .env
# file only holds the app key, which is generated once per container.
if [ ! -f .env ]; then
    cp .env.example .env
fi

if ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --force --no-interaction
fi

# Runs a SQL query against the app database and prints the first value,
# or fails when the database can't be reached yet.
db_query() {
    php -r '
        $pdo = new PDO(
            sprintf("mysql:host=%s;port=%s;dbname=%s", getenv("DB_HOST"), getenv("DB_PORT") ?: "3306", getenv("DB_DATABASE")),
            getenv("DB_USERNAME"),
            getenv("DB_PASSWORD"),
        );
        echo $pdo->query($argv[1])->fetchColumn();
    ' "$1"
}

echo "Waiting for the database..."
until db_query "SELECT 1" > /dev/null 2>&1; do
    sleep 2
done

php artisan migrate --force --no-interaction

# Load the demo data only into an empty database, so restarts keep your data.
if [ "${SEED_DEMO:-true}" = "true" ] && [ "$(db_query "SELECT COUNT(*) FROM users")" = "0" ]; then
    echo "Empty database: loading demo data (log in as demo@dresscost.test / password)."
    php artisan db:seed --force --no-interaction
fi

# Telescope (http://localhost:8080/telescope) runs because APP_ENV is local;
# keep its recordings to the last two days.
php artisan telescope:prune --hours=48 --no-interaction > /dev/null 2>&1 || true

php artisan optimize --no-interaction > /dev/null

echo "DressCost is running at ${APP_URL:-http://localhost:8080}"
echo "Telescope (requests, exceptions, queries) is at ${APP_URL:-http://localhost:8080}/telescope"
exec php artisan serve --host=0.0.0.0 --port=8000
