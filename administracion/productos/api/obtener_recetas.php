<?php

define('APP_BOOT', true);
require_once __DIR__ . '/../../../config/conexion.php';

$pdo = Conexion::conectar();

$stmt = $pdo->query("
SELECT idrecetas, nombre
FROM recetas
ORDER BY nombre ASC
");

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
