<!-- Asegúrate de tener este input oculto para el total -->
<input type="hidden" id="totalActualizado" name="totalActualizado" value="0">

<form action="procesar_pago.php" method="POST">
    <!-- Aquí va tu tabla de expedientes con los checkboxes que armamos antes -->
    <!-- ... (tu código de la tabla) ... -->
    
    <div id="bloque_confirmar" style="display:none;">
        <button type="submit" class="btn btn-success">Pagar con Gire/Rapipago</button>
    </div>
</form>
