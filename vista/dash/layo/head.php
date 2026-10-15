<?php
/* ============================================================
   Ruta: vista/dash/layo/head.php
   Función: Cabecera y navbar principal del dashboard.

   Menú:
   1. Catálogo
   2. Inventario
   3. Compras
   4. Ventas
   5. Facturación
   6. Pagos
   7. Reportes
   8. Administración → cada opción se autoriza individualmente mediante LOGI / AUT.

   Recibe:
   $usuarioActual desde dash-layo.php

   Carga:
   - dash/logi/acces.js → control de actividad y cierre de sesión
============================================================ */

/* ========================================================
   NIVEL 1 — CONEXIÓN LOGI
======================================================== */

require_once dirname(__DIR__, 3) . '/contr/login/login-db.php';
require_once dirname(__DIR__, 3) . '/contr/login/contr-perm.php';

/* ========================================================
   NIVEL 2 — CONTROL DE ACCESO ADMINISTRATIVO
   Administración solo se muestra cuando el rol del usuario
   tiene acceso total.
======================================================== */

$contPerm = new ContPerm(LOGINDB::abrir());
$accesoTotal = $contPerm->tieneAccesoTotal();

?>

<header class="dash-head">

    <!-- CONTROL RESPONSIVE: solo visible en móvil -->
    <button class="dash-toggle" type="button" aria-label="Abrir menú" aria-controls="dash-nav" aria-expanded="false">
        <span></span>
        <span></span>
    </button>

    <!-- MARCA -->
    <!-- Fondo para cerrar el menú lateral móvil al pulsarlo. -->
    <button class="dash-overlay" type="button" aria-label="Cerrar menú" hidden></button>

    <strong class="dash-logo">Sistema NUR</strong>

    <!-- NAVBAR -->
    <nav class="dash-nav" id="dash-nav" aria-label="Menú principal">
        <ul class="dash-menu">

            <!-- CATÁLOGO -->
            <li class="dash-item" data-modulo="catalogo">
                <button class="dash-link" type="button" aria-controls="dash-submenu-catalogo" aria-expanded="false">Catálogo</button>
                <ul class="dash-submenu" id="dash-submenu-catalogo">
                    <li><span class="dash-sub-link" data-submodulo="articulos" data-permiso="PRODV">Artículos</span></li>
                    <li><span class="dash-sub-link" data-submodulo="produ" data-permiso="PRODV">Productos</span></li>
                    <li><span class="dash-sub-link" data-submodulo="vehic" data-permiso="VEHIV">Vehículos</span></li>
                    <li><span class="dash-sub-link" data-submodulo="equiv" data-permiso="EQUIG">Equivalente</span></li>
                    <li><span class="dash-sub-link" data-submodulo="imext" data-permiso="IMEXG">Imext</span></li>
                </ul>
            </li>

            <!-- INVENTARIO -->
            <li class="dash-item" data-modulo="inventario">
                <button class="dash-link" type="button" aria-controls="dash-submenu-inventario" aria-expanded="false">Inventario</button>
                <ul class="dash-submenu" id="dash-submenu-inventario">
                    <li><span class="dash-sub-link" data-submodulo="almacenes" data-permiso="ALMAV">Almacenes</span></li>
                    <li><span class="dash-sub-link" data-submodulo="stock" data-permiso="STOCV">Stock</span></li>
                    <li><span class="dash-sub-link" data-submodulo="movimientos" data-permiso="MOVIV">Movimientos</span></li>
                    <li><span class="dash-sub-link" data-submodulo="ordco" data-permiso="OCOMV">Órdenes de compra</span></li>
                </ul>
            </li>

            <!-- COMPRAS -->
            <li class="dash-item" data-modulo="compras">
                <button class="dash-link" type="button" aria-controls="dash-submenu-compras" aria-expanded="false">Compras</button>
                <ul class="dash-submenu" id="dash-submenu-compras">
                    <li><span class="dash-sub-link" data-submodulo="proveedores" data-permiso="PROVV">Proveedores</span></li>
                    <li><span class="dash-sub-link" data-submodulo="orden-compra-ext" data-permiso="ORCEV">Orden compra ext.</span></li>
                    <li><span class="dash-sub-link" data-submodulo="compra-externa" data-permiso="COMEV">Compra externa</span></li>
                    <li><span class="dash-sub-link" data-submodulo="recepcion" data-permiso="RECEV">Recepción</span></li>
                </ul>
            </li>

            <!-- VENTAS -->
            <li class="dash-item" data-modulo="ventas">
                <button class="dash-link" type="button" aria-controls="dash-submenu-ventas" aria-expanded="false">Ventas</button>
                <ul class="dash-submenu" id="dash-submenu-ventas">
                    <li><span class="dash-sub-link" data-submodulo="clientes" data-permiso="CLIEV">Clientes</span></li>
                    <li><span class="dash-sub-link" data-submodulo="cotiz" data-permiso="COTIG">Cotización</span></li>
                    <li><span class="dash-sub-link" data-submodulo="operaciones" data-permiso="VENTG">Operaciones</span></li>
                    <li><span class="dash-sub-link" data-submodulo="notas-venta" data-permiso="NOVEV">Notas de venta</span></li>
                    <li><span class="dash-sub-link" data-submodulo="devoluciones" data-permiso="DEVOG">Devoluciones</span></li>
                </ul>
            </li>

            <!-- FACTURACIÓN -->
            <li class="dash-item" data-modulo="facturacion">
                <button class="dash-link" type="button" aria-controls="dash-submenu-facturacion" aria-expanded="false">Facturación</button>
                <ul class="dash-submenu" id="dash-submenu-facturacion">
                    <li><span class="dash-sub-link" data-submodulo="facturas" data-permiso="FACTV">Facturas</span></li>
                    <li><span class="dash-sub-link" data-submodulo="boletas" data-permiso="BOLEV">Boletas</span></li>
                    <li><span class="dash-sub-link" data-submodulo="notas-credito" data-permiso="NCREG">Notas de crédito</span></li>
                    <li><span class="dash-sub-link" data-submodulo="notas-debito" data-permiso="NDEBG">Notas de débito</span></li>
                    <li><span class="dash-sub-link" data-submodulo="guias-remision" data-permiso="GUIAG">Guías de remisión</span></li>
                    <li><span class="dash-sub-link" data-submodulo="sunat" data-permiso="SUNAG">SUNAT</span></li>
                </ul>
            </li>

            <!-- PAGOS -->
            <li class="dash-item" data-modulo="pagos">
                <button class="dash-link" type="button" aria-controls="dash-submenu-pagos" aria-expanded="false">Pagos</button>
                <ul class="dash-submenu" id="dash-submenu-pagos">
                    <li><span class="dash-sub-link" data-submodulo="registro-pagos" data-permiso="PAGOV">Registro de pagos</span></li>
                    <li><span class="dash-sub-link" data-submodulo="caja" data-permiso="CAJAG">Caja</span></li>
                    <li><span class="dash-sub-link" data-submodulo="creditos" data-permiso="CREDV">Créditos</span></li>
                </ul>
            </li>

            <!-- REPORTES -->
            <li class="dash-item" data-modulo="reportes">
                <button class="dash-link" type="button" aria-controls="dash-submenu-reportes" aria-expanded="false">Reportes</button>
                <ul class="dash-submenu" id="dash-submenu-reportes">
                    <li><span class="dash-sub-link" data-submodulo="informes" data-permiso="INFOV">Informes</span></li>
                    <li><span class="dash-sub-link" data-submodulo="auditoria" data-permiso="AUDIV">Auditoría</span></li>
                </ul>
            </li>

            <?php if ($accesoTotal): ?>

                <!-- ADMINISTRACIÓN — SOLO ACCESO TOTAL -->
                <li class="dash-item" data-modulo="admi">
                    <button class="dash-link" type="button" aria-controls="dash-submenu-admi" aria-expanded="false">Administración</button>

                    <ul class="dash-submenu" id="dash-submenu-admi">
                        <li><span class="dash-sub-link" data-submodulo="admi-perm" data-permiso="PERMV">Permisos</span></li>
                        <li><span class="dash-sub-link" data-submodulo="admi-usua" data-permiso="USUAV">Usuarios</span></li>
                        <li><span class="dash-sub-link" data-submodulo="admi-tiend" data-permiso="TIENV">Tiendas</span></li>
                    </ul>
                </li>

            <?php endif; ?>


        </ul>
    </nav>

    <!-- USUARIO -->
    <div class="dash-user">
        <span><?= htmlspecialchars((string) ($usuarioActual['logi'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>

        <a href="?salir=1" aria-label="Cerrar sesión" title="Cerrar sesión">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                <path d="M13 4h5a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-5" />
                <path d="M3 12h12m-4-4 4 4-4 4" />
            </svg>
            <span class="dash-sr-only">Cerrar sesión</span>
        </a>
    </div>

</header>

<script src="<?= ASSET_URL ?>/js/dash/logi/acces.js" defer></script>
<script src="<?= ASSET_URL ?>/js/dash/navb.js" defer></script>

<?php
?>