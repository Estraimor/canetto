<?php
/**
 * config/mailer.php — Canetto Email Helper
 * Usa PHPMailer con SMTP de Hostinger.
 */

if (!defined('APP_BOOT')) {
    http_response_code(403);
    exit('Acceso denegado.');
}

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// ─── Credenciales SMTP ─────────────────────────
define('MAIL_HOST',     'smtp.hostinger.com');
define('MAIL_PORT',     465);
define('MAIL_USER',     'no-reply@canettocookies.com');  // cuenta SMTP (autenticación)
define('MAIL_PASS',     'CanettoDefault123.');
define('MAIL_FROM',     'no-reply@canettocookies.com');  // remitente: solo auth y recuperación
define('MAIL_FROM_NAME','Canetto Cookies');
define('MAIL_SUPPORT',  'soporte@canettocookies.com');   // receptor de mensajes de soporte

// URL base del sitio: en producción sin subfolder, en local con /canetto
$_siteHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_isProd   = !in_array($_siteHost, ['localhost', '127.0.0.1'], true);
define('SITE_URL', $_isProd
    ? 'https://canettocookies.com'
    : 'http://localhost/canetto'
);
unset($_siteHost, $_isProd);

define('MAIL_LOGO_URL', 'https://canettocookies.com/img/Logo_Canetto_Cookie.png');

/**
 * Crea y devuelve un PHPMailer listo para usar.
 */
function crearMailer(): PHPMailer
{
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = MAIL_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = MAIL_USER;
    $mail->Password   = MAIL_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = MAIL_PORT;
    $mail->CharSet    = 'UTF-8';
    $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
    return $mail;
}

/**
 * Template HTML base de Canetto para emails.
 *
 * @param string $titulo    Título del email (texto dentro del header)
 * @param string $contenido HTML del cuerpo
 */
function plantillaEmail(string $titulo, string $contenido): string
{
    return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>{$titulo}</title>
</head>
<body style="margin:0;padding:0;background:#f4f1f0;font-family:'Segoe UI',Arial,sans-serif;">

  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f1f0;padding:30px 0;">
    <tr>
      <td align="center">

        <!-- Card -->
        <table width="560" cellpadding="0" cellspacing="0"
               style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);">

          <!-- Header rosado con logo -->
          <tr>
            <td align="center"
                style="background:linear-gradient(135deg,#c88e99 0%,#a46678 100%);padding:32px 40px 24px;">
              <img src="https://canettocookies.com/img/Logo_Canetto_Cookie.png"
                   alt="Canetto Cookies"
                   width="90" height="90"
                   style="border-radius:50%;display:block;margin:0 auto 14px;border:3px solid rgba(255,255,255,.4);">
              <div style="font-size:22px;font-weight:900;letter-spacing:4px;color:#ffffff;font-family:'Segoe UI',Arial,sans-serif;text-transform:uppercase;">
                CANETTO
              </div>
              <div style="color:rgba(255,255,255,.85);font-size:12px;margin-top:4px;letter-spacing:2px;text-transform:uppercase;">
                Cookies &amp; más
              </div>
            </td>
          </tr>

          <!-- Cuerpo -->
          <tr>
            <td style="padding:36px 40px 28px;">
              {$contenido}
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td align="center"
                style="background:#faf7f6;border-top:1px solid #f0e8e8;padding:20px 40px;">
              <p style="margin:0;font-size:12px;color:#aaa;">
                Este email fue enviado automáticamente por Canetto Cookies.<br>
                Si no solicitaste esta acción, ignorá este mensaje.
              </p>
              <p style="margin:8px 0 0;font-size:12px;color:#c88e99;">
                © <?= date('Y') ?> Canetto Cookies — soporte@canettocookies.com
              </p>
            </td>
          </tr>

        </table>
        <!-- /Card -->

      </td>
    </tr>
  </table>

</body>
</html>
HTML;
}

/**
 * Envía un email usando la plantilla de Canetto.
 *
 * @param string $toEmail
 * @param string $toName
 * @param string $asunto
 * @param string $titulo    Subtítulo del header del email
 * @param string $contenido HTML del cuerpo
 * @return bool true = enviado, false = falló
 */
function enviarEmail(string $toEmail, string $toName, string $asunto, string $titulo, string $contenido): bool
{
    try {
        $mail = crearMailer();
        $mail->addAddress($toEmail, $toName);
        $mail->Subject = $asunto;
        $mail->isHTML(true);
        $mail->Body    = plantillaEmail($titulo, $contenido);
        $mail->AltBody = strip_tags($contenido);
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('[Canetto Mailer] Error: ' . $e->getMessage());
        return false;
    }
}
