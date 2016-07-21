#!/bin/sh

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

. $DIR/include.sh

log "Dropping DB..."
echo "DROP DATABASE IF EXISTS \`$DB_NAME\`;" | mysql -h $DB_HOST -P $DB_PORT -u$DB_USER -p$DB_PASSWORD
check_failure $?
