<?php include DIR_TPL . 'header.tpl.php';
    use Mexitek\PHPColors\Color;
?>

    <!--h2><img src="<?php echo URL_TPL; ?>img/yotter.png" alt="Yotter"></h2-->

    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h2>Cuadernos</h2>
    </div>

<?php
    $usuario = $user['login'];
    if (isset($notebooks[$usuario])) {
        $apuntes = array_merge($notebooks[$usuario]);
    }

    if (! empty($apuntes)) {

        foreach ($apuntes as $name => $color) {
            // Si el color no existe como clave en el resultado, agrégalo
            if (! isset($resultado[$color])) {
                $resultado[$color] = [];
            }
            // Agrega la clave al subarray correspondiente al color
            $resultado[$color][] = $name;
        }

        // ksort($resultado); // Orden ascendente por color

        uksort($resultado, function ($color1, $color2) {
            $colorObj1 = new Color($color1);
            $RGB1      = $colorObj1->getRgb();
            $colorObj2 = new Color($color2);
            $RGB2      = $colorObj2->getRgb();

            // Calcula el valor "luminance" para cada color
            $luminancia1 = 0.299 * $RGB1['R'] + 0.587 * $RGB1['G'] + 0.114 * $RGB1['B'];
            $luminancia2 = 0.299 * $RGB2['R'] + 0.587 * $RGB2['G'] + 0.114 * $RGB2['B'];

            // Compara los valores de luminancia
            if ($luminancia1 < $luminancia2) {
                return -1;
            } elseif ($luminancia1 > $luminancia2) {
                return 1;
            }

            // En caso de empate, compara la suma de los componentes RGB
            $suma1 = array_sum($RGB1);
            $suma2 = array_sum($RGB2);

            return ($suma1 < $suma2) ? -1 : 1;
        });
    ?>
<style>
<?php
    // Obtener todas las claves (colores)
        $colores = array_keys($resultado);
        // Eliminar duplicados
        $colores_unicos = array_unique($colores);

        $radios    = "";
        $ck_radios = '.radio-color:has([value="book-color"]:checked) ~ .list-inline .list-inline-item:not(.book-color)';

        foreach ($colores_unicos as $color) {
            $name_color = str_replace("#", "book-color-", $color); // Remover almoadilla
            $class_name = "." . $name_color;

            $radios .= '<label class="' . $name_color . '"><input type="radio" name="show" value="' . $name_color . '"></label>';
            $ck_radios .= ', .radio-color:has([value="' . $name_color . '"]:checked) ~ .list-inline .list-inline-item:not(' . $class_name . ')';

            $new_color  = new Color($color);
            $lighten    = new Color($new_color->lighten());
            $text_color = $new_color->isDark() ? "rgba(255,255,255," : "rgba(0,0,0,";

            echo $class_name . " {
            background-color: " . $lighten->getCssGradient() . ";
            border-color: " . $new_color . ";
        } " . $class_name . ":after {
            border-color: " . $new_color . " !important;
        } a" . $class_name . " {
            color: " . $text_color . "0.75);
        } a" . $class_name . ":hover {
            color: " . $text_color . "1);
        }";
            $darken = new Color($new_color->darken());
            echo $class_name . ":hover {
            background-color: " . $darken->getCssGradient() . ";
            border-color: " . $darken . ";
        }";
        }
        echo $ck_radios;
    ?>

    {
        display: none;
        opacity: 0;
        width: 0;
        height: 0;
    }
</style>
<form class="radio-color">
    <?php echo $radios; ?>
	<button type="reset" class="btn btn-sm btn-secondary ms-2">Todos</button>
</form>
<div class="list-inline fl1 fs-body1">
<?php
    foreach ($resultado as $color => $names) {
            $name_color = str_replace("#", "book-color-", $color); // Remover almoadilla
            urldecode($name);
            foreach ($names as $name) {
                $real_name = urldecode($name);
                $book      = <<<EOD
		<a class="list-inline-item book-tag $name_color" href="?nb=$name">$real_name</a>
EOD;
                echo $book;
            }
        }
}?>
    </div>
    <a class="btn btn-secondary my-2" href="?action=add">Crear un nuevo cuaderno</a>
<?php include DIR_TPL . 'footer.tpl.php';?>