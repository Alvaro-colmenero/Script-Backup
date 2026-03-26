<?php
header('Content-Type: application/json');

$backup_id = isset($_COOKIE['backup_id']) ? $_COOKIE['backup_id'] : '';
$progress_file = __DIR__ . "/temp_progress_{$backup_id}.txt";

if ($backup_id && file_exists($progress_file)) {
    echo file_get_contents($progress_file);

    // Si el proceso terminó (100%), borramos el archivo temporal
    $data = json_decode(file_get_contents($progress_file), true);

    if (isset($data['percent']) && $data['percent'] >= 100) {
        // unlink($progress_file); // Opcional: borrar al terminar
    }
} else {
    echo json_encode(['percent' => 0, 'status' => 'Iniciando transferencia...']);
}