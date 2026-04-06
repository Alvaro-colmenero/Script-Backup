<?php
// progress.php
header('Content-Type: application/json');
if (file_exists('progress.json')) {
    echo file_get_contents('progress.json');
} else {
    echo json_encode(['percent' => 0, 'status' => 'Esperando...']);
}