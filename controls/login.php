<?php
require_once '../dataAccess/config.php';
session_unset();
session_destroy();
require_once '../html/head.php';
require_once '../html/header.php';
?>
<div class="row">&nbsp;</div> 
<div class="col-md-12"><hr></div>
<div class="col-md-12"><h2>Acceso a tr&aacute;mites.</h2></div>
<div class="row">
  <div class="col-md-4" style="background-color:#f7f7f7;">
    <form class="form-horizontal" name="login" method="post" action="control.php">
      <div class="form-group">
        <label class="control-label" for="matricula">Matrícula:</label>
        <input type="number" class="form-control" id="matricula" name="matricula" placeholder="Ingrese su matrícula" required=""> 
      </div>
      <div class="form-group">
        <label class="control-label" for="dni">DNI:</label>
        <input type="number" class="form-control" id="dni" name="dni" placeholder="Ingrese su dni" required="">
      </div>
      <div class="form-group">
        <label class="control-label" for="mail">e-mail:</label>
        <input type="email" class="form-control" id="mail" name="mail" placeholder="e-mail registrado" required="">
      </div>
      <div class="form-group"> 
       <div class="g-recaptcha" data-sitekey="6LcCvwojAAAAALlRaguxkglVMlQMiq00mDTpF-8J"></div>
      </div>
      <div class="form-group"> 
        <div class="col-sm-offset-2 col-sm-10">
          <button type="submit" class="btn btn-primary">Ingresar</button>
        </div>
      </div>
    </form>
  </div>
  <div class="col-md-8"><img src="../public/images/frente_cm.jpg" style="width: 600px;"></div>
</div>
<?php include("../html/footer.php");?>
  </div>

</body>
</html>



