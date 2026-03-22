#!/bin/bash
set -e

mysql -u root -p"${MYSQL_ROOT_PASSWORD}" <<-EOSQL
    CREATE DATABASE IF NOT EXISTS sistema_trazabilidad_security
        CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    CREATE DATABASE IF NOT EXISTS sistema_trazabilidad_core
        CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    GRANT ALL PRIVILEGES ON sistema_trazabilidad_security.* TO '${MYSQL_USER}'@'%';
    GRANT ALL PRIVILEGES ON sistema_trazabilidad_core.* TO '${MYSQL_USER}'@'%';
    FLUSH PRIVILEGES;
EOSQL
