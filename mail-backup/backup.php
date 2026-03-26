<?php
require 'config.php';
require 'classes/ImapClient.php';
require 'classes/MailDownloader.php';
require 'classes/ZipManager.php';

// Iniciar sesión y limpiar progreso anterior
session_start();
$_SESSION['progress_percent'] = 0;
$_SESSION['progress_status'] = "Estableciendo conexión...";
session_write_close();

$auth_type = $_POST['auth_type'] ?? 'direct';
$email = $_POST['email'];
$password = $_POST['password'];
$host = $_POST['imap'];
$port = $_POST['port'];

$login_user = ($auth_type === 'cpanel') ? $_POST['cpanel_user'] . "|" . $email : $email;

$emailSafe = preg_replace('/[^a-zA-Z0-9]/', '_', $email);
$backupDir = BACKUP_PATH . $emailSafe . '_' . date('Y-m-d_H-s');
mkdir($backupDir, 0777, true);

try {
    $imap = new ImapClient();
    $imap->connect($login_user, $password, $host, $port);

    $folders = $imap->getFolders();

    $downloader = new MailDownloader($imap);
    $downloader->downloadAll($folders, $backupDir);

    // Compresión
    session_start();
    $_SESSION['progress_percent'] = 95;
    $_SESSION['progress_status'] = "Creando archivo comprimido...";
    session_write_close();

    $zipFile = $backupDir . '.zip';
    $zip = new ZipManager();
    $zip->createZip($backupDir, $zipFile);

    session_start();
    $_SESSION['progress_percent'] = 100;
    $_SESSION['progress_status'] = "¡Backup completado!";
    session_write_close();

    echo "<script>
        window.parent.document.getElementById('resultArea').innerHTML = \"<a href='backups/" . basename($zipFile) . "' class='btn-download' download>Descargar Backup ZIP</a>\";
    </script>";

} catch (Exception $e) {
    session_start();
    $_SESSION['progress_status'] = "Error: " . $e->getMessage();
    session_write_close();
    echo "<script>window.parent.document.getElementById('resultArea').innerHTML = '<b style=\"color:red\">Error: " . addslashes($e->getMessage()) . "</b>';</script>";
}