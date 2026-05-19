<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

$data   = json_decode(file_get_contents('php://input'), true) ?: [];
$codigo = strtoupper(trim($data['codigo'] ?? ''));
$total  = (float)($data['total'] ?? 0);
$uid    = $_SESSION['tienda_cliente_id'] ?? null;

if (!$codigo) { echo json_encode(['ok'=>false,'msg'=>'Ingresá un código de cupón']); exit; }

try {
    $pdo = Conexion::conectar();
    // La validación de fechas la hace MySQL con CURDATE() para evitar problemas de timezone en PHP
    $stmt = $pdo->prepare("
        SELECT *,
            CASE
                WHEN fecha_inicio IS NOT NULL AND CURDATE() < fecha_inicio THEN 'pendiente'
                WHEN fecha_fin    IS NOT NULL AND CURDATE() > fecha_fin    THEN 'vencido'
                ELSE 'vigente'
            END AS estado_fecha
        FROM cupones
        WHERE codigo = ? AND activo = 1
    ");
    $stmt->execute([$codigo]);
    $c = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$c) { echo json_encode(['ok'=>false,'msg'=>'Cupón inválido o inactivo']); exit; }

    if ($c['estado_fecha'] === 'pendiente')
        { echo json_encode(['ok'=>false,'msg'=>'Este cupón aún no está vigente']); exit; }
    if ($c['estado_fecha'] === 'vencido')
        { echo json_encode(['ok'=>false,'msg'=>'Este cupón ya venció']); exit; }
    if ($c['max_usos'] && $c['usos_actuales'] >= $c['max_usos'])
        { echo json_encode(['ok'=>false,'msg'=>'Este cupón ya alcanzó su límite de usos']); exit; }

    if ($c['un_uso_por_usuario'] && $uid) {
        $usoStmt = $pdo->prepare("SELECT COUNT(*) FROM cupones_usos WHERE cupon_id=? AND usuario_id=?");
        $usoStmt->execute([$c['id'], $uid]);
        if ($usoStmt->fetchColumn() > 0)
            { echo json_encode(['ok'=>false,'msg'=>'Ya usaste este cupón anteriormente']); exit; }
    }

    if ($c['min_pedido'] > 0 && $total < $c['min_pedido'])
        { echo json_encode(['ok'=>false,'msg'=>'Este cupón requiere un pedido mínimo de $'.number_format($c['min_pedido'],0,',','.')]); exit; }

    // Calcular descuento
    $descuento = $c['tipo'] === 'porcentaje'
        ? round($total * $c['valor'] / 100, 2)
        : min((float)$c['valor'], $total);

    echo json_encode([
        'ok'          => true,
        'codigo'      => $c['codigo'],
        'descripcion' => $c['descripcion'],
        'tipo'        => $c['tipo'],
        'valor'       => (float)$c['valor'],
        'descuento'   => $descuento,
        'total_final' => max(0, $total - $descuento),
    ]);

} catch (Throwable $e) {
    echo json_encode(['ok'=>false,'msg'=>'Error al validar el cupón']);
}
