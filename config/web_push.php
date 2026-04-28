<?php
/**
 * Web Push helper — VAPID sin dependencias externas.
 * Firma JWT con ES256 (ECDSA P-256) y envía push vacío (el SW consulta
 * el contenido al servidor al recibirlo).
 */
require_once __DIR__ . '/push_config.php';

// ── Utilidades ────────────────────────────────────────────────────

function push_base64url(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function push_der_to_raw(string $der): string {
    // DER: 0x30 <seq_len> 0x02 <r_len> <r> 0x02 <s_len> <s>
    $offset = 2; // saltar SEQUENCE tag + longitud
    $offset++; // saltar INTEGER tag (0x02) de r
    $rLen   = ord($der[$offset++]);
    $r      = substr($der, $offset, $rLen);
    $offset += $rLen;
    $offset++; // saltar INTEGER tag (0x02) de s
    $sLen   = ord($der[$offset++]);
    $s      = substr($der, $offset, $sLen);

    // DER puede tener un 0x00 líder para indicar número positivo
    $r = ltrim($r, "\x00");
    $s = ltrim($s, "\x00");

    return str_pad($r, 32, "\x00", STR_PAD_LEFT)
         . str_pad($s, 32, "\x00", STR_PAD_LEFT);
}

function push_vapid_jwt(string $endpoint, string $subject, string $privPem): string {
    $parts    = parse_url($endpoint);
    $audience = $parts['scheme'] . '://' . $parts['host']
              . (isset($parts['port']) ? ':' . $parts['port'] : '');

    $header  = push_base64url(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
    $claims  = push_base64url(json_encode([
        'aud' => $audience,
        'exp' => time() + 43200,
        'sub' => $subject,
    ]));
    $signing = $header . '.' . $claims;

    openssl_sign($signing, $derSig, $privPem, OPENSSL_ALGO_SHA256);
    $rawSig = push_der_to_raw($derSig);

    return $signing . '.' . push_base64url($rawSig);
}

// ── Crear tablas si no existen ────────────────────────────────────

function push_ensure_tables(\PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS push_subscriptions (
            id            INT          AUTO_INCREMENT PRIMARY KEY,
            usuario_id    INT          NOT NULL,
            endpoint      TEXT         NOT NULL,
            endpoint_hash CHAR(64)     NOT NULL,
            p256dh        VARCHAR(512) NOT NULL,
            auth_key      VARCHAR(255) NOT NULL,
            activo        TINYINT(1)   NOT NULL DEFAULT 1,
            created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_ep (endpoint_hash),
            INDEX idx_uid (usuario_id, activo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS push_notificaciones (
            id         INT          AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT          NOT NULL,
            titulo     VARCHAR(255) NOT NULL,
            cuerpo     TEXT         NOT NULL,
            url        VARCHAR(512) NOT NULL DEFAULT '/canetto/tienda/mis-pedidos.php',
            leida      TINYINT(1)   NOT NULL DEFAULT 0,
            created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_uid_leida (usuario_id, leida)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

// ── Enviar push a TODOS los usuarios suscritos (paneles, anuncios) ─

function push_enviar_a_todos(\PDO $pdo, string $titulo, string $cuerpo, string $url = '/canetto/tienda/'): array {
    push_ensure_tables($pdo);

    // Insertar notificación para cada usuario único
    $uids = $pdo->query("SELECT DISTINCT usuario_id FROM push_subscriptions WHERE activo=1")->fetchAll(\PDO::FETCH_COLUMN);
    foreach ($uids as $uid) {
        $pdo->prepare("INSERT INTO push_notificaciones (usuario_id, titulo, cuerpo, url) VALUES (?,?,?,?)")
            ->execute([(int)$uid, $titulo, $cuerpo, $url]);
    }

    // Enviar push a cada dispositivo activo
    $subs    = $pdo->query("SELECT endpoint, endpoint_hash FROM push_subscriptions WHERE activo=1")->fetchAll(\PDO::FETCH_ASSOC);
    $privPem = PUSH_VAPID_PRIVATE_PEM;
    $pubKey  = PUSH_VAPID_PUBLIC;
    $subject = PUSH_SUBJECT;
    $sent = 0; $failed = 0;

    foreach ($subs as $sub) {
        try {
            $jwt = push_vapid_jwt($sub['endpoint'], $subject, $privPem);
            $ch  = curl_init($sub['endpoint']);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => [
                    'Authorization: vapid t=' . $jwt . ',k=' . $pubKey,
                    'TTL: 86400',
                    'Content-Length: 0',
                ],
                CURLOPT_POSTFIELDS     => '',
                CURLOPT_TIMEOUT        => 6,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($code === 410) {
                $pdo->prepare("UPDATE push_subscriptions SET activo=0 WHERE endpoint_hash=?")->execute([$sub['endpoint_hash']]);
                $failed++;
            } else {
                $sent++;
            }
        } catch (\Throwable $e) {
            $failed++;
        }
    }

    return ['sent' => $sent, 'failed' => $failed];
}

// ── Función principal: enviar push a todos los subs de un usuario ─

function push_enviar_a_usuario(\PDO $pdo, int $uid, int $idVenta, int $estado): void {
    try {
        push_ensure_tables($pdo);

        $mensajes  = PUSH_MENSAJES;
        $msg       = $mensajes[$estado] ?? null;
        if (!$msg) return;

        $titulo = $msg['titulo'];
        $cuerpo = $msg['cuerpo'] . " (Pedido #{$idVenta})";
        $url    = '/canetto/tienda/mis-pedidos.php';

        // Guardar notificación en DB
        $pdo->prepare("
            INSERT INTO push_notificaciones (usuario_id, titulo, cuerpo, url)
            VALUES (?, ?, ?, ?)
        ")->execute([$uid, $titulo, $cuerpo, $url]);

        // Obtener suscripciones activas del cliente
        $subs = $pdo->prepare("
            SELECT endpoint, p256dh, auth_key, endpoint_hash
            FROM push_subscriptions
            WHERE usuario_id = ? AND activo = 1
        ");
        $subs->execute([$uid]);
        $rows = $subs->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($rows)) return;

        $privPem = PUSH_VAPID_PRIVATE_PEM;
        $pubKey  = PUSH_VAPID_PUBLIC;
        $subject = PUSH_SUBJECT;

        foreach ($rows as $sub) {
            try {
                $jwt = push_vapid_jwt($sub['endpoint'], $subject, $privPem);

                $ch = curl_init($sub['endpoint']);
                curl_setopt_array($ch, [
                    CURLOPT_POST           => true,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER     => [
                        'Authorization: vapid t=' . $jwt . ',k=' . $pubKey,
                        'TTL: 86400',
                        'Content-Length: 0',
                    ],
                    CURLOPT_POSTFIELDS     => '',
                    CURLOPT_TIMEOUT        => 6,
                    CURLOPT_SSL_VERIFYPEER => true,
                ]);
                curl_exec($ch);
                $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                // Si el push service rechaza (410 Gone), desactivar suscripción
                if ($code === 410) {
                    $pdo->prepare("UPDATE push_subscriptions SET activo=0 WHERE endpoint_hash=?")
                        ->execute([$sub['endpoint_hash']]);
                }
            } catch (\Throwable $e) {
                // Silencioso: no romper la respuesta del admin
            }
        }
    } catch (\Throwable $e) {
        // Silencioso
    }
}
