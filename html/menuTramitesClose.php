    </div>
</div>
</div>

<div id="ampliarSeguroModal" class="modal fade" role="dialog">
<div class="modal-dialog modal-lg">
    <div class="modal-content">

      <!-- Modal Header -->
      <div class="modal-header alert-info">
        <h5 class="modal-title" id="exampleModalLabel">Propuesta Adicionales de Seguros</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <!-- Modal body -->
      <div class="modal-body">
        <p>
            Seguro de vida: (con condiciones especiales)<br>
            Edad de ejemplo: 45 años<br>
            Cobertura por muerte e incapacidad total y permanente: $90.000.000<br>
            Muerte por accidente: $180.000.000<br>
            Cuota mensual: $20.802,01 + IB<br>
            Agregando enfermedades graves: $5.000.000 – Cuota mensual $22.374 + IB<br>
            Ingreso hasta los 69 años – Vigencia hasta los 80 años<br>
            a los 20 años - $90.000.000<br>
            <hr>
        </p>
        <p>
            Lucro cesante – Edad 45 años – cada $100.000 Cuota $3.000
            <hr>
        </p>
        <p>
            Corto punzante – AP con gastos de farmacia<br>
            Cobertura de AP $15.000.000 – Coctel $1.500.000 – Cuota mensual $1.624,16
            <hr>
        </p>
        <p>
            Consultorio:<br>
            Incendio: edificio $54.000.000 – contenido $10.000.000<br>
            Robo contenido $1.000.000 - Cristales $200.000 – Responsabilidad civil $50.000.000 – Costo 12 x 9.090<br>
            Solo RC: cobertura $50.000.000 - $2.242,98 por mes – por todos $1.345,78
            <hr>
        </p>
        <p>
            En caso de estar interesado comunicarse con el Productor Asesor exclusivo:<br>
            Fernando Saenz Santa Maria : fdsantamaria@gmail.com<br>
            Chat Whatsapp : 1132723952
        </p>
      </div>

      <!-- Modal footer -->
      <div class="modal-footer">
        <button type="button" class="btn btn-secundary" data-dismiss="modal">Cerrar</button>
      </div>

    </div>
</div>
</div>

<div id="seguroAgremiadoModal" class="modal fade" role="dialog">
<div class="modal-dialog">
    <div class="modal-content">

      <!-- Modal Header -->
      <div class="modal-header alert-info">
        <h5 class="modal-title">Seguro de praxis m&eacute;dica</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <!-- Modal body -->
      <div class="modal-body">
        <div class="alert alert-info mb-0">
            Estimado colega, Usted se encuentra Agremiado, debe consultar con AMEPLA por el certificado de cobertura del seguro.
            <a href="https://www.amepla.org.ar/" class="alert-link" target="_blank">Ingreso a AMEPLA</a>
        </div>
      </div>

      <!-- Modal footer -->
      <div class="modal-footer">
        <button type="button" class="btn btn-secundary" data-dismiss="modal">Cerrar</button>
      </div>

    </div>
</div>
</div>

<script>
// ocultar Mensaje
$(document).ready(function() {
    setTimeout(function() {
        $(".ocultarMensaje").css('transition', 'opacity 1.5s').css('opacity', '0');
        setTimeout(function() {
            $(".ocultarMensaje").hide();
        }, 1500);
    },3000);
});

// menu lateral en mobile
(function() {
    var hamburger = document.getElementById('tramitesHamburgerBtn');
    var sidebar = document.getElementById('tramitesSidebar');
    if (!hamburger || !sidebar) { return; }
    hamburger.addEventListener('click', function() {
        var abierto = sidebar.classList.toggle('tramites-sidebar-open');
        hamburger.setAttribute('aria-expanded', abierto ? 'true' : 'false');
    });
})();
</script>
