<?php
/**
 * API estado de tienda — soporta 3 modos: abierta | solo_vista | cerrada
 * GET  → devuelve estado efectivo (con lógica de horario)
 * POST → cambia estado (requiere sesión admin)
 *   accion: set_modo  → modo: abierta|solo_vista|cerrada  + opcional mensaje
 *   accion: mensaje   → actualiza mensaje de cierre
 */
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/cors.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

$pdo = Conexion::conectar();

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS configuracion_tienda (
        clave   VARCHAR(60) PRIMARY KEY,
        valor   TEXT        NOT NULL,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    $defaults = [
        ['tienda_abierta',          '1'],
        ['tienda_mensaje_cierre',   'La tienda está temporalmente cerrada. ¡Volvemos pronto!'],
        ['tienda_modo',             'abierta'],
        ['horario_activado',        '0'],
        ['horario_apertura',        '09:00'],
        ['horario_cierre',          '21:00'],
        ['horario_forzado_cerrado', '0'],
    ];
    $ins = $pdo->prepare("INSERT IGNORE INTO configuracion_tienda (clave,valor) VALUES (?,?)");
    foreach ($defaults as [$k,$v]) $ins->execute([$k,$v]);
} catch (Throwable $e) {}

function getConfig(PDO $pdo): array {
    $rows = $pdo->query("SELECT clave, valor FROM configuracion_tienda")->fetchAll(PDO::FETCH_KEY_PAIR);
    return array_merge([
        'tienda_mensaje_cierre'   => 'La tienda está temporalmente cerrada. ¡Volvemos pronto!',
        'tienda_modo'             => 'abierta',
        'horario_activado'        => '0',
        'horario_apertura'        => '09:00',
        'horario_cierre'          => '21:00',
        'horario_forzado_cerrado' => '0',
    ], $rows);
}

function calcularEstado(PDO $pdo): array {
    $cfg           = getConfig($pdo);
    $modoConfig    = $cfg['tienda_modo'] ?? 'abierta';   // abierta|solo_vista|cerrada
    $horarioActivo = $cfg['horario_activado'] === '1';

    if ($horarioActivo) {
        $tz     = new DateTimeZone('America/Argentina/Buenos_Aires');
        $ahora  = new DateTime('now', $tz);
        $minAct = (int)$ahora->format('H') * 60 + (int)$ahora->format('i');

        [$ha, $ma] = explode(':', $cfg['horario_apertura']);
        [$hc, $mc] = explode(':', $cfg['horario_cierre']);
        $minAp = (int)$ha * 60 + (int)$ma;
        $minCi = (int)$hc * 60 + (int)$mc;

        $enHorario = ($minAct >= $minAp && $minAct < $minCi);
        $forzada   = $cfg['horario_forzado_cerrado'] === '1';

        $modoEfectivo = ($enHorario && !$forzada) ? $modoConfig : 'cerrada';
    } else {
        $enHorario    = null;
        $forzada      = false;
        $modoEfectivo = $modoConfig;
    }

    return [
        'abierta'          => $modoEfectivo !== 'cerrada',
        'acepta_pedidos'   => $modoEfectivo === 'abierta',
        'modo'             => $modoEfectivo,
        'modo_config'      => $modoConfig,
        'mensaje'          => $cfg['tienda_mensaje_cierre'],
        'horario_activado' => $horarioActivo,
        'horario_apertura' => $cfg['horario_apertura'],
        'horario_cierre'   => $cfg['horario_cierre'],
        'en_horario'       => $enHorario,
        'forzado_cerrado'  => $forzada,
    ];
}

function setModo(PDO $pdo, string $modo, bool $horarioActivo, ?bool $enHorario, bool $forzada): void {
    $modos = ['abierta', 'solo_vista', 'cerrada'];
    if (!in_array($modo, $modos, true)) return;

    $stmt = $pdo->prepare("INSERT INTO configuracion_tienda (clave,valor) VALUES (?,?)
                           ON DUPLICATE KEY UPDATE valor=?, updated_at=NOW()");

    if ($horarioActivo) {
        if ($modo === 'cerrada') {
            // Cierre manual durante horario → forzado
            $stmt->execute(['horario_forzado_cerrado','1','1']);
        } else {
            // Reabrir o cambiar a solo_vista → quitar forzado, guardar modo
            $stmt->execute(['horario_forzado_cerrado','0','0']);
            $stmt->execute(['tienda_modo', $modo, $modo]);
        }
    } else {
        $stmt->execute(['tienda_modo', $modo, $modo]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rolesPermitidos = ['admin','administrador','administracion'];
    if (!in_array(strtolower($_SESSION['rol'] ?? ''), $rolesPermitidos, true)) {
        http_response_code(403);
        echo json_encode(['ok'=>false,'msg'=>'Sin permiso']);
        exit;
    }

    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $accion = $body['accion'] ?? '';

    $estadoActual = calcularEstado($pdo);

    if ($accion === 'set_modo' && isset($body['modo'])) {
        // Si hay mensaje de cierre, guardarlo primero
        if (!empty($body['mensaje'])) {
            $msg = trim(substr($body['mensaje'], 0, 300));
            $pdo->prepare("INSERT INTO configuracion_tienda (clave,valor) VALUES ('tienda_mensaje_cierre',?)
                           ON DUPLICATE KEY UPDATE valor=?, updated_at=NOW()")
                ->execute([$msg, $msg]);
        }
        setModo($pdo, $body['modo'], $estadoActual['horario_activado'], $estadoActual['en_horario'], $estadoActual['forzado_cerrado']);
    }

    if ($accion === 'mensaje' && isset($body['mensaje'])) {
        $msg = trim(substr($body['mensaje'], 0, 300));
        $pdo->prepare("INSERT INTO configuracion_tienda (clave,valor) VALUES ('tienda_mensaje_cierre',?)
                       ON DUPLICATE KEY UPDATE valor=?, updated_at=NOW()")
            ->execute([$msg, $msg]);
    }

    // Backward compat: toggle = alterna entre abierta y cerrada
    if ($accion === 'toggle') {
        $nuevo = $estadoActual['abierta'] ? 'cerrada' : 'abierta';
        setModo($pdo, $nuevo, $estadoActual['horario_activado'], $estadoActual['en_horario'], $estadoActual['forzado_cerrado']);
    }

    echo json_encode(['ok'=>true] + calcularEstado($pdo));
    exit;
}

echo json_encode(['ok'=>true] + calcularEstado($pdo));
