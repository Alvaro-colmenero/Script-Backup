<?php
set_time_limit(0);
ini_set('memory_limit', '512M');

define('BACKUP_PATH', __DIR__ . '/backups/');

if (!file_exists(BACKUP_PATH)) {
    mkdir(BACKUP_PATH, 0777, true);
}