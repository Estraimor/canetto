<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
include '../../panel/dashboard/layaut/nav.php';

$pdo = Conexion::conectar();

$pdo->exec("CREATE TABLE IF NOT EXISTS toppings (
    idtoppings INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    precio DECIMAL(10,2) NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT NOW()
)");
$pdo->exec("CREATE TABLE IF NOT EXISTS producto_toppings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    productos_idproductos INT NOT NULL,
    toppings_idtoppings INT NOT NULL,
    UNIQUE KEY uq_pt (productos_idproductos, toppings_idtoppings)
)");

$toppings = $pdo->query("
    SELECT t.*, COUNT(pt.id) AS en_productos
    FROM toppings t
    LEFT JOIN producto_toppings pt ON pt.toppings_idtoppings = t.idtoppings
    GROUP BY t.idtoppings
    ORDER BY t.nombre ASC
")->fetchAll(PDO::FETCH_ASSOC);

$total    = count($toppings);
$activos  = count(array_filter($toppings, fn($t) => $t['activo']));
$inactivos = $total - $activos;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Toppings — Canetto</title>
<link rel="stylesheet" href="../../assets/dashboard.css">
<link rel="stylesheet" href="toppings.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="layout">
  <?php include '../../panel/dashboard/layaut/sidebar.php'; ?>
  <main class="main-content">
  <div class="content-body">

    <!-- Header -->
    <div class="tp-header">
      <div>
        <h1>Toppings</h1>
        <p>Extras disponibles para agregar a los productos de la tienda</p>
      </div>
      <button class="btn-tp" onclick="abrirModal()">
        <i class="fa-solid fa-plus"></i> Nuevo topping
      </button>
    </div>

    <!-- Stats -->
    <div class="tp-stats">
      <div class="tp-stat">
        <div class="tp-stat-label">Total</div>
        <div class="tp-stat-value pk"><?= $total ?></div>
      </div>
      <div class="tp-stat">
        <div class="tp-stat-label">Activos</div>
        <div class="tp-stat-value ok"><?= $activos ?></div>
      </div>
      <div class="tp-stat">
        <div class="tp-stat-label">Inactivos</div>
        <div class="tp-stat-value inactive"><?= $inactivos ?></div>
      </div>
    </div>

    <!-- Toolbar -->
    <div class="tp-toolbar">
      <div class="tp-search-wrap">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="tpSearch" placeholder="Buscar topping..." oninput="filtrar(this.value)">
      </div>
    </div>

    <!-- Table -->
    <div class="tp-table-wrap">
      <table class="tp-table" id="tpTable">
        <thead>
          <tr>
            <th>Topping</th>
            <th>Precio adicional</th>
            <th>Asignado a</th>
            <th>Estado</th>
            <th class="td-actions">Acciones</th>
          </tr>
        </thead>
        <tbody id="tpTbody">
        <?php if (empty($toppings)): ?>
          <tr class="tp-empty-row">
            <td colspan="5">
              <div class="tp-empty-ic"><i class="fa-solid fa-candy-cane"></i></div>
              <div class="tp-empty-title">No hay toppings todavía</div>
              <div class="tp-empty-sub">Creá el primer topping para asignarlo a tus productos</div>
              <button class="btn-tp" onclick="abrirModal()"><i class="fa-solid fa-plus"></i> Crear topping</button>
            </td>
          </tr>
        <?php else: foreach ($toppings as $t): ?>
          <tr data-nombre="<?= strtolower(htmlspecialchars($t['nombre'])) ?>">
            <td class="td-name"><?= htmlspecialchars($t['nombre']) ?></td>
            <td class="td-price">$<?= number_format($t['precio'], 0, ',', '.') ?></td>
            <td>
              <span class="tp-prod-badge">
                <i class="fa-solid fa-box"></i>
                <?= $t['en_productos'] ?> producto<?= $t['en_productos'] != 1 ? 's' : '' ?>
              </span>
            </td>
            <td>
              <?php if ($t['activo']): ?>
                <span class="tp-status active"><i class="fa-solid fa-circle-check"></i> Activo</span>
              <?php else: ?>
                <span class="tp-status inactive"><i class="fa-solid fa-circle-xmark"></i> Inactivo</span>
              <?php endif; ?>
            </td>
            <td class="td-actions">
              <button class="tp-icon-btn tp-icon-edit" title="Editar"
                onclick="editarTopping(<?= $t['idtoppings'] ?>, <?= htmlspecialchars(json_encode($t['nombre']), ENT_QUOTES) ?>, <?= $t['precio'] ?>, <?= $t['activo'] ?>)">
                <i class="fa-solid fa-pen"></i>
              </button>
              <button class="tp-icon-btn tp-icon-del" title="Eliminar"
                onclick="eliminarTopping(<?= $t['idtoppings'] ?>, <?= htmlspecialchars(json_encode($t['nombre']), ENT_QUOTES) ?>)">
                <i class="fa-solid fa-trash"></i>
              </button>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

  </div><!-- /content-body -->
  </main>
</div>

<!-- Modal -->
<div class="tp-modal-wrap" id="tpModalWrap">
  <div class="tp-modal-backdrop" onclick="cerrarModal()"></div>
  <div class="tp-modal-dialog">
    <div class="tp-modal-head">
      <div>
        <div class="tp-modal-title-txt" id="tpModalTitle">Nuevo topping</div>
        <div class="tp-modal-sub-txt">Completá los datos del extra</div>
      </div>
      <button class="tp-x" onclick="cerrarModal()"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="tp-modal-body">
      <input type="hidden" id="tpId">

      <div class="tp-field">
        <label>Nombre *</label>
        <input type="text" id="tpNombre" placeholder="Ej: Glasé de chocolate, Sprinkles, Nueces...">
      </div>

      <div class="tp-field">
        <label>Precio adicional ($) *</label>
        <input type="number" id="tpPrecio" min="0" step="1" placeholder="0">
        <small>Este monto se suma al precio base del producto</small>
      </div>

      <div class="tp-field">
        <label>Estado</label>
        <select id="tpActivo">
          <option value="1">Activo — visible en la tienda</option>
          <option value="0">Inactivo — oculto en la tienda</option>
        </select>
      </div>

      <div class="tp-modal-alert" id="tpAlert"></div>

      <div class="tp-modal-actions">
        <button class="btn-tp btn-tp-soft" onclick="cerrarModal()">Cancelar</button>
        <button class="btn-tp" id="tpBtnSave" onclick="guardarTopping()">
          <i class="fa-solid fa-floppy-disk"></i> Guardar
        </button>
      </div>
    </div>
  </div>
</div>

<?php include '../../panel/dashboard/layaut/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function abrirModal(id=0, nombre='', precio=0, activo=1){
  document.getElementById('tpId').value     = id;
  document.getElementById('tpNombre').value = nombre;
  document.getElementById('tpPrecio').value = precio || '';
  document.getElementById('tpActivo').value = activo;
  document.getElementById('tpModalTitle').textContent = id ? 'Editar topping' : 'Nuevo topping';
  document.getElementById('tpAlert').textContent = '';
  document.getElementById('tpAlert').className = 'tp-modal-alert';
  document.getElementById('tpModalWrap').classList.add('open');
  setTimeout(()=>document.getElementById('tpNombre').focus(), 120);
}

