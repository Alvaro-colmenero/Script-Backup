<?php
session_start();
// Leemos y cerramos inmediatamente para no bloquear el script de backup
$percent = isset($_SESSION['progress_percent']) ? $_SESSION['progress_percent'] : 0;
$status  = isset($_SESSION['progress_status']) ? $_SESSION['progress_status'] : 'Cargando...';
session_write_close();

header('Content-Type: application/json');
echo json_encode(['percent' => $percent, 'status' => $status]);