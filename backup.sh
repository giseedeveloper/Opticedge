#!/bin/bash
set -e

PROJECT_DIR="/root/opticedge"
DB_USER="opticedge_user"
DB_PASS="SecurePassword123!"
DB_NAME="opticedge"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
FILENAME="backup_${TIMESTAMP}.sql"

cd "$PROJECT_DIR"

docker compose exec -T mysql mysqldump --no-tablespaces -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "database/sql/$FILENAME"

git add "database/sql/$FILENAME"
git commit -m "Auto DB backup $TIMESTAMP"
git pull --rebase --autostash
git push
