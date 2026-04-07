<?php
// Configuración de límites del servidor
set_time_limit(0);
ini_set('memory_limit', '512M');

// Definición de rutas absolutas
define('BACKUP_PATH', __DIR__ . '/backups/');
define('TEMP_PATH', __DIR__ . '/temp/');

// Crear carpetas si no existen
if (!file_exists(BACKUP_PATH)) {
    mkdir(BACKUP_PATH, 0777, true);
}
if (!file_exists(TEMP_PATH)) {
    mkdir(TEMP_PATH, 0777, true);
}