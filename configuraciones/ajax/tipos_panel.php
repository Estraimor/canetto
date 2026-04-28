<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
header('Content-Type: application/json');

$pdo = Conexion::conectar();

// Crear tabla con columna sistema si no existe
$pdo->exec("CREATE TABLE IF NOT EXISTS tipos_panel (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    clave   VARCHAR(40)  NOT NULL UNIQUE,
    label   VARCHAR(60)  NOT NULL,
    emoji   VARCHAR(8)   NOT NULL DEFAULT '📌',
    color   VARCHAR(20)  NOT NULL DEFAULT '#888888',
    activo  TINYINT(1)   NOT NULL DEFAULT 1,
    sistema TINYINT(1)   NOT NULL DEFAULT 0,
    orden   INT          NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Agregar columna sistema si no existe (idempotente)
try { $pdo->exec("ALTER TABLE tipos_panel ADD COLUMN sistema TINYINT(1) NOT NULL DEFAULT 0 AFTER activo"); } catch (Throwable $e) {}

// Tipos del sistema — fijos, no se pueden borrar ni inactivar
$sistemaTypes = [
    ['promo',       'Promo',        '📢', '#c88e99', 0],
    ['bienvenida',  'Bienvenida',   '👋', '#1d9e75', 1],
    ['regalo',      'Regalo',       '🎁', '#7c3aed', 2],
    ['soporte',     'Soporte',      '🛟', '#0891b2', 3],
    ['temporada',   'Temporada',    '🌸', '#f59e0b', 4],
    ['descuento',   'Descuento',    '💸', '#dc2626', 5],
    ['novedad',     'Novedad',      '✨', '#8b5cf6', 6],
    ['anuncio',     'Anuncio',      '📣', '#0ea5e9', 7],
    ['informativo', 'Informativo',  'ℹ️',  '#64748b', 8],
    ['marketing',   'Marketing',    '🚀', '#f97316', 9],
];

$ins = $pdo->prepare("INSERT IGNORE INTO tipos_panel (clave,label,emoji,color,orden,sistema) VALUES (?,?,?,?,?,1)");
foreach ($sistemaTypes as $d) $ins->execute($d);

// Marcar como sistema los tipos por defecto (aunque ya existieran)
$clavesSistema = array_column($sistemaTypes, 0);
$placeholders  = implode(',', array_fill(0, count($clavesSistema), '?'));
$pdo->prepare("UPDATE tipos_panel SET sistema=1 WHERE clave IN ($placeholders)")->execute($clavesSistema);

$accion = $_GET['accion'] ?? $_POST['accion'] ?? 'listar';

if ($accion === 'listar') {
    $rows = $pdo->query("SELECT * FROM tipos_panel ORDER BY activo DESC, sistema DESC, orden ASC")->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rows);
    exit;
}

if ($accion === 'activar') {
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare("UPDATE tipos_panel SET activo=1 WHERE id=?")->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

if ($accion === 'guardar') {
    $id    = (int)($_POST['id'] ?? 0);
    $label = trim($_POST['label'] ?? '');
    $emoji = trim($_POST['emoji'] ?? '📌');
    $color = trim($_POST['color'] ?? '#888888');

    if (!$label) { echo json_encode(['success'=>false,'message'=>'El nombre es obligatorio']); exit; }

    if (!$id) {
        $clave = strtolower(preg_replace('/[^a-z0-9]/i', '', iconv('UTF-8','ASCII//TRANSLIT', $label)));
        if (!$clave) $clave = 'tipo_' . time();
        $existe = $pdo->prepare("SELECT id FROM tipos_panel WHERE clave=?");
        $existe->execute([$clave]);
        if ($existe->fetch()) $clave .= '_' . time();
        $orden = (int)$pdo->query("SELECT COALESCE(MAX(orden),0)+1 FROM tipos_panel")->fetchColumn();
        $pdo->prepare("INSERT INTO tipos_panel (clave,label,emoji,color,orden,sistema) VALUES (?,?,?,?,?,0)")
            ->execute([$clave, $label, $emoji, $color, $orden]);
    } else {
        // No se puede editar el label/emoji/color de tipos del sistema
        $isSistema = (int)$pdo->prepare("SELECT sistema FROM tipos_panel WHERE id=?")->execute([$id]) && false;
        $row = $pdo->prepare("SELECT sistema FROM tipos_panel WHERE id=?");
        $row->execute([$id]);
        $sys = (int)($row->fetchColumn() ?: 0);
        if ($sys) { echo json_encode(['success'=>false,'message'=>'Los tipos del sistema no se pueden editar.']); exit; }
        $pdo->prepare("UPDATE tipos_panel SET label=?, emoji=?, color=? WHERE id=?")->execute([$label, $emoji, $color, $id]);
    }
    echo json_encode(['success' => true]);
    exit;
}

if ($accion === 'eliminar') {
    $id  = (int)($_POST['id'] ?? 0);
    $row = $pdo->prepare("SELECT sistema FROM tipos_panel WHERE id=?");
    $row->execute([$id]);
    if ((int)$row->fetchColumn()) {
        echo json_encode(['success'=>false,'message'=>'Este tipo es del sistema y no se puede desactivar.']);
        exit;
    }
    $pdo->prepare("UPDATE tipos_panel SET activo=0 WHERE id=?")->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

if ($accion === 'eliminar_hard') {
    $id  = (int)($_POST['id'] ?? 0);
    $row = $pdo->prepare("SELECT sistema FROM tipos_panel WHERE id=?");
    $row->execute([$id]);
    if ((int)$row->fetchColumn()) {
        echo json_encode(['success'=>false,'message'=>'Los tipos del sistema son fijos y no se pueden eliminar.']);
        exit;
    }
    $enUso = $pdo->prepare("SELECT COUNT(*) FROM oferta o JOIN tipos_panel tp ON tp.clave=o.tipo_panel WHERE tp.id=?");
    $enUso->execute([$id]);
    if ((int)$enUso->fetchColumn() > 0) {
        echo json_encode(['success'=>false,'message'=>'Este tipo tiene paneles asociados. Eliminá los paneles primero.']);
        exit;
    }
    $pdo->prepare("DELETE FROM tipos_panel WHERE id=?")->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success'=>false,'message'=>'Acción desconocida']);
