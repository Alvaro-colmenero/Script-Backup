<?php
// procesar_restauracion.php
session_start();
set_time_limit(0); // Evita que el script se corte por tiempo
ignore_user_abort(true);

function updateProgress($percent, $status) {
    file_put_contents('progress.json', json_encode(['percent' => $percent, 'status' => $status]));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $pass  = $_POST['password'];
    $host  = $_POST['imap'];
    $zip   = $_FILES['zip_file'];

    updateProgress(0, "Iniciando...");

    // 1. Conexión IMAP
    $connectionString = "{" . $host . ":993/imap/ssl}";
    $mbox = @imap_open($connectionString, $email, $pass);

    if (!$mbox) {
        updateProgress(0, "Error: " . imap_last_error());
        exit;
    }

    // 2. Descomprimir temporalmente
    $tempDir = 'temp_' . time();
    $zipArchive = new ZipArchive;
    if ($zipArchive->open($zip['tmp_name']) === TRUE) {
        $zipArchive->extractTo($tempDir);
        $zipArchive->close();
    }

    // 3. Buscar archivos .eml
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tempDir));
    $emlFiles = [];
    foreach ($files as $file) {
        if ($file->isFile() && $file->getExtension() == 'eml') {
            $emlFiles[] = $file->getPathname();
        }
    }

    $total = count($emlFiles);
    $count = 0;

    foreach ($emlFiles as $filePath) {
        $content = file_get_contents($filePath);

        // Determinar carpeta (Estructura original)
        $relPath = str_replace($tempDir . DIRECTORY_SEPARATOR, '', dirname($filePath));
        $folder = ($relPath == dirname($filePath)) ? "INBOX" : str_replace(DIRECTORY_SEPARATOR, '.', $relPath);

        // Intentar crear la carpeta por si no existe
        @imap_createmailbox($mbox, $connectionString . $folder);

        // Subir correo
        if (imap_append($mbox, $connectionString . $folder, $content, "\\Seen")) {
            $count++;
            $percent = round(($count / $total) * 100);
            updateProgress($percent, "Subiendo a $folder...");
        }
    }

    // Limpieza
    imap_close($mbox);
    eliminarCarpeta($tempDir);
    updateProgress(100, "Completado: $count correos restaurados.");
}

function eliminarCarpeta($dir) {
    if (!file_exists($dir)) return;
    $files = array_diff(scandir($dir), array('.', '..'));
    foreach ($files as $file) {
        (is_dir("$dir/$file")) ? eliminarCarpeta("$dir/$file") : unlink("$dir/$file");
    }
    rmdir($dir);
}