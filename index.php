<?php
require 'vendor/autoload.php';
$handler = PhpConsole\Handler::getInstance();
$handler->start(); // inicializar manejadores
PhpConsole\Helper::register(); // registrará la clase global PC

date_default_timezone_set('UTC');
setlocale(LC_ALL, 'en_US.UTF8');
error_reporting(0); // Desactivar toda notificación de error
set_time_limit(20);

?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>md</title>
<!-- bootstrap CSS only -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <link rel="stylesheet" href="dist/notas.css">
</head>
<body>

<?php
/*
use Jajo\JSONDB; // Solo lee formatos JSON planos
$json_db = new JSONDB( __DIR__ );
$usuarios = $json_db->select( '*' )
->from( $base_root . '/users.json' )
->get();
 */
$base_root = 'edit/data';
$usuarios = json_decode(file_get_contents($base_root . '/users.json'));
?>

<div class="col-lg-8 mx-auto p-3 py-md-5">
  <header class="d-flex align-items-center pb-3 mb-5 border-bottom">
    <div class="d-flex align-items-center text-dark text-decoration-none">
      <img class="me-2" src="view/dist/y-black.svg" alt="" width="50" height="50">
    </div>
    <nav aria-label="breadcrumb">
        <ol class="mb-0 breadcrumb">
            <li class="fs-4 breadcrumb-item active" aria-current="page">Inicio</li>
        </ol>
    </nav>
  </header>
  <main>
    <div class="row g-5">
      <div class="col-md-6">
        <div class="h-100 p-5 text-white bg-dark bg-topography rounded-3">
          <h2>Yotter</h2>
          <p>Sólo una app para tomar notas o apuntes de forma fácil y rápida.</p>
          <a class="btn btn-outline-light" type="button" href="edit">Editor</a>
        </div>
      </div>

      <div class="col-md-6">
        <h2>Usuarios</h2>
        <ul class="icon-list">
<?php
foreach ($usuarios as $usuario) {
	$username = $usuario->login;

	$jsondata = file_get_contents($base_root . '/notebooks.json');
	$json = json_decode($jsondata, true);
	$num_cuadernos_min = count($json[$username]);
	$num_cuadernos_max = $num_cuadernos . " " . ngettext('Cuaderno', 'Cuadernos', $num_cuadernos);

	echo "<li class='position-relative'><a href='notebooks?user=$username'>$username<span class='position-absolute top-0 end-0 badge rounded-pill bg-danger'>
    $num_cuadernos_min
    <span class='visually-hidden'>unread messages</span>
  </span></a></li>";
}?>
        </ul>
      </div>
    </div>
  </main>
  <footer class="py-3 mt-5 text-muted border-top">
    yussel.com.mx © 2022
  </footer>
</div>
</html>