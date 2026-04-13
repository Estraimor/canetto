<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';
$pageTitle = "Auditoría";
include '../panel/dashboard/layaut/nav.php';

$pdo = Conexion::conectar();

// Crear tabla si no existe
$pdo->exec("CREATE TABLE IF NOT EXISTS `auditoria` (
    `idauditoria` INT(11) NOT NULL AUTO_INCREMENT,
    `usuario_id` INT(11) DEFAULT NULL,
    `usuario_nombre` VARCHAR(100) DEFAULT NULL,
    `accion` VARCHAR(100) NOT NULL,
    `modulo` VARCHAR(50) DEFAULT NULL,
    `descripcion` TEXT DEFAULT NULL,
    `ip` VARCHAR(50) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`idauditoria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Agregar columna si no existe
try { $pdo->exec("ALTER TABLE auditoria ADD COLUMN sucursal_nombre VARCHAR(100) NULL"); } catch (Throwable $e) {}

$total   = (int)$pdo->query("SELECT COUNT(*) FROM auditoria")->fetchColumn();
$hoy     = (int)$pdo->query("SELECT COUNT(*) FROM auditoria WHERE DATE(created_at)=CURDATE()")->fetchColumn();
$modulos = $pdo->query("SELECT COUNT(DISTINCT modulo) FROM auditoria WHERE modulo IS NOT NULL")->fetchColumn();
$sucursales = $pdo->query("SELECT DISTINCT COALESCE(sucursal_nombre,'Casa Central') AS nombre FROM auditoria ORDER BY nombre")->fetchAll(PDO::FETCH_COLUMN);
?>

<link rel="stylesheet" href="/canetto/configuraciones/cfg.css">
<style>
.audit-filters {
    display: flex;
    gap: .75rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    align-items: center;
}
.audit-filters select,
.audit-filters input[type=date] {
    padding: .5rem .8rem;
    border: 1px solid var(--rule-dark);
    border-radius: var(--radius);
    font-family: 'DM Sans', sans-serif;
    font-size: .82rem;
    color: var(--ink);
    background: var(--white);
    outline: none;
    transition: border-color var(--transition);
}
.audit-filters select:focus,
.audit-filters input[type=date]:focus { border-color: var(--ink); }
.audit-label {
    font-size: .72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: var(--ink-soft);
}
.btn-outline {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .5rem 1rem; border-radius: var(--radius);
    font-size: .78rem; font-weight: 600; cursor: pointer;
    border: 1px solid var(--rule-dark); background: var(--white); color: var(--ink);
    font-family: 'DM Sans', sans-serif;
    transition: all var(--transition);
}
.btn-outline:hover { background: var(--ink); color: var(--white); border-color: var(--ink); }
</style>

<div class="cfg-module">

    <div class="cfg-page-header">
        <div class="cfg-page-header__left">
            <a class="cfg-back" href="/canetto/configuraciones/index.php">
                <i class="fa-solid fa-chevron-left" style="font-size:.6rem"></i> Configuraciones
            </a>
            <div class="cfg-page-title">
                <span>Configuración</span>
                Auditoría del sistema
            </div>
        </div>
        <button class="btn-outline" onclick="dt && dt.ajax.reload()">
            <i class="fa-solid fa-rotate-right"></i> Actualizar
        </button>
    </div>

    <div class="stats-bar">
        <div class="stat-card">
            <div class="stat-card__num" id="statTotal"><?= $total ?></div>
            <div class="stat-card__label">Total eventos</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__num num-success" id="statHoy"><?= $hoy ?></div>
            <div class="stat-card__label">Hoy</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__num" id="statModulos"><?= $modulos ?></div>
            <div class="stat-card__label">Módulos registrados</div>
        </div>
    </div>

    <div class="audit-filters">
        <span class="audit-label">Filtrar por:</span>
        <select id="filtroSucursal" onchange="aplicarFiltros()">
            <option value="">Todas las sucursales</option>
            <?php foreach ($sucursales as $suc): ?>
            <option value="<?= htmlspecialchars($suc) ?>"><?= htmlspecialchars($suc) ?></option>
            <?php endforeach; ?>
        </select>
        <select id="filtroModulo" onchange="aplicarFiltros()">
            <option value="">Todos los módulos</option>
            <option value="usuarios">Usuarios</option>
            <option value="roles">Roles</option>
            <option value="metodos_pago">Métodos de pago</option>
            <option value="sucursales">Sucursales</option>
            <option value="pedidos">Pedidos</option>
            <option value="ventas">Ventas</option>
            <option value="stock">Stock</option>
            <option value="proveedores">Proveedores</option>
        </select>
        <select id="filtroAccion" onchange="aplicarFiltros()">
            <option value="">Todas las acciones</option>
            <option value="crear">Crear</option>
            <option value="editar">Editar</option>
            <option value="eliminar">Eliminar</option>
            <option value="acceso">Acceso</option>
        </select>
        <input type="date" id="filtroFecha" onchange="aplicarFiltros()" title="Filtrar por fecha">
        <button class="btn-outline" onclick="limpiarFiltros()">
            <i class="fa-solid fa-xmark"></i> Limpiar
        </button>
    </div>

    <div class="table-wrap">
        <table id="tablaAuditoria" style="width:100%">
            <thead>
                <tr>
                    <th>Fecha y hora</th>
                    <th>Usuario</th>
                    <th>Sucursal</th>
                    <th>Acción</th>
                    <th>Módulo</th>
                    <th>Descripción</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

</div>

<?php include '../panel/dashboard/layaut/footer.php'; ?>
<script>
let dt = null;

const ACCION_CLASS = { crear: 'crear', editar: 'editar', eliminar: 'eliminar', acceso: 'acceso' };

$(document).ready(function () {
    dt = $('#tablaAuditoria').DataTable({
        ajax: {
            url: 'ajax/listar_auditoria.php',
            dataSrc: function(json) {
                document.getElementById('statTotal').textContent = json.length;
                const hoy = new Date().toISOString().split('T')[0];
                const hoyCount = json.filter(e => e.created_at && e.created_at.startsWith(hoy)).length;
                document.getElementById('statHoy').textContent = hoyCount;
                return json;
            }
        },
        columns: [
            { data: 'created_at', render: v => {
                if (!v) return '—';
                const d = new Date(v.replace(' ', 'T'));
                return '<span style="font-size:.78rem;white-space:nowrap;">' +
                    d.toLocaleDateString('es-AR') + '<br><span style="color:var(--ink-soft)">' +
                    d.toLocaleTimeString('es-AR', {hour:'2-digit',minute:'2-digit'}) + '</span></span>';
            }},
            { data: null, render: row => {
                const nombre = esc(row.usuario_nombre || 'Sistema');
                return '<strong>' + nombre + '</strong>';
            }},
            { data: 'sucursal_nombre', render: v => v
                ? '<span style="font-size:.78rem;font-weight:600;color:#c88e99;">' + esc(v) + '</span>'
                : '<span style="color:var(--ink-soft)">Casa Central</span>' },
            { data: 'accion', render: v => {
                const cls = ACCION_CLASS[v] || '';
                return '<span class="audit-action ' + cls + '">' + esc(v) + '</span>';
            }},
            { data: 'modulo', render: v => v ? '<span style="font-size:.78rem;font-weight:600;text-transform:uppercase;letter-spacing:.04em;color:var(--ink-soft);">' + esc(v) + '</span>' : '<span style="color:var(--ink-soft)">—</span>' },
            { data: 'descripcion', render: v => v ? '<span style="font-size:.8rem;">' + esc(v) + '</span>' : '<span style="color:var(--ink-soft)">—</span>' },
            { data: 'ip', render: v => v ? '<code style="font-size:.75rem;background:#f5f5f5;padding:.1rem .35rem;border-radius:3px;">' + esc(v) + '</code>' : '—' }
        ],
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
        pageLength: 25,
        order: [[0, 'desc']],
    });
});

function aplicarFiltros() {
    const sucursal = document.getElementById('filtroSucursal').value;
    const modulo   = document.getElementById('filtroModulo').value;
    const accion   = document.getElementById('filtroAccion').value;
    const fecha    = document.getElementById('filtroFecha').value;

    $.fn.dataTable.ext.search = [];

    if (sucursal || modulo || accion || fecha) {
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex, row) {
            if (sucursal && (row.sucursal_nombre || 'Casa Central') !== sucursal) return false;
            if (modulo   && row.modulo  !== modulo)  return false;
            if (accion   && row.accion  !== accion)  return false;
            if (fecha    && row.created_at && !row.created_at.startsWith(fecha)) return false;
            return true;
        });
    }

    dt.draw();
}

function limpiarFiltros() {
    document.getElementById('filtroSucursal').value = '';
    document.getElementById('filtroModulo').value   = '';
    document.getElementById('filtroAccion').value   = '';
    document.getElementById('filtroFecha').value    = '';
    $.fn.dataTable.ext.search = [];
    dt.draw();
}

function esc(str) {
    return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}
</script>
