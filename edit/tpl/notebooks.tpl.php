<?php include DIR_TPL . 'header.tpl.php';
use Mexitek\PHPColors\Color;
?>

    <!--h2><img src="<?php echo URL_TPL; ?>img/yotter.png" alt="Yotter"></h2-->

    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h2>Cuadernos</h2>
    </div>

    <div class="list-inline fl1 fs-body1">
<?php
if (!empty($notebooks[$user['login']])) {
	$colors = [];

	foreach ($notebooks[$user['login']] as $name => $color) {
		$class = str_replace("#", "", $color);

		if (!is_int(__::search($colors, $class))) {
			$colors = __::append($colors, $class);
		}

		?>
        <a class="list-inline-item book-tag book-color-<?php echo $class; ?>" href="<?php echo URL . '?nb=' . $name; ?>"><?php echo urldecode($name); ?></a>
<?php }

	echo "<style>";
	foreach ($colors as $key => $value) {
		// list($r, $g, $b) = sscanf($value, "%02x%02x%02x");
		$color = new Color('#' . $value);
		$lighten = new Color('#' . $color->lighten());
		$darken = new Color('#' . $color->darken());
		echo ".book-color-" . $value . " {
            background-color: " . $lighten->getCssGradient() . ";
            border-color: " . $color . ";
            color: rgba(0,0,0,0.75);
        }";
		echo ".book-color-" . $value . ":hover {
            background-color: " . $darken->getCssGradient() . ";
            border-color: " . $darken . ";
            color: rgba(255,255,255,0.75);
        }";
	}
	echo "</style>";

}?>
    </div>
    <a class="btn btn-secondary my-2" href="?action=add">Comience un nuevo cuaderno</a>
<?php include DIR_TPL . 'footer.tpl.php';?>