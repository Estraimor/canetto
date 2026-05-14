<?php

define('APP_BOOT', true);
require_once __DIR__ . '/../../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['usuario_id'])) { http_response_code(401); echo json_encode(['error'=>'No autorizado']); exit; }

$pdo = Conexion::conectar();

$stmt = $pdo->query("
SELECT idrecetas, nombre
FROM recetas
ORDER BY nombre ASC
");

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
