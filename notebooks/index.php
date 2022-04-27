<?php
require '../vendor/autoload.php';
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
    <title>Usuarios</title>
<!-- bootstrap CSS only -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <link rel="stylesheet" href="dist/notebooks.css">
</head>
<?php
$base_root = '../edit/data';
if (isset($_GET['user'])) {
	$user = $_GET['user'];
	$user_root = $base_root . "/" . $user;
} else {
	header("Location: ../users");
}
$cuadernos = json_decode(file_get_contents($base_root . '/notebooks.json'));
$jsondata = file_get_contents($base_root . '/notebooks.json');
$json = json_decode($jsondata, true);
$cuadernos = array_keys($json[$user]);
// $num_cuadernos = count($cuadernos);
?>
<body>
<main>
<div class="container py-4">
  <header class="mb-4 border-bottom">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="../">Inicio</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?php echo $user; ?></li>
      </ol>
    </nav>
  </header>
    <div class="col-float col-margin col-margin-bottom col-padding cols-4 mb-3">
        <?php
foreach ($cuadernos as $cuaderno) {
	$num_apuntes = 0;
	$jsondata2 = file_get_contents($user_root . "/" . $cuaderno . '/notebook.json');
	$json2 = json_decode($jsondata2, true);
	$apuntes = array_keys($json2['tree']);
	foreach ($apuntes as $apunte) {
		if (endsWith($apunte, ".md")) {
			$num_apuntes++;
		}
	}

	$tam_cuaderno = getTamanio($num_apuntes);
	$num_apuntes = $num_apuntes . " " . ngettext('Apunte', 'Apuntes', $num_apuntes);
	$nom_cuaderno = urldecode($cuaderno);

	/* echo "<div class='card'>"; */

	echo "<a href='../view/index.php?usr=$user&nb=$cuaderno' class='col note' style='--notes:$tam_cuaderno;'>";

	echo <<<EOT

        <div class='child'>
            <h4>$nom_cuaderno</h4>
            <small class="link-secondary">$num_apuntes</small>
        </div>
EOT;
	$i = 0;
	while ($i < $tam_cuaderno) {
		echo "<div class='child'></div>";
		$i++;
	}

	/* echo "</div>"; */

	echo "</a>";
}
?>
    </div>
</div>
</main>
</body>
<?php
function getTamanio($apuntes) {
	/*  0 - 1,2
		            1 - 3 a 5
		            2 - 6 a 10
		            3 - 11 a 18
	*/
	if ($apuntes < 3) {
		return 0; // Por que antes de 3 apuntes, view no funcionará
	} else if ($apuntes < 6) {
		return 1;
	} else if ($apuntes < 11) {
		return 2;
	} else if ($apuntes < 19) {
		return 3;
	} else {
		return 4;
	}
}
function endsWith($haystack, $needle, $case = true) {
	$expectedPosition = strlen($haystack) - strlen($needle);
	if ($case) {
		return strrpos($haystack, $needle, 0) === $expectedPosition;
	} else {
		return strripos($haystack, $needle, 0) === $expectedPosition;
	}
}
?>
</html>