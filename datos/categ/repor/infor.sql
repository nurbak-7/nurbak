/* ============================================================
   Ruta: datos/categ/repor/infor.sql
   Módulo: Reportes / Informes

   RESPONSABILIDAD:
   No almacena datos operativos. Los informes se construyen
   consultando las bases de origen.

   FUENTES:
   - cate → productos y vehículos.
   - inve → almacenes, stock y movimientos.
   - equiv → proveedores, órdenes y recepciones.
   - vent → clientes, operaciones, notas y devoluciones.
   - factu → facturas, boletas, NC, ND y guías.
   - pago → pagos, caja y créditos.

   Este archivo es documental porque INFORMES no necesita una
   tabla propia para comenzar. Las consultas se agregan en
   modul/repo/info/info-repo.php según el informe requerido.
============================================================ */
