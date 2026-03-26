<?php

require 'config.php';
require 'classes/ImapClient.php';
require 'classes/MailDownloader.php';
require 'classes/ZipManager.php';

// Captura de datos
$auth_type = $_POST['auth_type'] ?? 'direct';
$target_email = $_POST['email'];
$password = $_POST['password'];
$host = $_POST['imap'];
$port = $_POST['port'];

/**
 * LÓGICA DE AUTENTICACIÓN
 * Si es cPanel, el usuario IMAP debe ser: usuario_cpanel|email_objetivo
 */
$login_user = $target_email;
if ($auth_type === 'cpanel' && !empty($_POST['cpanel_user'])) {
    $login_user = $_POST['cpanel_user'] . "|" . $target_email;
}

$emailSafe = preg_replace('/[^a-zA-Z0-9]/', '_', $target_email);
$fecha = date('Y-m-d_H-i-s');
$backupDir = BACKUP_PATH . $emailSafe . '_' . $fecha;

if (!mkdir($backupDir, 0777, true)) {
    die("Error al crear el directorio de backup.");
}

try {
    // Conectar IMAP usando el login procesado
    $imap = new ImapClient();
    $imap->connect($login_user, $password, $host, $port);

    // Obtener carpetas
    $folders = $imap->getFolders();

    if (!$folders) {
        die("No se encontraron carpetas o error en la autenticación.");
    }

    // Descargar correos
    $downloader = new MailDownloader($imap);
    $downloader->downloadAll($folders, $backupDir);

    // Cerrar conexión
    $imap->close();

    // Crear zip
    $zipFile = $backupDir . '.zip';
    $zip = new ZipManager();

    if ($zip->createZip($backupDir, $zipFile)) {
        echo "<h3>Backup completado con éxito</h3>";
        echo "<p>Cuenta respaldada: $target_email</p>";
        echo "<a href='backups/" . basename($zipFile) . "' class='btn-download' download>Descargar archivo ZIP</a>";
    } else {
        echo "Error al generar el archivo comprimido.";
    }

} catch (Exception $e) {
    echo "Error crítico: " . $e->getMessage();
}