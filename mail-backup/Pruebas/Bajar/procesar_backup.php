<?php
require 'config.php';
session_start();
set_time_limit(0);
ignore_user_abort(true);

function setStatus($p, $s, $f = null) {
    file_put_contents('progress.json', json_encode(['percent' => $p, 'status' => $s, 'file' => $f]));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $pass  = $_POST['password'];
    $host  = $_POST['imap'];
    $limit = !empty($_POST['limite']) ? (int)$_POST['limite'] : 999999;

    setStatus(5, "Conectando a IMAP...");

    $mbox = @imap_open("{".$host.":993/imap/ssl}", $email, $pass);
    if (!$mbox) {
        setStatus(0, "Error: " . imap_last_error());
        exit;
    }

    $list = imap_getmailboxes($mbox, "{".$host.":993/imap/ssl}", "*");
    $tempDir = 'temp_bkp_' . time();
    mkdir($tempDir, 0777, true);

    $totalGlobal = 0;

    foreach ($list as $key => $val) {
        if ($totalGlobal >= $limit) break;

        $name = str_replace("{".$host.":993/imap/ssl}", "", $val->name);
        setStatus(20, "Escaneando: $name");

        @imap_reopen($mbox, $val->name);
        $ids = imap_search($mbox, 'ALL');

        if ($ids) {
            $folderPath = $tempDir . '/' . str_replace(['.', '/'], DIRECTORY_SEPARATOR, $name);
            if (!is_dir($folderPath)) mkdir($folderPath, 0777, true);

            foreach ($ids as $num) {
                if ($totalGlobal >= $limit) break;

                $header = imap_fetchheader($mbox, $num);
                $body = imap_body($mbox, $num);
                file_put_contents($folderPath . "/msg_$num.eml", $header . $body);

                $totalGlobal++;
                if ($totalGlobal % 5 == 0) {
                    setStatus(50, "Descargados: $totalGlobal correos...");
                }
            }
        }
    }

    // Crear ZIP
    setStatus(90, "Creando archivo ZIP...");
    $zipName = BACKUP_DIR . 'backup_' . str_replace(['@', '.'], '_', $email) . '_' . time() . '.zip';
    $zip = new ZipArchive();
    $zip->open($zipName, ZipArchive::CREATE);

    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tempDir));
    foreach ($files as $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen(realpath($tempDir)) + 1);
            $zip->addFile($filePath, $relativePath);
        }
    }
    $zip->close();

    // Limpiar
    array_map('unlink', glob("$tempDir/*.*"));
    rmdir($tempDir);
    imap_close($mbox);

    setStatus(100, "Backup finalizado", $zipName);
}