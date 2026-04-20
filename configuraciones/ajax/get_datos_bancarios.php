<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = Conexion::conectar();
    $pdo->exec("CREATE TABLE IF NOT EXISTS datos_bancarios (
        id        INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        titular   VARCHAR(200) NOT NULL DEFAULT '',
        banco     VARCHAR(100) NOT NULL DEFAULT '',
        cbu       VARCHAR(22)  NOT NULL DEFAULT '',
        alias     VARCHAR(50)  NOT NULL DEFAULT '',
        instrucciones TEXT NULL,
        pin_hash  VARCHAR(255) NULL,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $row = $pdo->query("SELECT titular, banco, cbu, alias, instrucciones FROM datos_bancarios ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

    if ($row && ($row['cbu'] || $row['alias'])) {
        echo json_encode(['ok' => true, 'datos' => $row], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['ok' => false]);
    }
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
