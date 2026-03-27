<?php
require 'config.php';
require 'classes/ImapClient.php';
require 'classes/MailDownloader.php';
require 'classes/ZipManager.php';

$login_user = $argv[1];
$password = $argv[2];
$host = $argv[3];
$port = $argv[4];
$progress_file = $argv[5];
$backupDir = $argv[6];

try {
    updateProgress(0, "Lanzado el subproceso... ", $progress_file);
    $imap = new ImapClient();
    $imap->connect($login_user, $password, $host, $port);
    $folders = $imap->getFolders();

    $downloader = new MailDownloader($imap);
    $downloader->setProgressFile($progress_file); // Vinculamos el archivo de texto

    $downloader->downloadAll($folders, $backupDir);

    $downloader->updateRealProgress(95, "Comprimiendo backup...");

    $zipFile = $backupDir . '.zip';
    $zip = new ZipManager();
    $zip->createZip($backupDir, $zipFile);

    $downloader->updateRealProgress(100, "¡Finalizado!");

} catch (Exception $e) {
    updateProgress(0, "Error: " . $e->getMessage(), $progress_file);
    //echo "<script>window.parent.document.getElementById('resultArea').innerHTML = '<b style=\"color:red\">Error: " . addslashes($e->getMessage()) . "</b>';</script>";
}

function updateProgress($percent, $status, $progressFile): void
{
    if ($progressFile) {
        file_put_contents($progressFile, json_encode([
            'percent' => $percent,
            'status' => $status
        ]));
    }
}