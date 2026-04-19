<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';
$pageTitle = "Configuraciones";
include '../panel/dashboard/layaut/nav.php';

$pdo = Conexion::conectar();

$counts = ['usuarios' => 0, 'roles' => 0, 'roles_usuario' => 0,
           'metodos_pago' => 0, 'sucursales' => 0, 'auditoria' => 0, 'ofertas' => 0];

try { $counts['usuarios']      = (int)$pdo->query("SELECT COUNT(*) FROM usuario")->fetchColumn(); }      catch(Exception $e) {}
try { $counts['roles']         = (int)$pdo->query("SELECT COUNT(*) FROM roles")->fetchColumn(); }        catch(Exception $e) {}
try { $counts['roles_usuario'] = (int)$pdo->query("SELECT COUNT(DISTINCT usuario_idusuario) FROM usuarios_roles")->fetchColumn(); } catch(Exception $e) {}
try { $counts['metodos_pago']  = (int)$pdo->query("SELECT COUNT(*) FROM metodo_pago")->fetchColumn(); }  catch(Exception $e) {}
try { $counts['sucursales']    = (int)$pdo->query("SELECT COUNT(*) FROM sucursal")->fetchColumn(); }     catch(Exception $e) {}
try { $counts['auditoria']     = (int)$pdo->query("SELECT COUNT(*) FROM auditoria")->fetchColumn(); }    catch(Exception $e) {}
try { $counts['ofertas']       = (int)$pdo->query("SELECT COUNT(*) FROM oferta WHERE activo=1")->fetchColumn(); } catch(Exception $e) {}
?>

<link rel="stylesheet" href="<?= URL_ASSETS ?>/configuraciones/cfg.css">
<style>
.hub-module * { box-sizing: border-box; }
.hub-module {
    font-family: 'DM Sans', sans-serif;
    color: var(--ink);
    background: var(--paper);
    min-height: 100vh;
    padding: 2.5rem 2rem 5rem;
}

/* ── Header ── */
.hub-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    margin-bottom: 3rem;
    padding-bottom: 1.5rem;
    border-bottom: 2px solid var(--ink);
    gap: 1rem;
}
.hub-header__title {
    font-family: 'Playfair Display', serif;
    font-size: 2.6rem;
    font-weight: 700;
    letter-spacing: -.5px;
    line-height: 1;
}
.hub-header__title span {
    display: block;
    font-family: 'DM Sans', sans-serif;
    font-size: .72rem;
    font-weight: 500;
    letter-spacing: .2em;
    text-transform: uppercase;
    color: var(--ink-soft);
    margin-bottom: .4rem;
}
.hub-header__sub {
    font-size: .85rem;
    color: var(--ink-soft);
    max-width: 320px;
    text-align: right;
    line-height: 1.6;
}

/* ── Grid ── */
.hub-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
}

/* ── Cards ── */
.hub-card {
    background: var(--white);
    border: 1px solid var(--rule);
    border-radius: 12px;
    padding: 2rem;
    cursor: pointer;
    text-decoration: none;
    color: var(--ink);
    display: flex;
    flex-direction: column;
    gap: 1.2rem;
    transition: transform var(--transition), box-shadow var(--transition), border-color var(--transition);
    position: relative;
    overflow: hidden;
    animation: hubFadeUp .45s ease both;
}
.hub-card:nth-child(1) { animation-delay: .04s; }
.hub-card:nth-child(2) { animation-delay: .08s; }
.hub-card:nth-child(3) { animation-delay: .12s; }
.hub-card:nth-child(4) { animation-delay: .16s; }
.hub-card:nth-child(5) { animation-delay: .20s; }
.hub-card:nth-child(6) { animation-delay: .24s; }

.hub-card::after {
    content: '';
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 3px;
    background: var(--ink);
    transform: scaleX(0);
    transform-origin: left;
    transition: transform .35s cubic-bezier(.4,0,.2,1);
}
.hub-card:hover {
    transform: translateY(-6px);
    box-shadow: var(--shadow-lg);
    border-color: var(--rule-dark);
}
.hub-card:hover::after { transform: scaleX(1); }

