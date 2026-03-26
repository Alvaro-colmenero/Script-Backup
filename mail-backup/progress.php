<?php
require 'config.php'; // Para obtener TEMP_PATH
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate'); // Evita que el navegador cachee la respuesta

// Recuperar el ID de la cookie
$backup_id = $_COOKIE['backup_id'] ?? '';
$progress_file = TEMP_PATH . "progress_{$backup_id}.txt";

if ($backup_id && file_exists($progress_file)) {
    // Leemos el contenido actual del archivo
    $content = file_get_contents($progress_file);
    echo $content;

    // Opcional: Si llegó al 100%, podrías borrarlo aquí después de unos segundos
    /*
    $data = json_decode($content, true);
    if (isset($data['percent']) && $data['percent'] >= 100) {
        // unlink($progress_file);
    }
    */
} else {
    // Estado por defecto si el archivo aún no se crea
    echo json_encode([
        'percent' => 0,
        'status' => 'Iniciando transferencia...'
    ]);
}