<?php include DIR_TPL.'header.tpl.php'; ?>
    
    <!--h2><img src="<?php echo URL_TPL; ?>img/jotter.png" alt="Jotter"></h2-->
    
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Cuadernos</h1>
    </div>

    <div class="list-inline fl1 fs-body1">
<?php
if(!empty($notebooks[$user['login']])) {
    $colors = [];

    foreach($notebooks[$user['login']] as $name => $color) {
        $class = str_replace("#", "", $color);

        if (!is_int(__::search($colors, $class))) {
            $colors = __::append($colors, $class);
        }

?>
        <a class="list-inline-item book-tag book-color-<?php echo $class; ?>" href="<?php echo URL.'?nb='.$name; ?>"><?php echo urldecode($name); ?></a>
<?php }

    echo "<style>";
    foreach ($colors as $key => $value) {
        list($r, $g, $b) = sscanf($value, "%02x%02x%02x");
        echo ".book-color-" . $value . " { background-color: rgba(" . $r . "," . $g . "," . $b . ", 0.05); color: #" . $value . "; border-color: rgba(" . $r . "," . $g . "," . $b . ", 0.1); }";
        echo ".book-color-" . $value . ":hover { background-color: #" . $value . "; }";
    }
    echo "</style>";

} ?>
    </div>
    <a class="btn btn-secondary my-2" href="?action=add">Comience un nuevo cuaderno</a>
<?php include DIR_TPL.'footer.tpl.php'; ?>