/* ── Card icon ── */
.hub-card__icon {
    width: 56px; height: 56px;
    background: var(--paper);
    border: 1px solid var(--rule);
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem;
    color: var(--ink-mid);
    transition: background var(--transition), color var(--transition), border-color var(--transition), transform var(--transition);
}
.hub-card:hover .hub-card__icon {
    background: var(--ink);
    color: var(--white);
    border-color: var(--ink);
    transform: scale(1.05) rotate(-3deg);
}

/* ── Card body ── */
.hub-card__title {
    font-family: 'Playfair Display', serif;
    font-size: 1.3rem;
    font-weight: 700;
    margin-bottom: .35rem;
}
.hub-card__desc {
    font-size: .82rem;
    color: var(--ink-soft);
    line-height: 1.6;
}

/* ── Card footer ── */
.hub-card__footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 1rem;
    border-top: 1px solid var(--rule);
    margin-top: auto;
}
.hub-card__count {
    font-family: 'Playfair Display', serif;
    font-size: 1.8rem;
    font-weight: 700;
    line-height: 1;
    transition: transform var(--transition);
}
.hub-card:hover .hub-card__count { transform: scale(1.05); }
.hub-card__count-label {
    font-size: .68rem;
    color: var(--ink-soft);
    text-transform: uppercase;
    letter-spacing: .08em;
    margin-top: .2rem;
}
.hub-card__btn {
    display: inline-flex; align-items: center; gap: .4rem;
    font-size: .75rem; font-weight: 600; letter-spacing: .06em;
    text-transform: uppercase; color: var(--ink-soft);
    transition: color var(--transition), gap var(--transition);
    white-space: nowrap;
}
.hub-card:hover .hub-card__btn { color: var(--ink); gap: .65rem; }

@keyframes hubFadeUp {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}

@media (max-width: 1024px) { .hub-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 640px)  {
    .hub-grid { grid-template-columns: 1fr; }
    .hub-header { flex-direction: column; align-items: flex-start; }
    .hub-header__sub { text-align: left; max-width: 100%; }
}
</style>

