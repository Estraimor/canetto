<?php
define('APP_BOOT', true);
require_once '../../../config/conexion.php';
require_once '../../../config/audit.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['usuario_id'])) { http_response_code(401); echo json_encode(['ok'=>false]); exit; }
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true) ?: [];

$id          = (int)($data['id'] ?? 0);
$codigo      = strtoupper(trim($data['codigo'] ?? ''));
$descripcion = trim($data['descripcion'] ?? '');
$tipo        = in_array($data['tipo'] ?? '', ['porcentaje','fijo','envio_gratis']) ? $data['tipo'] : 'porcentaje';
$valor       = max(0, (float)($data['valor'] ?? 0));
$min_pedido  = max(0, (float)($data['min_pedido'] ?? 0));
$max_usos    = isset($data['max_usos']) && $data['max_usos'] !== '' ? max(1,(int)$data['max_usos']) : null;
$un_uso      = isset($data['un_uso_por_usuario']) ? (int)$data['un_uso_por_usuario'] : 1;
$fecha_ini   = $data['fecha_inicio'] ?? null ?: null;
$fecha_fin   = $data['fecha_fin']    ?? null ?: null;
$activo      = isset($data['activo']) ? (int)$data['activo'] : 1;

if (!$codigo || ($tipo !== 'envio_gratis' && $valor <= 0)) {
    echo json_encode(['ok'=>false,'msg'=>'Código requerido. El valor es obligatorio para cupones de descuento']); exit;
}
if ($tipo === 'envio_gratis') { $valor = 0; }
if ($tipo === 'porcentaje' && $valor > 100) {
    echo json_encode(['ok'=>false,'msg'=>'El porcentaje no puede superar 100%']); exit;
}

try {
    $pdo = Conexion::conectar();

    if ($id) {
        $pdo->prepare("UPDATE cupones SET
            codigo=?, descripcion=?, tipo=?, valor=?, min_pedido=?, max_usos=?,
            un_uso_por_usuario=?, fecha_inicio=?, fecha_fin=?, activo=?, updated_at=NOW()
            WHERE id=?")
            ->execute([$codigo,$descripcion,$tipo,$valor,$min_pedido,$max_usos,$un_uso,$fecha_ini,$fecha_fin,$activo,$id]);
        audit($pdo,'editar','cupones',"Editó cupón: {$codigo} (ID:{$id})");
    } else {
        // Verificar código único
        $exists = $pdo->prepare("SELECT COUNT(*) FROM cupones WHERE codigo=?");
        $exists->execute([$codigo]);
        if ($exists->fetchColumn() > 0) {
            echo json_encode(['ok'=>false,'msg'=>"El código '{$codigo}' ya existe"]); exit;
        }
        $pdo->prepare("INSERT INTO cupones (codigo,descripcion,tipo,valor,min_pedido,max_usos,un_uso_por_usuario,fecha_inicio,fecha_fin,activo)
            VALUES (?,?,?,?,?,?,?,?,?,?)")
            ->execute([$codigo,$descripcion,$tipo,$valor,$min_pedido,$max_usos,$un_uso,$fecha_ini,$fecha_fin,$activo]);
        $id = $pdo->lastInsertId();
        audit($pdo,'crear','cupones',"Creó cupón: {$codigo} | {$tipo} {$valor}");
    }

    echo json_encode(['ok'=>true,'id'=>(int)$id]);
} catch (Throwable $e) {
    echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
}
