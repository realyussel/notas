<?php
require 'vendor/autoload.php';
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

    <link rel="stylesheet" href="build/bootstrap.min.css">
    <link rel="stylesheet" href="css/main.css">
</head>
<body>

    <div class="d-flex flex-column flex-md-row align-items-center p-2 px-md-4 mb-3 bg-white border-bottom shadow-sm">
        <h5 class="my-0 mr-md-auto font-weight-normal">Markdown</h5>
        <a class="btn btn-outline-primary" href="edit">Editor</a>
    </div>

<?php
    $base_root    = 'edit/data';

    /*
    use Jajo\JSONDB; // Solo lee formatos JSON planos
    $json_db = new JSONDB( __DIR__ );
    $usuarios = $json_db->select( '*' )
    ->from( $base_root . '/users.json' )
    ->get();
    */

    $usuarios = json_decode(file_get_contents($base_root . '/users.json'));
?>
    <div class="container">

        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">Usuarios</h1>
            <!--div class="btn-toolbar mb-2 mb-md-0">
              <div class="list-search-icon"></div>
            </div-->
        </div>

        <div class="list-wrapper">
            <?php
                $i = 0;
                foreach($usuarios as $usuario)
                {
                    $username = $usuario->login;

                    $jsondata = file_get_contents( $base_root . '/notebooks.json');
                    $json = json_decode($jsondata,true);
                    $num_cuadernos = count($json[$username]);
                    $num_cuadernos = $num_cuadernos . " " . ngettext('Cuaderno', 'Cuadernos', $num_cuadernos);

                    echo "<a class='list-user' href='notebooks?user=$username'><div class='list-user-avatar'></div><div class='list-user-info'><p class='list-user-name mb-0'>$username</p><small class='list-user-msg'>$num_cuadernos</small></div></a>";
                    $i++;
                }
            ?>
        </div>
    </div>

</body>
    <script src="build/jquery-3.4.1.slim.min.js"></script>
    <script src="build/bootstrap.min.js"></script>
</html>