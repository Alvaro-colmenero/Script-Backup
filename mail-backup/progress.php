<?php
session_start();
header('Content-Type: application/json');

// Devolvemos el porcentaje guardado en la sesión
echo json_encode([
    'percentage' => $_SESSION['backup_progress'] ?? 0,
    'status' => $_SESSION['backup_status'] ?? 'Iniciando...'
]);