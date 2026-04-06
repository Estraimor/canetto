<?php
define('APP_BOOT', true);
session_start();
require_once __DIR__ . '/../../../config/conexion.php';
require_once __DIR__ . '/../../../config/audit.php';

header('Content-Type: application/json');

$pdo        = Conexion::conectar();
$usuario_id = $_SESSION['usuario_id'] ?? null;

// Agregar columnas nuevas si no existen (migración segura)
foreach ([
    "ALTER TABLE compra_materia_prima ADD COLUMN unidad_compra   VARCHAR(10)    NULL AFTER costo",
    "ALTER TABLE compra_materia_prima ADD COLUMN cantidad_original DECIMAL(10,3) NULL AFTER unidad_compra",
] as $sql) { try { $pdo->exec($sql); } catch (Throwable $e) {} }

$data             = json_decode(file_get_contents('php://input'), true);
$idproveedor      = intval($data['idproveedor']        ?? 0);
$idmateria        = intval($data['idmateria_prima']     ?? 0);
$cantidad         = floatval($data['cantidad']          ?? 0);  // ya en unidad BASE
$cantidad_original= isset($data['cantidad_original'])   ? floatval($data['cantidad_original']) : null;
$unidad_compra    = trim($data['unidad_compra']         ?? ''); // abreviatura ej. "Kg"
$costo            = (isset($data['costo']) && $data['costo'] !== null && $data['costo'] !== '')
                    ? floatval($data['costo']) : null;          // costo por unidad de compra
$observaciones    = trim($data['observaciones']         ?? '');

if (!$idproveedor || !$idmateria || $cantidad <= 0) {
    echo json_encode(['ok' => false, 'msg' => 'Datos incompletos o inválidos']);
    exit;
}

try {
    // Nombres para auditoría
    $stmtMP = $pdo->prepare("
        SELECT mp.nombre, um.abreviatura AS unidad_base
        FROM materia_prima mp
        LEFT JOIN unidad_medida um ON um.idunidad_medida = mp.unidad_medida_idunidad_medida
        WHERE mp.idmateria_prima = ?
    ");
    $stmtMP->execute([$idmateria]);
    $mp = $stmtMP->fetch();
    $nombreMateria = $mp['nombre'] ?? "ID {$idmateria}";
    $unidadBase    = $mp['unidad_base'] ?? '';

    $stmtProv = $pdo->prepare("SELECT nombre FROM proveedor WHERE idproveedor = ?");
    $stmtProv->execute([$idproveedor]);
    $nombreProveedor = $stmtProv->fetchColumn() ?: "ID {$idproveedor}";

    $pdo->beginTransaction();

    // Stock anterior
    $stmtAntes = $pdo->prepare("SELECT stock_actual FROM materia_prima WHERE idmateria_prima = ?");
    $stmtAntes->execute([$idmateria]);
    $stockAnterior = $stmtAntes->fetchColumn();
    if ($stockAnterior === false) throw new Exception('Materia prima no encontrada');

    // Actualizar stock (cantidad ya viene en unidad BASE)
    $pdo->prepare("
        UPDATE materia_prima SET stock_actual = stock_actual + ?, updated_at = NOW()
        WHERE idmateria_prima = ?
    ")->execute([$cantidad, $idmateria]);

    $nuevoStock = $stockAnterior + $cantidad;

    // Actualizar costo en pivot proveedor-materia (guardamos costo por unidad de compra)
    if ($costo !== null) {
        $pdo->prepare("
            UPDATE materia_prima_has_proveedor
            SET costo = ?, updated_at = NOW()
            WHERE materia_prima_idmateria_prima = ? AND proveedor_idproveedor = ?
        ")->execute([$costo, $idmateria, $idproveedor]);
    }

    // Historial
    try {
        $pdo->prepare("
            INSERT INTO compra_materia_prima
            (proveedor_idproveedor, materia_prima_idmateria_prima,
             cantidad, costo, unidad_compra, cantidad_original,
             stock_anterior, stock_nuevo, observaciones, usuario_id, created_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,NOW())
        ")->execute([
            $idproveedor, $idmateria,
            $cantidad, $costo,
            $unidad_compra ?: null,
            $cantidad_original !== null ? $cantidad_original : null,
            $stockAnterior, $nuevoStock,
            $observaciones ?: null, $usuario_id,
        ]);
    } catch (PDOException $ignored) {}

    $pdo->commit();

    // Descripción legible para auditoría
    $cantDesc = ($cantidad_original !== null && $unidad_compra)
        ? "{$cantidad_original} {$unidad_compra} = {$cantidad} {$unidadBase}"
        : "{$cantidad} {$unidadBase}";
    $costoDesc = $costo !== null
        ? '$' . number_format($costo, 2) . ($unidad_compra ? "/{$unidad_compra}" : '')
        : 'sin costo';

    audit($pdo, 'registrar', 'compras',
        "Compra: {$nombreMateria} × {$cantDesc} | Proveedor: {$nombreProveedor}" .
        " | Costo: {$costoDesc} | Stock {$stockAnterior} → {$nuevoStock}" .
        ($observaciones ? " | Obs: {$observaciones}" : '')
    );

    echo json_encode(['ok' => true, 'stock_nuevo' => $nuevoStock]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
