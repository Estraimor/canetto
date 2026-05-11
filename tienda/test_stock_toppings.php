<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';
$pdo = Conexion::conectar();

// Ver todos los toppings con su stock actual
$rows = $pdo->query("
    SELECT t.idtoppings, t.nombre, t.precio,
           COALESCE(ts.stock_actual, -1) AS stock,
           COALESCE(ts.stock_minimo, 0)  AS stock_minimo,
           ts.idtoppings_stock
    FROM toppings t
    LEFT JOIN toppings_stock ts ON ts.toppings_idtoppings = t.idtoppings
    WHERE t.activo = 1
    ORDER BY t.nombre
")->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Toppings actuales</h2><table border=1 cellpadding=6>";
echo "<tr><th>ID</th><th>Nombre</th><th>Precio</th><th>Stock actual</th><th>Stock mínimo</th></tr>";
foreach ($rows as $r) {
    $color = $r['stock'] < 0 ? '#eee' : ($r['stock'] == 0 ? '#ffcccc' : ($r['stock'] <= $r['stock_minimo'] ? '#fff3cd' : '#ccffcc'));
    echo "<tr style='background:$color'><td>{$r['idtoppings']}</td><td>{$r['nombre']}</td><td>\${$r['precio']}</td><td>{$r['stock']}</td><td>{$r['stock_minimo']}</td></tr>";
}
echo "</table>";

// Aplicar cambios de prueba si se envía el form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aplicar'])) {
    $updates = [
        // Formato: [idtoppings, stock_actual, stock_minimo]
        // Los primeros 3: stock bueno
        // Los siguientes 2: stock bajo
        // Los últimos 2: sin stock
    ];

    // Obtener IDs ordenados
    $ids = array_column($rows, 'idtoppings');
    $total = count($ids);

    foreach ($ids as $i => $id) {
        if ($i < intval($total * 0.4)) {
            // 40% — stock bueno (20 unidades, min 5)
            $stock = 20; $min = 5;
        } elseif ($i < intval($total * 0.7)) {
            // 30% — stock bajo (3 unidades, min 5)
            $stock = 3; $min = 5;
        } else {
            // 30% — sin stock (0)
            $stock = 0; $min = 5;
        }

        // Upsert en toppings_stock
        $exists = $pdo->prepare("SELECT idtoppings_stock FROM toppings_stock WHERE toppings_idtoppings = ?");
        $exists->execute([$id]);
        $row = $exists->fetch();
        if ($row) {
            $pdo->prepare("UPDATE toppings_stock SET stock_actual=?, stock_minimo=? WHERE toppings_idtoppings=?")->execute([$stock, $min, $id]);
        } else {
            $pdo->prepare("INSERT INTO toppings_stock (toppings_idtoppings, stock_actual, stock_minimo) VALUES (?,?,?)")->execute([$id, $stock, $min]);
        }
    }
    echo "<div style='background:#d4edda;padding:12px;margin:12px 0;border-radius:6px'><b>✅ Stocks actualizados. Recargá la página para ver los cambios.</b></div>";
    echo "<meta http-equiv='refresh' content='1'>";
}
?>
<form method="POST" style="margin:20px 0">
  <p>Esto va a repartir los toppings en:</p>
  <ul>
    <li style="color:green"><b>40%</b> — Stock bueno (20 uds)</li>
    <li style="color:orange"><b>30%</b> — Stock bajo (3 uds, mínimo 5)</li>
    <li style="color:red"><b>30%</b> — Sin stock (0 uds)</li>
  </ul>
  <button name="aplicar" value="1" style="padding:10px 24px;background:#c88e99;color:#fff;border:none;border-radius:8px;cursor:pointer;font-size:15px">Aplicar stocks de prueba</button>
</form>
