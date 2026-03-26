<?php

set_time_limit(0);
ini_set('memory_limit', '512M');

define('BACKUP_PATH', __DIR__ . '/backups/');
define('TEMP_PATH', __DIR__ . '/temp/');

if (!file_exists(BACKUP_PATH)) {
    mkdir(BACKUP_PATH, 0777, true);
}

if (!file_exists(TEMP_PATH)) {
    mkdir(TEMP_PATH, 0777, true);
}