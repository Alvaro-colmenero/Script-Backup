<?php

use classes\ImapClient;
use classes\MailDownloader;
use classes\ZipManager;

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
    $imap = new ImapClient();
    updateProgress(0, "Conectando con servidor... ", $progress_file);
    $imap->connect($login_user, $password, $host, $port);

    updateProgress(0, "Recopilando directorios... ", $progress_file);
    $folders = $imap->getFolders();

    $downloader = new MailDownloader($imap);
    $downloader->setProgressFile($progress_file); // Vinculamos el archivo de texto

    $downloader->downloadAll($folders, $backupDir);

    updateProgress(95, "Comprimiendo backup...", $progress_file);

    $zipFile = $backupDir . '.zip';
    $zip = new ZipManager();
    if(!$zip->createZip($backupDir, $zipFile))
        updateProgress(95, "Error: Fallo al crear el zip.", $progress_file);

    updateProgress(100, "¡Finalizado!", $progress_file);

} catch (Exception $e) {
    updateProgress(0, "Error: " . $e->getMessage(), $progress_file);
} finally {
    recursiveRemoveDirectory($backupDir);
    sleep(5);
    unlink($progress_file);
    if (isset($imap)) $imap?->close();
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

function recursiveRemoveDirectory($directory)
{
    foreach(glob("{$directory}/*") as $file)
    {
        if(is_dir($file)) {
            recursiveRemoveDirectory($file);
        } else {
            unlink($file);
        }
    }
    rmdir($directory);
}