<?php
session_start();
require 'config.php';
require 'classes/ImapClient.php';
require 'classes/MailDownloader.php';
require 'classes/ZipManager.php';

// Inicializar progreso
$_SESSION['progress_percent'] = 5;
$_SESSION['progress_status'] = "Conectando al servidor...";
session_write_close();

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

    $downloader = new MailDownloader($imap);
    $downloader->downloadAll($folders, $backupDir);

    // Comprimir
    session_start();
    $_SESSION['progress_status'] = "Comprimiendo archivos...";
    session_write_close();

    $zipFile = $backupDir . '.zip';
    $zip = new ZipManager();
    $zip->createZip($backupDir, $zipFile);

    // Finalizar
    session_start();
    $_SESSION['progress_percent'] = 100;
    $_SESSION['progress_status'] = "¡Completado!";
    session_write_close();

    // Enviar respuesta al iframe (se mostrará al usuario)
    echo "<script>
        window.parent.document.getElementById('resultArea').innerHTML = \"<h3>Backup listo</h3><a href='backups/" . basename($zipFile) . "' download>Descargar ZIP</a>\";
    </script>";

} catch (Exception $e) {
    session_start();
    $_SESSION['progress_status'] = "Error: " . $e->getMessage();
    session_write_close();
}