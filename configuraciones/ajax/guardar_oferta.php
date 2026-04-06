<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/audit.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id'])) { http_response_code(403); exit; }

header('Content-Type: application/json; charset=utf-8');
$pdo = Conexion::conectar();

$id                    = !empty($_POST['idoferta'])              ? (int)$_POST['idoferta']    : 0;
$titulo                = trim($_POST['titulo']                   ?? '');
$descripcion           = trim($_POST['descripcion']              ?? '');
$emoji                 = trim($_POST['emoji']                    ?? '🎉');
$tipo                  = trim($_POST['tipo']                     ?? 'promo');
$valor                 = ($_POST['valor'] ?? '') !== '' ? (float)$_POST['valor'] : null;
$activo                = (int)($_POST['activo']                  ?? 1);
$fecha_inicio          = ($_POST['fecha_inicio'] ?? '') !== '' ? $_POST['fecha_inicio'] : null;
$fecha_fin             = ($_POST['fecha_fin']    ?? '') !== '' ? $_POST['fecha_fin']    : null;
$imagenActual          = trim($_POST['imagen_actual']            ?? '');
$productos_idproductos = ($_POST['productos_idproductos'] ?? '') !== '' ? (int)$_POST['productos_idproductos'] : null;

if (!$titulo) { echo json_encode(['ok'=>false,'msg'=>'El título es obligatorio.']); exit; }

// Handle image upload
$imagenFinal = $imagenActual;
if (!empty($_FILES['imagen']['name'])) {
    $file     = $_FILES['imagen'];
    $allowed  = ['image/jpeg','image/png','image/webp'];
    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $extMap   = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];

    if (!in_array($file['type'], $allowed) || $file['size'] > 2 * 1024 * 1024) {
        echo json_encode(['ok'=>false,'msg'=>'Imagen inválida. JPG/PNG/WebP máx 2MB.']); exit;
    }
    $dir      = __DIR__ . '/../../img/ofertas/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $filename = uniqid('oferta_') . '.' . $extMap[$file['type']];
    if (!move_uploaded_file($file['tmp_name'], $dir . $filename)) {
        echo json_encode(['ok'=>false,'msg'=>'No se pudo guardar la imagen.']); exit;
    }
    // Delete old image if it exists
    if ($imagenActual && file_exists($dir . $imagenActual)) {
        @unlink($dir . $imagenActual);
    }
    $imagenFinal = $filename;
}
// If user removed image (imagen_actual cleared) and no new file
if (empty($_POST['imagen_actual']) && empty($_FILES['imagen']['name'])) {
    // User cleared the image
    if ($imagenActual && file_exists(__DIR__ . '/../../img/ofertas/' . $imagenActual)) {
        @unlink(__DIR__ . '/../../img/ofertas/' . $imagenActual);
    }
    $imagenFinal = null;
}

if ($id) {
    $stmt = $pdo->prepare("UPDATE oferta SET titulo=?,descripcion=?,emoji=?,tipo=?,valor=?,imagen=?,activo=?,fecha_inicio=?,fecha_fin=?,productos_idproductos=? WHERE idoferta=?");
    $stmt->execute([$titulo, $descripcion ?: null, $emoji, $tipo, $valor, $imagenFinal, $activo, $fecha_inicio, $fecha_fin, $productos_idproductos, $id]);
    audit($pdo, 'editar', 'ofertas', 'Editó oferta: ' . $titulo);
} else {
    $stmt = $pdo->prepare("INSERT INTO oferta (titulo,descripcion,emoji,tipo,valor,imagen,activo,fecha_inicio,fecha_fin,productos_idproductos) VALUES (?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([$titulo, $descripcion ?: null, $emoji, $tipo, $valor, $imagenFinal, $activo, $fecha_inicio, $fecha_fin, $productos_idproductos]);
    audit($pdo, 'crear', 'ofertas', 'Creó oferta: ' . $titulo);
}

echo json_encode(['ok'=>true]);
