<form class="orcex-mini" data-orcex-form>
    <div class="orcex-numero">
        <label for="orcex-nume">N° ORDEN</label>
        <input id="orcex-nume" type="text" value="" data-orcex-nume readonly aria-readonly="true">
    </div>

    <div class="orcex-cam">
        <div class="orcex-campo">
            <label>RUC</label>
            <input type="text" inputmode="numeric" maxlength="11" autocomplete="off" data-orcex-ruc>
            <small data-orcex-ruc-mens></small>
        </div>
        <div class="orcex-campo">
            <label>PROVEEDOR</label>
            <input type="text" maxlength="180" data-orcex-razon>
        </div>
        <div class="orcex-campo">
            <label>DIRECCIÓN</label>
            <input type="text" maxlength="200" data-orcex-direc>
        </div>
        <div class="orcex-campo">
            <label>FECHA</label>
            <input type="date" data-orcex-fecha required>
        </div>
    </div>

    <input type="hidden" data-orcex-prove>

    <div class="orcex-prod-acciones">
        <button type="button" data-orcex-agre>+ AGREGAR PRODUCTO</button>
        <button type="button" data-orcex-libr>+ SIN PRODUCTO</button>
    </div>

    <div class="orcex-det">
        <div class="orcex-tabla-area">
            <table class="orcex-detalle">
                <thead>
                    <tr>
                        <th>CANT.</th>
                        <th>DESCRIPCIÓN</th>
                        <th>P.U.</th>
                        <th>TOTAL</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody data-orcex-deta></tbody>
            </table>
        </div>
        <div class="orcex-total">TOTAL: <strong data-orcex-total>0.00</strong></div>
    </div>

    <div class="orcex-emisor">
        <label for="orcex-emiso">EMISOR</label>
        <div class="orcex-emisor-cam">
            <input id="orcex-emiso" type="text" maxlength="20" autocomplete="off" data-orcex-emiso required placeholder="Código de usuario">
            <span class="orcex-emisor-nomb" data-orcex-emiso-nomb></span>
        </div>
        <small data-orcex-emiso-mens></small>
    </div>

    <div class="orcex-acciones">
        <button type="button" data-orcex-canc>CANCELAR</button>
        <button type="submit">GUARDAR</button>
    </div>
</form>
