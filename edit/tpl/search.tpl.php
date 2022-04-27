<?php include DIR_TPL . 'header.tpl.php';?>
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h2>Buscar en los cuadernos</h2>
    </div>
<form action="" method="post">
    			<div class="form-group row mb-3">
                    <div class="col-4">
                        <input class="form-control" type="text" name="string" id="string" value="<?php echo (isset($_POST['string'])) ? $_POST['string'] : "" ?>" autofocus="autofocus">
                    </div>
                    <div class="col-auto">
                        <input class="btn btn-primary" type="submit" value="Buscar">
                    </div>
                </div>

</form>
<?php

if ($_POST) {
	$string = $_POST['string'];
	$dir = 'data';
	$extArray = array("md");
	if ($_POST['ext'] != "") {
		$extArray = explode(",", $_POST['ext']);
	}
	echo "<table class='table'>
  <thead>
    <tr>
      <th>Ruta de archivo</th>
      <th>Última fecha de modificación</th>
    </tr>
  </thead>
  <tbody>";
	listFolderFiles($string, $dir, $extArray);
	echo "</tbody></table>";
}
function listFolderFiles($string, $dir = '', $extArray = []) {
	if (!$dir) {
		$dir = getcwd();
	}
	$ffs = scandir($dir);
	foreach ($ffs as $ff) {
		if ($ff != '.' && $ff != '..') {
			if (is_dir($dir . '/' . $ff)) {
				listFolderFiles($string, $dir . '/' . $ff, $extArray);
			} else {
				$extension = pathinfo($dir . '/' . $ff, PATHINFO_EXTENSION);
				if (!empty($extArray)) {
					if (in_array($extension, $extArray)) {
						$content = file_get_contents($dir . '/' . $ff);
						if (strpos($content, $string) !== false) {
							echo "<tr><td>" . $dir . '/' . $ff . "</td><td>" . date("d-m-Y H:i:s", filemtime($dir . '/' . $ff)) . "</td></tr>";
						}
					}
				} else {
					$content = file_get_contents($dir . '/' . $ff);
					if (strpos($content, $string) !== false) {
						echo "<tr><td>" . $dir . '/' . $ff . "</td><td>" . date("d-m-Y H:i:s", filemtime($dir . '/' . $ff)) . "</td></tr>";
					}
				}
			}
		}
	}
}
include DIR_TPL . 'footer.tpl.php';?>