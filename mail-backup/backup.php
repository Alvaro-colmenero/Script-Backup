<?php

require 'config.php';
require 'classes/ImapClient.php';
require 'classes/MailDownloader.php';
require 'classes/ZipManager.php';

$email = $_POST['email'];
$password = $_POST['password'];
$host = $_POST['imap'];
$port = $_POST['port'];

$backupDir = BACKUP_PATH . md5($email . time());

mkdir($backupDir, 0777, true);

// conectar IMAP
$imap = new ImapClient();
$imap->connect($email, $password, $host, $port);

// obtener carpetas
$folders = $imap->getFolders();

if (!$folders) {
    die("No se encontraron carpetas.");
}

// descargar correos
$downloader = new MailDownloader($imap);
$downloader->downloadAll($folders, $backupDir);

// cerrar conexión
$imap->close();

// crear zip
$zipFile = $backupDir . '.zip';

$zip = new ZipManager();
$zip->createZip($backupDir, $zipFile);

echo "<h3>Backup completado</h3>";
echo "<a href='backups/" . basename($zipFile) . "' download>Descargar ZIP</a>";