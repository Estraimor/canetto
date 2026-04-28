<?php
/**
 * Script ONE-TIME: genera el par de claves VAPID para Web Push.
 * Ejecutar UNA SOLA VEZ en el navegador, copiar las claves en config/push_config.php
 * Luego eliminar o proteger este archivo.
 */
if (PHP_SAPI !== 'cli' && (!isset($_SERVER['REMOTE_ADDR']) || $_SERVER['REMOTE_ADDR'] !== '127.0.0.1')) {
    // Sólo accesible desde localhost
    http_response_code(403);
    exit('Acceso denegado. Sólo ejecutar localmente.');
}

$key = openssl_pkey_new([
    'curve_name'       => 'prime256v1',
    'private_key_type' => OPENSSL_KEYTYPE_EC,
]);
if (!$key) {
    die('ERROR OpenSSL: ' . openssl_error_string());
}

$details = openssl_pkey_get_details($key);
$pubRaw  = "\x04" . $details['ec']['x'] . $details['ec']['y']; // 65 bytes
$pubB64  = rtrim(strtr(base64_encode($pubRaw), '+/', '-_'), '=');

openssl_pkey_export($key, $privPem);

header('Content-Type: text/plain; charset=utf-8');
echo "============================================\n";
echo " CLAVES VAPID — Copiar en config/push_config.php\n";
echo "============================================\n\n";
echo "VAPID_PUBLIC_KEY  = '$pubB64'\n\n";
echo "VAPID_PRIVATE_PEM = <<<'EOT'\n$privPem\nEOT\n\n";
echo "============================================\n";
echo "¡ELIMINAR ESTE ARCHIVO DESPUÉS DE USARLO!\n";
