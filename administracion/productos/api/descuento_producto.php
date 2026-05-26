<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id'])) { http_response_code(403); exit; }

header('Content-Type: application/json; charset=utf-8');
$pdo = Conexion::conectar();

/* ── GET: obtener descuento activo de un producto ── */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $idProducto = (int)($_GET['id'] ?? 0);
    if (!$idProducto) { echo json_encode(['ok' => false]); exit; }

    $stmt = $pdo->prepare("
        SELECT idoferta, titulo, valor, activo
        FROM oferta
        WHERE productos_idproductos = ?
          AND tipo_panel = 'descuento'
        ORDER BY activo DESC, idoferta DESC
        LIMIT 1
    ");
    $stmt->execute([$idProducto]);
    $panel = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($panel) {
        echo json_encode(['ok' => true, 'panel' => $panel]);
    } else {
        echo json_encode(['ok' => true, 'panel' => null]);
    }
    exit;
}

/* ── POST: guardar o quitar descuento ── */
$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$accion = $body['accion'] ?? '';
$idProducto = (int)($body['id_producto'] ?? 0);

if (!$idProducto) { echo json_encode(['ok' => false, 'msg' => 'ID inválido']); exit; }

/* Obtener nombre del producto */
$stmtProd = $pdo->prepare("SELECT nombre FROM productos WHERE idproductos = ?");
$stmtProd->execute([$idProducto]);
$prod = $stmtProd->fetch(PDO::FETCH_ASSOC);
if (!$prod) { echo json_encode(['ok' => false, 'msg' => 'Producto no encontrado']); exit; }

/* Panel existente para este producto */
$stmtExiste = $pdo->prepare("
    SELECT idoferta FROM oferta
    WHERE productos_idproductos = ? AND tipo_panel = 'descuento'
    ORDER BY idoferta DESC LIMIT 1
");
$stmtExiste->execute([$idProducto]);
$panelExistente = $stmtExiste->fetchColumn();

if ($accion === 'guardar') {
    $valor = isset($body['valor']) && $body['valor'] !== '' ? (float)$body['valor'] : null;
    if ($valor === null || $valor <= 0) {
        echo json_encode(['ok' => false, 'msg' => 'El descuento debe ser mayor a 0']); exit;
    }
    $titulo = $prod['nombre'] . ' — ' . (int)$valor . '% OFF';

    if ($panelExistente) {
        $stmt = $pdo->prepare("UPDATE oferta SET titulo=?, valor=?, activo=1, tipo_panel='descuento', tipo='descuento' WHERE idoferta=?");
        $stmt->execute([$titulo, $valor, $panelExistente]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO oferta (titulo, tipo, tipo_panel, valor, activo, productos_idproductos, emoji) VALUES (?, 'descuento', 'descuento', ?, 1, ?, '💸')");
        $stmt->execute([$titulo, $valor, $idProducto]);
    }
    echo json_encode(['ok' => true]);

} elseif ($accion === 'quitar') {
    if ($panelExistente) {
        $stmt = $pdo->prepare("UPDATE oferta SET activo=0 WHERE idoferta=?");
        $stmt->execute([$panelExistente]);
    }
    echo json_encode(['ok' => true]);

} else {
    echo json_encode(['ok' => false, 'msg' => 'Acción desconocida']);
}
