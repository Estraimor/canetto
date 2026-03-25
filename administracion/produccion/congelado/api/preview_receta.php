<?php
declare(strict_types=1);
define('APP_BOOT', true);

/* =========================
ERRORES (solo debug)
========================= */
ini_set('display_errors', 1);
error_reporting(E_ALL);

/* =========================
HEADER JSON
========================= */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../../config/conexion.php';

try {

    $pdo = Conexion::conectar();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    /* =========================
    INPUT
    ========================= */
    $rawInput = file_get_contents("php://input");

    if (!$rawInput) {
        throw new Exception("No llegó input");
    }

    $input = json_decode($rawInput, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("JSON inválido: " . json_last_error_msg());
    }

    $recetaId = (int)($input['receta'] ?? 0);
    $cantidadDeseada = (float)($input['cantidad'] ?? 0);

    if ($recetaId <= 0 || $cantidadDeseada <= 0) {
        throw new Exception("Datos inválidos");
    }

    /* =========================
    RECETA
    ========================= */
    $stmt = $pdo->prepare("
        SELECT cantidad_galletas
        FROM recetas
        WHERE idrecetas = ?
        LIMIT 1
    ");
    $stmt->execute([$recetaId]);

    $receta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$receta) {
        throw new Exception("Receta no encontrada");
    }

    $base = (float)$receta['cantidad_galletas'];

    if ($base <= 0) {
        throw new Exception("Base inválida");
    }

    $factor = $cantidadDeseada / $base;

    /* =========================
    INGREDIENTES + UNIDAD REAL
    ========================= */
    $stmt = $pdo->prepare("
        SELECT
            mp.nombre,
            COALESCE(mp.stock_actual,0) as stock,
            ri.cantidad,
            COALESCE(um.nombre,'') as unidad_nombre,
            COALESCE(um.abreviatura,'') as unidad_abreviatura
        FROM receta_ingredientes ri
        INNER JOIN materia_prima mp
            ON mp.idmateria_prima = ri.materia_prima_idmateria_prima
        LEFT JOIN unidad_medida um
            ON um.idunidad_medida = mp.unidad_medida_idunidad_medida
        WHERE ri.recetas_idrecetas = ?
    ");

    $stmt->execute([$recetaId]);
    $ingredientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$ingredientes) {
        throw new Exception("La receta no tiene ingredientes");
    }

    /* =========================
    FUNCION REDONDEO 🔥
    ========================= */
    function formatearCantidad(array $i, float $cantidad): float {

        $unidadNombre = mb_strtolower(trim($i['unidad_nombre']));
        $unidadAbr    = mb_strtolower(trim($i['unidad_abreviatura']));

        if ($unidadNombre === 'unidades' || $unidadAbr === 'u') {
            return (float) round($cantidad); // 🔥 ENTERO
        }

        return round($cantidad, 2); // 🔥 DECIMALES
    }

    /* =========================
    CALCULO
    ========================= */
    $out = [];
    $ok = true;

    foreach ($ingredientes as $i) {

        $necesarioRaw = (float)$i['cantidad'] * $factor;
        $necesario = formatearCantidad($i, $necesarioRaw);

        $stock = (float)$i['stock'];

        $faltante = $stock < $necesario;

        if ($faltante) $ok = false;

        /* =========================
        FORMATO UNIDAD (singular/plural)
        ========================= */
        $unidadTexto = $i['unidad_nombre'];

        if ($unidadTexto === 'Unidades' && $necesario == 1) {
            $unidadTexto = 'Unidad';
        }

        $out[] = [
            "nombre" => $i['nombre'],
            "cantidad" => $necesario,
            "unidad" => $unidadTexto,
            "stock" => formatearCantidad($i, $stock),
            "faltante" => $faltante
        ];
    }

    echo json_encode([
        "status" => "ok",
        "ingredientes" => $out,
        "puede_producir" => $ok
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    echo json_encode([
        "status" => "error",
        "mensaje" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}