<div class="hub-module">

    <div class="hub-header">
        <div class="hub-header__title">
            <span>Panel del sistema</span>
            Configuraciones
        </div>
        <div class="hub-header__sub">
            Seleccioná un módulo para<br>gestionar la configuración del sistema.
        </div>
    </div>

    <div class="hub-grid">

        <!-- Usuarios -->
        <a class="hub-card" href="<?= URL_ASSETS ?>/configuraciones/usuarios.php">
            <div class="hub-card__icon"><i class="fa-solid fa-users"></i></div>
            <div>
                <div class="hub-card__title">Usuarios</div>
                <div class="hub-card__desc">Gestión de cuentas, credenciales y estado de acceso al sistema.</div>
            </div>
            <div class="hub-card__footer">
                <div>
                    <div class="hub-card__count"><?= $counts['usuarios'] ?></div>
                    <div class="hub-card__count-label">registrados</div>
                </div>
                <div class="hub-card__btn">Acceder <i class="fa-solid fa-arrow-right" style="font-size:.65rem"></i></div>
            </div>
        </a>

        <!-- Roles -->
        <a class="hub-card" href="<?= URL_ASSETS ?>/configuraciones/roles.php">
            <div class="hub-card__icon"><i class="fa-solid fa-shield-halved"></i></div>
            <div>
                <div class="hub-card__title">Roles</div>
                <div class="hub-card__desc">Definición de roles que determinan los niveles de acceso al sistema.</div>
            </div>
            <div class="hub-card__footer">
                <div>
                    <div class="hub-card__count"><?= $counts['roles'] ?></div>
                    <div class="hub-card__count-label">definidos</div>
                </div>
                <div class="hub-card__btn">Acceder <i class="fa-solid fa-arrow-right" style="font-size:.65rem"></i></div>
            </div>
        </a>

        <!-- Roles por usuario -->
        <a class="hub-card" href="<?= URL_ASSETS ?>/configuraciones/roles_usuario.php">
            <div class="hub-card__icon"><i class="fa-solid fa-user-tag"></i></div>
            <div>
                <div class="hub-card__title">Roles por usuario</div>
                <div class="hub-card__desc">Asignación y gestión de roles a cada usuario del sistema.</div>
            </div>
            <div class="hub-card__footer">
                <div>
                    <div class="hub-card__count"><?= $counts['roles_usuario'] ?></div>
                    <div class="hub-card__count-label">con rol asignado</div>
                </div>
                <div class="hub-card__btn">Acceder <i class="fa-solid fa-arrow-right" style="font-size:.65rem"></i></div>
            </div>
        </a>

        <!-- Métodos de pago -->
        <a class="hub-card" href="<?= URL_ASSETS ?>/configuraciones/metodos_pago.php">
            <div class="hub-card__icon"><i class="fa-solid fa-credit-card"></i></div>
            <div>
                <div class="hub-card__title">Métodos de pago</div>
                <div class="hub-card__desc">Configuración de formas de cobro disponibles para las ventas.</div>
            </div>
            <div class="hub-card__footer">
                <div>
                    <div class="hub-card__count"><?= $counts['metodos_pago'] ?></div>
                    <div class="hub-card__count-label">configurados</div>
                </div>
                <div class="hub-card__btn">Acceder <i class="fa-solid fa-arrow-right" style="font-size:.65rem"></i></div>
            </div>
        </a>

        <!-- Sucursales -->
        <a class="hub-card" href="<?= URL_ASSETS ?>/configuraciones/sucursales.php">
            <div class="hub-card__icon"><i class="fa-solid fa-building"></i></div>
            <div>
                <div class="hub-card__title">Sucursales</div>
                <div class="hub-card__desc">Ubicación, datos de contacto y gestión de puntos de venta.</div>
            </div>
            <div class="hub-card__footer">
                <div>
                    <div class="hub-card__count"><?= $counts['sucursales'] ?></div>
                    <div class="hub-card__count-label">registradas</div>
                </div>
                <div class="hub-card__btn">Acceder <i class="fa-solid fa-arrow-right" style="font-size:.65rem"></i></div>
            </div>
        </a>

        <!-- Ofertas Tienda -->
        <a class="hub-card" href="<?= URL_ASSETS ?>/configuraciones/ofertas.php">
            <div class="hub-card__icon"><i class="fa-solid fa-tag"></i></div>
            <div>
                <div class="hub-card__title">Ofertas Tienda</div>
                <div class="hub-card__desc">Carrusel de promociones y ofertas que aparecen en la tienda online.</div>
            </div>
            <div class="hub-card__footer">
                <div>
                    <div class="hub-card__count"><?= $counts['ofertas'] ?></div>
                    <div class="hub-card__count-label">activas</div>
                </div>
                <div class="hub-card__btn">Gestionar <i class="fa-solid fa-arrow-right" style="font-size:.65rem"></i></div>
            </div>
        </a>

        <!-- Auditoría -->
        <a class="hub-card" href="<?= URL_ASSETS ?>/configuraciones/auditoria.php">
            <div class="hub-card__icon"><i class="fa-solid fa-clipboard-list"></i></div>
            <div>
                <div class="hub-card__title">Auditoría</div>
                <div class="hub-card__desc">Registro completo de actividad: acciones, modificaciones y accesos al sistema.</div>
            </div>
            <div class="hub-card__footer">
                <div>
                    <div class="hub-card__count"><?= $counts['auditoria'] ?></div>
                    <div class="hub-card__count-label">eventos registrados</div>
                </div>
                <div class="hub-card__btn">Ver registro <i class="fa-solid fa-arrow-right" style="font-size:.65rem"></i></div>
            </div>
        </a>

    </div>

</div>

<?php include '../panel/dashboard/layaut/footer.php'; ?>