function editarTopping(id, nombre, precio, activo){ abrirModal(id, nombre, precio, activo); }

function cerrarModal(){
  document.getElementById('tpModalWrap').classList.remove('open');
}

async function guardarTopping(){
  const id     = parseInt(document.getElementById('tpId').value) || 0;
  const nombre = document.getElementById('tpNombre').value.trim();
  const precio = parseFloat(document.getElementById('tpPrecio').value) || 0;
  const activo = parseInt(document.getElementById('tpActivo').value);
  const alEl   = document.getElementById('tpAlert');

  if (!nombre){
    alEl.textContent = 'El nombre es obligatorio.';
    alEl.className   = 'tp-modal-alert show';
    document.getElementById('tpNombre').focus();
    return;
  }

  const btn = document.getElementById('tpBtnSave');
  btn.disabled = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';

  try {
    const res  = await fetch('api/guardar.php', {
      method: 'POST',
      body: JSON.stringify({idtoppings:id, nombre, precio, activo}),
      headers: {'Content-Type':'application/json'}
    });
    const data = await res.json();
    if (data.ok) { cerrarModal(); location.reload(); }
    else { alEl.textContent = data.msg || 'Error al guardar.'; alEl.className = 'tp-modal-alert show'; }
  } catch {
    alEl.textContent = 'Error de conexión.'; alEl.className = 'tp-modal-alert show';
  }

  btn.disabled = false;
  btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Guardar';
}

async function eliminarTopping(id, nombre){
  const r = await Swal.fire({
    title: '¿Eliminar topping?',
    html: `Se eliminará <strong>${nombre}</strong> de todos los productos donde esté asignado.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#c88e99',
    cancelButtonColor: '#94a3b8',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  });
  if (!r.isConfirmed) return;

  const res  = await fetch('api/eliminar.php', {
    method: 'POST',
    body: JSON.stringify({idtoppings: id}),
    headers: {'Content-Type':'application/json'}
  });
  const data = await res.json();
  if (data.ok) location.reload();
  else Swal.fire('Error', data.msg || 'No se pudo eliminar', 'error');
}

function filtrar(q){
  const val = q.toLowerCase().trim();
  document.querySelectorAll('#tpTbody tr[data-nombre]').forEach(tr => {
    tr.style.display = tr.dataset.nombre.includes(val) ? '' : 'none';
  });
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarModal(); });
</script>
</body>
</html>
