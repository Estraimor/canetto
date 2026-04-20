<?php
/**
 * API pública: calcula costo de envío según distancia.
 * GET/POST: lat_entrega, lng_entrega
 * Devuelve: { ok, costo, distancia_km, tramo }
 */
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
header('Content-Type: application/json; charset=utf-8');

$lat = isset($_REQUEST['lat']) && is_numeric($_REQUEST['lat']) ? (float)$_REQUEST['lat'] : null;
$lng = isset($_REQUEST['lng']) && is_numeric($_REQUEST['lng']) ? (float)$_REQUEST['lng'] : null;

if ($lat === null || $lng === null) {
    echo json_encode(['ok' => false, 'msg' => 'Coordenadas requeridas']); exit;
}

try {
    $pdo = Conexion::conectar();

    /* ── Crear tabla de tarifas si no existe ── */
    $pdo->exec("CREATE TABLE IF NOT EXISTS tarifas_envio (
        id          INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        km_desde    DECIMAL(5,1) NOT NULL DEFAULT 0,
        km_hasta    DECIMAL(5,1) NOT NULL DEFAULT 5,
        precio      DECIMAL(10,2) NOT NULL DEFAULT 0,
        descripcion VARCHAR(100) NULL,
        activo      TINYINT(1) NOT NULL DEFAULT 1,
        updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    /* ── Seed con tarifas realistas en ARS (nafta ~$1.600/L, 10L/100km, ida+vuelta) ── */
    $count = (int)$pdo->query("SELECT COUNT(*) FROM tarifas_envio")->fetchColumn();
    if ($count === 0) {
        $pdo->exec("INSERT INTO tarifas_envio (km_desde, km_hasta, precio, descripcion) VALUES
            (0,    3,   4500,  'Zona cercana (0–3 km)'),
            (3,    6,   7000,  'Zona media (3–6 km)'),
            (6,    10,  10500, 'Zona media-lejana (6–10 km)'),
            (10,   15,  15000, 'Zona lejana (10–15 km)'),
            (15,   25,  21000, 'Zona muy lejana (15–25 km)'),
            (25,   999, 29000, 'Zona extrema (+25 km)')
        ");
    }

    /* ── Obtener sucursal origen (la más cercana con coords) ── */
    $sucursales = $pdo->query("SELECT latitud, longitud FROM sucursal WHERE activo=1 AND latitud IS NOT NULL AND longitud IS NOT NULL")->fetchAll(PDO::FETCH_ASSOC);

    if (empty($sucursales)) {
        echo json_encode(['ok' => false, 'msg' => 'No hay sucursales con coordenadas configuradas']); exit;
    }

    /* Haversine */
    function hvKm(float $la1, float $lo1, float $la2, float $lo2): float {
        $R = 6371; $d = M_PI / 180;
        $a = sin(($la2-$la1)*$d/2)**2 + cos($la1*$d)*cos($la2*$d)*sin(($lo2-$lo1)*$d/2)**2;
        return 2 * $R * atan2(sqrt($a), sqrt(1-$a));
    }

    $minDist = INF;
    foreach ($sucursales as $s) {
        $d = hvKm((float)$s['latitud'], (float)$s['longitud'], $lat, $lng);
        if ($d < $minDist) $minDist = $d;
    }

    /* ── Buscar tarifa correspondiente ── */
    $tarifa = $pdo->prepare("SELECT precio, descripcion FROM tarifas_envio WHERE activo=1 AND km_desde <= ? AND km_hasta > ? ORDER BY km_desde ASC LIMIT 1");
    $tarifa->execute([$minDist, $minDist]);
    $row = $tarifa->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        /* Fallback: tarifa más alta disponible */
        $row = $pdo->query("SELECT precio, descripcion FROM tarifas_envio WHERE activo=1 ORDER BY km_hasta DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    }

    $costo = $row ? (float)$row['precio'] : 25000.0;

    echo json_encode([
        'ok'          => true,
        'costo'       => $costo,
        'distancia_km'=> round($minDist, 1),
        'tramo'       => $row['descripcion'] ?? '',
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
