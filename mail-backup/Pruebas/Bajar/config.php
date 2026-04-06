<?php
define('BACKUP_DIR', 'backups/');
if (!is_dir(BACKUP_DIR)) mkdir(BACKUP_DIR, 0777, true);
?>