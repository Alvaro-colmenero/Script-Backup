<?php
// Configuración de rutas
define('BACKUP_PATH', 'backups/');
define('TEMP_PATH', 'temp_restore/');

// Crear carpetas automáticamente si no existen
if (!is_dir(BACKUP_PATH)) {
    mkdir(BACKUP_PATH, 0777, true);
}

if (!is_dir(TEMP_PATH)) {
    mkdir(TEMP_PATH, 0777, true);
}
?>