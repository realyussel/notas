<?php
require '../vendor/autoload.php';
$handler = PhpConsole\Handler::getInstance();
$handler->start(); // inicializar manejadores
PhpConsole\Helper::register(); // registrará la clase global PC

date_default_timezone_set( 'UTC' );
setlocale( LC_ALL, 'en_US.UTF8' );
error_reporting( 0 ); // Desactivar toda notificación de error
set_time_limit( 20 );

?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>md</title>

    <link rel="stylesheet" href="../build/bootstrap.min.css">
    <link rel="stylesheet" href="css/main.css">

</head>
<body>

    <div class="d-flex flex-column flex-md-row align-items-center p-2 px-md-4 mb-3 bg-white border-bottom shadow-sm">
        <h5 class="my-0 mr-md-auto font-weight-normal">Markdown</h5>

        <a class="btn btn-outline-primary" href="../edit">Editor</a>
    </div>

<?php
    $base_root    = '../edit/data';

    if(isset($_GET['user'])) {
        $user = $_GET['user'];
        $user_root = $base_root . "/" . $user;
    } else {
        header("Location: ../users");
    }

    $cuadernos = json_decode(file_get_contents($base_root . '/notebooks.json'));

    $jsondata = file_get_contents( $base_root . '/notebooks.json');
    $json = json_decode($jsondata,true);
    $cuadernos = array_keys($json[$user]);
    $num_cuadernos = count($cuadernos);
?>

<div class="container">
    
    <nav class="bg-light" aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="..">Usuarios</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo ngettext('Cuaderno', 'Cuadernos', $num_cuadernos) . " de " . $user; ?></li>
        </ol>
    </nav>
    

    <div id="main" class="card-deck mb-3">
        <?php
            $num_cuadernos = count($cuadernos);
            foreach($cuadernos as $cuaderno)
            {
                $num_apuntes = 0;
                $jsondata2 = file_get_contents( $user_root . "/" . $cuaderno . '/notebook.json');
                $json2 = json_decode($jsondata2,true);
                $apuntes = array_keys($json2['tree']);
                foreach($apuntes as $apunte) {
                    if (endsWith($apunte,".md")) {
                        $num_apuntes++;
                    }
                }

                $tam_cuaderno = getTamanio($num_apuntes);
                $num_apuntes = $num_apuntes . " " . ngettext('Apunte', 'Apuntes', $num_apuntes);
                $nom_cuaderno = urldecode($cuaderno);
                
    /* echo "<div class='card'>"; */

    echo "<a href='../view/index.php?usr=$user&nb=$cuaderno' target='_blank' class='card' style='--cards:$tam_cuaderno;'>";

echo <<<EOT
    
        <div class='child'>
            <h3 class='card-title'>$nom_cuaderno</h3>
            <p class='card-msg lead'><small>$num_apuntes</small></p>
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

  <!--footer class="pt-4 my-md-5 pt-md-5 border-top">
    <div class="row">
      <div class="col-12 col-md">
        <img class="mb-2" src="../img/yussel-footer.svg" alt="" width="24" height="24">
        <small class="d-block mb-3 text-muted">© <?php echo date("Y"); ?></small>
      </div>
    </div>
  </footer-->
</div>

</body>

    <script src="../build/jquery-3.4.1.slim.min.js"></script>
    <script src="../build/bootstrap.min.js"></script>

<?php
    function getTamanio ($apuntes) {
        /*  0 - 1,2
            1 - 3 a 5
            2 - 6 a 10
            3 - 11 a 18
            4 - 19 ó más */
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
    function endsWith($haystack,$needle,$case=true) {
        $expectedPosition = strlen($haystack) - strlen($needle);
        if ($case) {
            return strrpos($haystack, $needle, 0) === $expectedPosition;
        } else {
            return strripos($haystack, $needle, 0) === $expectedPosition;
        }
    }
?>
</html>