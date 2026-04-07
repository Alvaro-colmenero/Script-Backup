<?php
require 'config.php';
require 'classes/ImapClient.php';
require 'classes/MailDownloader.php';
require 'classes/ZipManager.php';

try {
// 1. Generar ID único para esta operación
    $backup_id = md5($_POST['email'] . time());
    $progress_file = TEMP_PATH . "progress_{$backup_id}.txt";

// 2. Enviar cookie al navegador para que progress.php sepa qué leer
    setcookie('backup_id', $backup_id, time() + 3600, "/");

// Estado inicial
    updateProgress(0, "Conectando al servidor IMAP...", $progress_file);

    $auth_type = $_POST['auth_type'] ?? 'direct';
    $email = $_POST['email'];
    $password = $_POST['password'];
    $host = $_POST['imap'];
    $port = $_POST['port'];

    $login_user = ($auth_type === 'cpanel') ? $_POST['cpanel_user'] . "|" . $email : $email;
    $emailSafe = preg_replace('/[^a-zA-Z0-9]/', '_', $email);
    $backupDir = BACKUP_PATH . $emailSafe . '_' . date('Y-m-d_H-i-s');

    if (!file_exists($backupDir)) {
        updateProgress(0, "Creando nueva carpeta en backups... ", $progress_file);
        mkdir($backupDir, 0777, true);
    }

    pclose(
        popen(
            "start /B php backup.php "             //Comando de shell que lanza 'backup.php' en segundo plano
            . "\"$login_user\" \"$password\" \"$host\" "    //Parametros
            . "\"$port\" \"$progress_file\" \"$backupDir\" "
            . "> NUL 2> NUL",                               //Redireccion del output
            'r'                                       //Modo de operacion (lectura)
        )
    );
} catch (Exception $e) {
    updateProgress(0, "Error: " . $e->getMessage(), $progress_file);
}

// Función optimizada para escribir progreso sin bloqueos
function updateProgress($percent, $status, $progressFile) {
    if ($progressFile) {
        file_put_contents($progressFile, json_encode([
            'percent' => $percent,
            'status' => $status
        ]));
    }
}