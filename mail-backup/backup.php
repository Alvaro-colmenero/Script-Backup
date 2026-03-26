<?php
require 'config.php';
require 'classes/ImapClient.php';
require 'classes/MailDownloader.php';
require 'classes/ZipManager.php';

set_time_limit(0);
ini_set('memory_limit', '512M');

// Crear un ID único para este backup para que no se mezclen si hay varios usuarios
$backup_id = md5($_POST['email'] . time());
$progress_file = __DIR__ . "/temp_progress_{$backup_id}.txt";

// Guardar el ID en una cookie para que progress.php sepa qué archivo leer
setcookie('backup_id', $backup_id, time() + 3600, "/");

function updateProgress($percent, $status, $file) {
    $data = json_encode(['percent' => $percent, 'status' => $status]);
    file_put_contents($file, $data);
}

updateProgress(5, "Conectando al servidor IMAP...", $progress_file);

$auth_type = $_POST['auth_type'] ?? 'direct';
$email = $_POST['email'];
$password = $_POST['password'];
$host = $_POST['imap'];
$port = $_POST['port'];

$login_user = ($auth_type === 'cpanel') ? $_POST['cpanel_user'] . "|" . $email : $email;

$emailSafe = preg_replace('/[^a-zA-Z0-9]/', '_', $email);
$backupDir = BACKUP_PATH . $emailSafe . '_' . date('Y-m-d_H-i-s');
mkdir($backupDir, 0777, true);

try {
    $imap = new ImapClient();
    $imap->connect($login_user, $password, $host, $port);

    $folders = $imap->getFolders();

    // Pasamos el archivo de progreso al downloader
    $downloader = new MailDownloader($imap);
    $downloader->setProgressFile($progress_file);
    $downloader->downloadAll($folders, $backupDir);

    updateProgress(95, "Comprimiendo archivos...", $progress_file);

    $zipFile = $backupDir . '.zip';
    $zip = new ZipManager();
    $zip->createZip($backupDir, $zipFile);

    updateProgress(100, "¡Finalizado!", $progress_file);

    echo "<script>
        window.parent.document.getElementById('resultArea').innerHTML = \"<br><a href='backups/" . basename($zipFile) . "' style='padding:15px; background:#28a745; color:white; text-decoration:none; border-radius:5px; display:inline-block;'>DESCARGAR BACKUP ZIP</a>\";
        window.parent.finishBackup();
    </script>";

    // Borrar archivo temporal después de un rato (opcional)
} catch (Exception $e) {
    updateProgress(0, "Error: " . $e->getMessage(), $progress_file);
    echo "<script>window.parent.document.getElementById('resultArea').innerHTML = '<b style=\"color:red\">Error: " . addslashes($e->getMessage()) . "</b>';</script>";
}