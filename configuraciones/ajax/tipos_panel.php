<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
header('Content-Type: application/json');

$pdo = Conexion::conectar();

// Crear tabla si no existe
$pdo->exec("CREATE TABLE IF NOT EXISTS tipos_panel (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    clave   VARCHAR(40)  NOT NULL UNIQUE,
    label   VARCHAR(60)  NOT NULL,
    emoji   VARCHAR(8)   NOT NULL DEFAULT '📌',
    color   VARCHAR(20)  NOT NULL DEFAULT '#888888',
    activo  TINYINT(1)   NOT NULL DEFAULT 1,
    orden   INT          NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Seed tipos por defecto si la tabla está vacía
$count = $pdo->query("SELECT COUNT(*) FROM tipos_panel")->fetchColumn();
if ($count == 0) {
    $defaults = [
        ['promo',      'Promo',      '📢', '#c88e99', 0],
        ['bienvenida', 'Bienvenida', '👋', '#1d9e75', 1],
        ['regalo',     'Regalo',     '🎁', '#7c3aed', 2],
        ['soporte',    'Soporte',    '🛟', '#0891b2', 3],
        ['temporada',  'Temporada',  '🌸', '#f59e0b', 4],
        ['descuento',  'Descuento',  '💸', '#dc2626', 5],
    ];
    $ins = $pdo->prepare("INSERT INTO tipos_panel (clave,label,emoji,color,orden) VALUES (?,?,?,?,?)");
    foreach ($defaults as $d) $ins->execute($d);
}

$accion = $_GET['accion'] ?? $_POST['accion'] ?? 'listar';

if ($accion === 'listar') {
    // Devuelve activos e inactivos para que el frontend pueda mostrar ambos
    $rows = $pdo->query("SELECT * FROM tipos_panel ORDER BY activo DESC, orden ASC")->fetchAll(PDO::FETCH_ASSOC);
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

    // Generar clave desde el label si es nuevo
    if (!$id) {
        $clave = strtolower(preg_replace('/[^a-z0-9]/i', '', iconv('UTF-8','ASCII//TRANSLIT', $label)));
        if (!$clave) $clave = 'tipo_' . time();
        // Verificar unicidad
        $existe = $pdo->prepare("SELECT id FROM tipos_panel WHERE clave=?");
        $existe->execute([$clave]);
        if ($existe->fetch()) $clave .= '_' . time();

        $orden = (int)$pdo->query("SELECT COALESCE(MAX(orden),0)+1 FROM tipos_panel")->fetchColumn();
        $pdo->prepare("INSERT INTO tipos_panel (clave,label,emoji,color,orden) VALUES (?,?,?,?,?)")
            ->execute([$clave, $label, $emoji, $color, $orden]);
    } else {
        $pdo->prepare("UPDATE tipos_panel SET label=?, emoji=?, color=? WHERE id=?")
            ->execute([$label, $emoji, $color, $id]);
    }
    echo json_encode(['success' => true]);
    exit;
}

if ($accion === 'eliminar') {
    // Soft delete (inactivar) — siempre permitido
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare("UPDATE tipos_panel SET activo=0 WHERE id=?")->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

if ($accion === 'eliminar_hard') {
    // Borrado físico — solo si no tiene paneles
    $id = (int)($_POST['id'] ?? 0);
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
