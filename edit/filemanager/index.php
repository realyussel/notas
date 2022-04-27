<?php
require '../../vendor/autoload.php';

define('URL_TPL', '../tpl/');

$handler = PhpConsole\Handler::getInstance();
$handler->start(); // inicializar manejadores
PhpConsole\Helper::register(); // registrará la clase global PC

date_default_timezone_set('UTC');
setlocale(LC_ALL, 'en_US.UTF8');
error_reporting(0); // Desactivar toda notificación de error
set_time_limit(20);

require_once '../lib/ext/yoslogin.lib.php';
require_once '../lib/utils.class.php';
require_once '../lib/jotter.class.php';
require_once '../lib/login.class.php';

//check if user is logged in
$logger = new Login('jotter');
$user = $logger->authUser();

$use_auth = !$user['isLoggedIn'];

// Users: array('Username' => 'Password', 'Username2' => 'Password2', ...)
$auth_users = array(
	'realyussel' => 'año1912',
);

// Enable highlight.js (https://highlightjs.org/) on view's page
$use_highlightjs = true;

// highlight.js style
$highlightjs_style = 'vs';

// Default timezone for date() and time() - http://php.net/manual/en/timezones.php
$default_timezone = 'Europe/Minsk'; // UTC+3

// Root path for file manager
$root_path = dirname(dirname(__FILE__)) . '/data'; // $_SERVER['DOCUMENT_ROOT'];

// Root url for links in file manager.Relative to $http_host. Variants: '', 'path/to/subfolder'
// Will not working if $root_path will be outside of server document root
$root_url = '';

// Server hostname. Can set manually if wrong
$http_host = $_SERVER['HTTP_HOST'];

// input encoding for iconv
$iconv_input_encoding = 'CP1251';

// date() format for file modification date
$datetime_format = 'd.m.y H:i';

//--- EDIT BELOW CAREFULLY OR DO NOT EDIT AT ALL

// if fm included
if (defined('FM_EMBED')) {
	$use_auth = false;
} else {
	@set_time_limit(600);

	date_default_timezone_set($default_timezone);

	ini_set('default_charset', 'UTF-8');
	if (version_compare(PHP_VERSION, '5.6.0', '<') && function_exists('mb_internal_encoding')) {
		mb_internal_encoding('UTF-8');
	}
	if (function_exists('mb_regex_encoding')) {
		mb_regex_encoding('UTF-8');
	}

	session_cache_limiter('');
	session_name('filemanager');
	session_start();
}

if (empty($auth_users)) {
	$use_auth = false;
}

$is_https = isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1)
|| isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https';

// clean and check $root_path
$root_path = rtrim($root_path, '\\/');
$root_path = str_replace('\\', '/', $root_path);
if (!@is_dir($root_path)) {
	echo sprintf('<h1>Root path "%s" not found!</h1>', fm_enc($root_path));
	exit;
}

// clean $root_url
$root_url = fm_clean_path($root_url);

// abs path for site
defined('FM_ROOT_PATH') || define('FM_ROOT_PATH', $root_path);
defined('FM_ROOT_URL') || define('FM_ROOT_URL', ($is_https ? 'https' : 'http') . '://' . $http_host . (!empty($root_url) ? '/' . $root_url : ''));
defined('FM_SELF_URL') || define('FM_SELF_URL', ($is_https ? 'https' : 'http') . '://' . $http_host . $_SERVER['PHP_SELF']);

// logout
if (isset($_GET['logout'])) {
	unset($_SESSION['logged']);
	fm_redirect(FM_SELF_URL);
}

// Show image here
if (isset($_GET['img'])) {
	fm_show_image($_GET['img']);
}

// Auth
if ($use_auth) {
	if (isset($_SESSION['logged'], $auth_users[$_SESSION['logged']])) {
		// Logged
	} elseif (isset($_POST['fm_usr'], $_POST['fm_pwd'])) {
		// Logging In
		sleep(1);
		if (isset($auth_users[$_POST['fm_usr']]) && $_POST['fm_pwd'] === $auth_users[$_POST['fm_usr']]) {
			$_SESSION['logged'] = $_POST['fm_usr'];
			fm_set_msg('Estás conectado', 'alert-success');
			fm_redirect(FM_SELF_URL . '?p=');
		} else {
			unset($_SESSION['logged']);
			fm_set_msg('Contraseña incorrecta', 'alert-danger');
			fm_redirect(FM_SELF_URL);
		}
	} else {
		// Form
		unset($_SESSION['logged']);
		fm_show_header();
		fm_show_message();
		?>
        <div class="path">
            <form action="" method="post" style="margin:10px;text-align:center">
                <input name="fm_usr" value="" placeholder="Username" required>
                <input type="password" name="fm_pwd" value="" placeholder="Password" required>
                <input type="submit" value="Login">
            </form>
        </div>
        <?php
fm_show_footer();
		exit;
	}
}

define('FM_IS_WIN', DIRECTORY_SEPARATOR == '\\');

// always use ?p=
if (!isset($_GET['p'])) {
	fm_redirect(FM_SELF_URL . '?p=');
}

// get path
$p = isset($_GET['p']) ? $_GET['p'] : (isset($_POST['p']) ? $_POST['p'] : '');

// clean path
$p = fm_clean_path($p);

// instead globals vars
define('FM_PATH', $p);
define('FM_USE_AUTH', $use_auth);

defined('FM_ICONV_INPUT_ENC') || define('FM_ICONV_INPUT_ENC', $iconv_input_encoding);
defined('FM_USE_HIGHLIGHTJS') || define('FM_USE_HIGHLIGHTJS', $use_highlightjs);
defined('FM_HIGHLIGHTJS_STYLE') || define('FM_HIGHLIGHTJS_STYLE', $highlightjs_style);
defined('FM_DATETIME_FORMAT') || define('FM_DATETIME_FORMAT', $datetime_format);

unset($p, $use_auth, $iconv_input_encoding, $use_highlightjs, $highlightjs_style);

/*************************** ACTIONS ***************************/

// Delete file / folder
if (isset($_GET['del'])) {
	$del = $_GET['del'];
	$del = fm_clean_path($del);
	$del = str_replace('/', '', $del);
	if ($del != '' && $del != '..' && $del != '.') {
		$path = FM_ROOT_PATH;
		if (FM_PATH != '') {
			$path .= '/' . FM_PATH;
		}
		$is_dir = is_dir($path . '/' . $del);
		if (fm_rdelete($path . '/' . $del)) {
			$msg = $is_dir ? 'Carpeta <b>%s</b> eliminada' : 'Archivo <b>%s</b> eliminado';
			fm_set_msg(sprintf($msg, fm_enc($del)), 'alert-success');
		} else {
			$msg = $is_dir ? 'Carpeta <b>%s</b> no eliminada' : 'Archivo <b>%s</b> no eliminado';
			fm_set_msg(sprintf($msg, fm_enc($del)), 'alert-danger');
		}
	} else {
		fm_set_msg('Nombre de archivo o carpeta incorrecto', 'alert-danger');
	}
	fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
}

// Create folder
if (isset($_GET['new'])) {
	$new = strip_tags($_GET['new']); // remove unwanted characters from folder name
	$new = fm_clean_path($new);
	$new = str_replace('/', '', $new);
	if ($new != '' && $new != '..' && $new != '.') {
		$path = FM_ROOT_PATH;
		if (FM_PATH != '') {
			$path .= '/' . FM_PATH;
		}
		if (fm_mkdir($path . '/' . $new, false) === true) {
			fm_set_msg(sprintf('Carpeta <b>%s</b> creada', fm_enc($new)), 'alert-success');
		} elseif (fm_mkdir($path . '/' . $new, false) === $path . '/' . $new) {
			fm_set_msg(sprintf('La carpeta <b>%s</b> ya existe', fm_enc($new)), 'alert-warning');
		} else {
			fm_set_msg(sprintf('Carpeta <b>%s</b> no creada', fm_enc($new)), 'alert-danger');
		}
	} else {
		fm_set_msg('Nombre de carpeta incorrecto', 'alert-danger');
	}
	fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
}

// Copy folder / file
if (isset($_GET['copy'], $_GET['finish'])) {
	// from
	$copy = $_GET['copy'];
	$copy = fm_clean_path($copy);
	// empty path
	if ($copy == '') {
		fm_set_msg('Ruta de origen no definida', 'alert-danger');
		fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
	}
	// abs path from
	$from = FM_ROOT_PATH . '/' . $copy;
	// abs path to
	$dest = FM_ROOT_PATH;
	if (FM_PATH != '') {
		$dest .= '/' . FM_PATH;
	}
	$dest .= '/' . basename($from);
	// move?
	$move = isset($_GET['move']);
	// copy/move
	if ($from != $dest) {
		$msg_from = trim(FM_PATH . '/' . basename($from), '/');
		if ($move) {
			$rename = fm_rename($from, $dest);
			if ($rename) {
				fm_set_msg(sprintf('Movido de <b>%s</b> a <b>%s</b>', fm_enc($copy), fm_enc($msg_from)), 'alert-success');
			} elseif ($rename === null) {
				fm_set_msg('El archivo o carpeta con esa ruta ya existe', 'alert-warning');
			} else {
				fm_set_msg(sprintf('Error al mover de <b>%s</b> a <b>%s</b>', fm_enc($copy), fm_enc($msg_from)), 'alert-danger');
			}
		} else {
			if (fm_rcopy($from, $dest)) {
				fm_set_msg(sprintf('Copiado de <b>%s</b> a <b>%s</b>', fm_enc($copy), fm_enc($msg_from)), 'alert-success');
			} else {
				fm_set_msg(sprintf('Error al copiar de <b>%s</b> a <b>%s</b>', fm_enc($copy), fm_enc($msg_from)), 'alert-danger');
			}
		}
	} else {
		fm_set_msg('Las rutas no deben ser iguales', 'alert-warning');
	}
	fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
}

// Mass copy files/ folders
if (isset($_POST['file'], $_POST['copy_to'], $_POST['finish'])) {
	// from
	$path = FM_ROOT_PATH;
	if (FM_PATH != '') {
		$path .= '/' . FM_PATH;
	}
	// to
	$copy_to_path = FM_ROOT_PATH;
	$copy_to = fm_clean_path($_POST['copy_to']);
	if ($copy_to != '') {
		$copy_to_path .= '/' . $copy_to;
	}
	if ($path == $copy_to_path) {
		fm_set_msg('Las rutas no deben ser iguales', 'alert-warning');
		fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
	}
	if (!is_dir($copy_to_path)) {
		if (!fm_mkdir($copy_to_path, true)) {
			fm_set_msg('No se puede crear la carpeta de destino', 'alert-danger');
			fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
		}
	}
	// move?
	$move = isset($_POST['move']);
	// copy/move
	$errors = 0;
	$files = $_POST['file'];
	if (is_array($files) && count($files)) {
		foreach ($files as $f) {
			if ($f != '') {
				// abs path from
				$from = $path . '/' . $f;
				// abs path to
				$dest = $copy_to_path . '/' . $f;
				// do
				if ($move) {
					$rename = fm_rename($from, $dest);
					if ($rename === false) {
						$errors++;
					}
				} else {
					if (!fm_rcopy($from, $dest)) {
						$errors++;
					}
				}
			}
		}
		if ($errors == 0) {
			$msg = $move ? 'Archivos y carpetas seleccionados movidos' : 'Archivos y carpetas seleccionados copiados';
			fm_set_msg($msg, 'alert-success');
		} else {
			$msg = $move ? 'Error al mover elementos' : 'Error al copiar elementos';
			fm_set_msg($msg, 'alert-danger');
		}
	} else {
		fm_set_msg('Nada seleccionado', 'alert-warning');
	}
	fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
}

// Rename
if (isset($_GET['ren'], $_GET['to'])) {
	// old name
	$old = $_GET['ren'];
	$old = fm_clean_path($old);
	$old = str_replace('/', '', $old);
	// new name
	$new = $_GET['to'];
	$new = fm_clean_path($new);
	$new = str_replace('/', '', $new);
	// path
	$path = FM_ROOT_PATH;
	if (FM_PATH != '') {
		$path .= '/' . FM_PATH;
	}
	// rename
	if ($old != '' && $new != '') {
		if (fm_rename($path . '/' . $old, $path . '/' . $new)) {
			fm_set_msg(sprintf('Renombrado de <b>%s</b> a <b>%s</b>', fm_enc($old), fm_enc($new)), 'alert-success');
		} else {
			fm_set_msg(sprintf('Error al cambiar el nombre de <b>%s</b> a <b>%s</b>', fm_enc($old), fm_enc($new)), 'alert-danger');
		}
	} else {
		fm_set_msg('Nombres no establecidos', 'alert-danger');
	}
	fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
}

// Download
if (isset($_GET['dl'])) {
	$dl = $_GET['dl'];
	$dl = fm_clean_path($dl);
	$dl = str_replace('/', '', $dl);
	$path = FM_ROOT_PATH;
	if (FM_PATH != '') {
		$path .= '/' . FM_PATH;
	}
	if ($dl != '' && is_file($path . '/' . $dl)) {
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="' . basename($path . '/' . $dl) . '"');
		header('Content-Transfer-Encoding: binary');
		header('Connection: Keep-Alive');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		header('Content-Length: ' . filesize($path . '/' . $dl));
		readfile($path . '/' . $dl);
		exit;
	} else {
		fm_set_msg('Archivo no encontrado', 'alert-danger');
		fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
	}
}

// Upload
if (isset($_POST['upl'])) {
	$path = FM_ROOT_PATH;
	if (FM_PATH != '') {
		$path .= '/' . FM_PATH;
	}

	$errors = 0;
	$uploads = 0;
	$total = count($_FILES['upload']['name']);

	for ($i = 0; $i < $total; $i++) {
		$tmp_name = $_FILES['upload']['tmp_name'][$i];
		if (empty($_FILES['upload']['alert-danger'][$i]) && !empty($tmp_name) && $tmp_name != 'none') {
			if (move_uploaded_file($tmp_name, $path . '/' . $_FILES['upload']['name'][$i])) {
				$uploads++;
			} else {
				$errors++;
			}
		}
	}

	if ($errors == 0 && $uploads > 0) {
		fm_set_msg(sprintf('Todos los archivos subidos a <b>%s</b>', fm_enc($path)), 'alert-success');
	} elseif ($errors == 0 && $uploads == 0) {
		fm_set_msg('Nada subido', 'alert-warning');
	} else {
		fm_set_msg(sprintf('Error al subir archivos. Archivos subidos: %s', $uploads), 'alert-danger');
	}

	fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
}

// Mass deleting
if (isset($_POST['group'], $_POST['delete'])) {
	$path = FM_ROOT_PATH;
	if (FM_PATH != '') {
		$path .= '/' . FM_PATH;
	}

	$errors = 0;
	$files = $_POST['file'];
	if (is_array($files) && count($files)) {
		foreach ($files as $f) {
			if ($f != '') {
				$new_path = $path . '/' . $f;
				if (!fm_rdelete($new_path)) {
					$errors++;
				}
			}
		}
		if ($errors == 0) {
			fm_set_msg('Archivos y carpetas seleccionados eliminados', 'alert-success');
		} else {
			fm_set_msg('Error al eliminar elementos', 'alert-danger');
		}
	} else {
		fm_set_msg('Nada seleccionado', 'alert-warning');
	}

	fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
}

// Pack files
if (isset($_POST['group'], $_POST['zip'])) {
	$path = FM_ROOT_PATH;
	if (FM_PATH != '') {
		$path .= '/' . FM_PATH;
	}

	if (!class_exists('ZipArchive')) {
		fm_set_msg('Las operaciones con archivos no están disponibles', 'alert-danger');
		fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
	}

	$files = $_POST['file'];
	if (!empty($files)) {
		chdir($path);

		if (count($files) == 1) {
			$one_file = reset($files);
			$one_file = basename($one_file);
			$zipname = $one_file . '_' . date('ymd_His') . '.zip';
		} else {
			$zipname = 'archive_' . date('ymd_His') . '.zip';
		}

		$zipper = new FM_Zipper();
		$res = $zipper->create($zipname, $files);

		if ($res) {
			fm_set_msg(sprintf('Archivo <b>%s</b> creado', fm_enc($zipname)), 'alert-success');
		} else {
			fm_set_msg('Archivo no creado', 'alert-danger');
		}
	} else {
		fm_set_msg('Nada seleccionado', 'alert-warning');
	}

	fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
}

// Unpack
if (isset($_GET['unzip'])) {
	$unzip = $_GET['unzip'];
	$unzip = fm_clean_path($unzip);
	$unzip = str_replace('/', '', $unzip);

	$path = FM_ROOT_PATH;
	if (FM_PATH != '') {
		$path .= '/' . FM_PATH;
	}

	if (!class_exists('ZipArchive')) {
		fm_set_msg('Las operaciones con archivos no están disponibles', 'alert-danger');
		fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
	}

	if ($unzip != '' && is_file($path . '/' . $unzip)) {
		$zip_path = $path . '/' . $unzip;

		//to folder
		$tofolder = '';
		if (isset($_GET['tofolder'])) {
			$tofolder = pathinfo($zip_path, PATHINFO_FILENAME);
			if (fm_mkdir($path . '/' . $tofolder, true)) {
				$path .= '/' . $tofolder;
			}
		}

		$zipper = new FM_Zipper();
		$res = $zipper->unzip($zip_path, $path);

		if ($res) {
			fm_set_msg('Archivo desempaquetado', 'alert-success');
		} else {
			fm_set_msg('Archivo no desempaquetado', 'alert-danger');
		}

	} else {
		fm_set_msg('Archivo no encontrado', 'alert-danger');
	}
	fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
}

// Change Perms (not for Windows)
if (isset($_POST['chmod']) && !FM_IS_WIN) {
	$path = FM_ROOT_PATH;
	if (FM_PATH != '') {
		$path .= '/' . FM_PATH;
	}

	$file = $_POST['chmod'];
	$file = fm_clean_path($file);
	$file = str_replace('/', '', $file);
	if ($file == '' || (!is_file($path . '/' . $file) && !is_dir($path . '/' . $file))) {
		fm_set_msg('Archivo no encontrado', 'alert-danger');
		fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
	}

	$mode = 0;
	if (!empty($_POST['ur'])) {
		$mode |= 0400;
	}
	if (!empty($_POST['uw'])) {
		$mode |= 0200;
	}
	if (!empty($_POST['ux'])) {
		$mode |= 0100;
	}
	if (!empty($_POST['gr'])) {
		$mode |= 0040;
	}
	if (!empty($_POST['gw'])) {
		$mode |= 0020;
	}
	if (!empty($_POST['gx'])) {
		$mode |= 0010;
	}
	if (!empty($_POST['or'])) {
		$mode |= 0004;
	}
	if (!empty($_POST['ow'])) {
		$mode |= 0002;
	}
	if (!empty($_POST['ox'])) {
		$mode |= 0001;
	}

	if (@chmod($path . '/' . $file, $mode)) {
		fm_set_msg('Permisos cambiados', 'alert-success');
	} else {
		fm_set_msg('Permisos no cambiados', 'alert-danger');
	}

	fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
}

/*************************** /ACTIONS ***************************/

// get current path
$path = FM_ROOT_PATH;
if (FM_PATH != '') {
	$path .= '/' . FM_PATH;
}

// check path
if (!is_dir($path)) {
	fm_redirect(FM_SELF_URL . '?p=');
}

// get parent folder
$parent = fm_get_parent_path(FM_PATH);

$objects = is_readable($path) ? scandir($path) : array();
$folders = array();
$files = array();
if (is_array($objects)) {
	foreach ($objects as $file) {
		if ($file == '.' || $file == '..') {
			continue;
		}
		$new_path = $path . '/' . $file;
		if (is_file($new_path)) {
			$files[] = $file;
		} elseif (is_dir($new_path) && $file != '.' && $file != '..') {
			$folders[] = $file;
		}
	}
}

if (!empty($files)) {
	natcasesort($files);
}
if (!empty($folders)) {
	natcasesort($folders);
}

// upload form
if (isset($_GET['upload'])) {
	fm_show_header(); // HEADER
	fm_show_nav_path(FM_PATH); // current path
	?>
    <div class="card">
    	<div class="card-header">
	        <h5 class="card-title">Subiendo archivos</h5>
	        <h6 class="card-subtitle mb-2 text-muted">Carpeta de destino: <?php echo fm_enc(fm_convert_win(FM_ROOT_PATH . '/' . FM_PATH)) ?></h6>
	    </div>
        <form action="" method="post" enctype="multipart/form-data">
        	<div class="card-body">
				<p class="card-text">
		            <input type="hidden" name="p" value="<?php echo fm_enc(FM_PATH) ?>">
		            <input type="hidden" name="upl" value="1">
					<input class="form-control" type="file" name="upload[]">
					<input class="form-control" type="file" name="upload[]">
					<input class="form-control" type="file" name="upload[]">
				</p>
				<div class="mt-3">
    			<a class="me-3" href="?p=<?php echo urlencode(FM_PATH) ?>" class="card-link">Cancelar</a>
    			<button class="btn btn-primary">Subir</button>
    			</div>
    		</div>
        </form>
    </div>
    <?php
fm_show_footer();
	exit;
}

// copy form POST
if (isset($_POST['copy'])) {
	$copy_files = $_POST['file'];
	if (!is_array($copy_files) || empty($copy_files)) {
		fm_set_msg('Nada seleccionado', 'alert-warning');
		fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
	}

	fm_show_header(); // HEADER
	fm_show_nav_path(FM_PATH); // current path
	?>
    <div class="card">
        <div class="card-header">
	        <h5 class="card-title">Proceso de copiar</h5>
	    </div>
        <form action="" method="post">
            <input type="hidden" name="p" value="<?php echo fm_enc(FM_PATH) ?>">
            <input type="hidden" name="finish" value="1">
            <?php
foreach ($copy_files as $cf) {
		echo '<input type="hidden" name="file[]" value="' . fm_enc($cf) . '">' . PHP_EOL;
	}
	$copy_files_enc = array_map('fm_enc', $copy_files);
	?>
            <p class="break-word">Archivos: <b><?php echo implode('</b>, <b>', $copy_files_enc) ?></b></p>
            <p class="break-word">Carpeta de origen: <?php echo fm_enc(fm_convert_win(FM_ROOT_PATH . '/' . FM_PATH)) ?><br>
                <label for="inp_copy_to">Carpeta de destino:</label>
                <?php echo FM_ROOT_PATH ?>/<input name="copy_to" id="inp_copy_to" value="<?php echo fm_enc(FM_PATH) ?>">
            </p>
            <p><div class="form-check"><input class="form-check-input" type="checkbox" name="move" value="1">Mover</div></p>
            <p>
                <button class="btn">Copiar</button> &nbsp;
                <b><a href="?p=<?php echo urlencode(FM_PATH) ?>">Cancelar</a></b>
            </p>
        </form>
    </div>
    <?php
fm_show_footer();
	exit;
}

// copy form
if (isset($_GET['copy']) && !isset($_GET['finish'])) {
	$copy = $_GET['copy'];
	$copy = fm_clean_path($copy);
	if ($copy == '' || !file_exists(FM_ROOT_PATH . '/' . $copy)) {
		fm_set_msg('Archivo no encontrado', 'alert-danger');
		fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
	}

	fm_show_header(); // HEADER
	fm_show_nav_path(FM_PATH); // current path
	?>
    <div class="card">
        <div class="card-header">
	        <h5 class="card-title">Proceso de copiar</h5>
            <h6 class="card-subtitle mb-2 text-muted">Ruta de origen: <?php echo fm_enc(fm_convert_win(FM_ROOT_PATH . '/' . $copy)) ?><br>Carpeta de destino: <?php echo fm_enc(fm_convert_win(FM_ROOT_PATH . '/' . FM_PATH)) ?>
        	</h6>
        </div>
        <div class="card-body">
        <div class="btn-group mb-3">
            <a class="btn btn-outline-secondary" href="?p=<?php echo urlencode(FM_PATH) ?>&amp;copy=<?php echo urlencode($copy) ?>&amp;finish=1">Copiar</a>
            <a class="btn btn-outline-secondary" href="?p=<?php echo urlencode(FM_PATH) ?>&amp;copy=<?php echo urlencode($copy) ?>&amp;finish=1&amp;move=1">Mover</a>
            <a class="btn btn-outline-secondary" href="?p=<?php echo urlencode(FM_PATH) ?>">Cancelar</a>
        </div>
        <h5 class="card-title">Seleccione la carpeta:</h5>
    	</div>
        <ul class="list-group list-group-flush">
            <?php
if ($parent !== false) {
		?>
                <li class="list-group-item"><a href="?p=<?php echo urlencode($parent) ?>&amp;copy=<?php echo urlencode($copy) ?>"><img src="../tpl/img/feather/corner-left-up.svg"> ..</a></li>
            <?php
}
	foreach ($folders as $f) {
		?>
                <li class="list-group-item"><a href="?p=<?php echo urlencode(trim(FM_PATH . '/' . $f, '/')) ?>&amp;copy=<?php echo urlencode($copy) ?>"><i class="icon-folder"></i><?php echo fm_enc(fm_convert_win($f)) ?></a></li>
            <?php
}
	?>
        </ul>
    </div>
    <?php
fm_show_footer();
	exit;
}

// file viewer
if (isset($_GET['view'])) {
	$file = $_GET['view'];
	$file = fm_clean_path($file);
	$file = str_replace('/', '', $file);
	if ($file == '' || !is_file($path . '/' . $file)) {
		fm_set_msg('Archivo no encontrado', 'alert-danger');
		fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
	}

	fm_show_header(); // HEADER
	fm_show_nav_path(FM_PATH); // current path

	$file_url = FM_ROOT_URL . fm_convert_win((FM_PATH != '' ? '/' . FM_PATH : '') . '/' . $file);
	$file_path = $path . '/' . $file;

	$ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
	$mime_type = fm_get_mime_type($file_path);
	$filesize = filesize($file_path);

	$is_zip = false;
	$is_image = false;
	$is_audio = false;
	$is_video = false;
	$is_text = false;

	$view_title = 'Archivo comprimido';
	$filenames = false; // for zip
	$content = ''; // for text

	if ($ext == 'zip') {
		$is_zip = true;
		$view_title = 'zip';
		$filenames = fm_get_zif_info($file_path);
	} elseif (in_array($ext, fm_get_image_exts())) {
		$is_image = true;
		$view_title = 'Imagen';
	} elseif (in_array($ext, fm_get_audio_exts())) {
		$is_audio = true;
		$view_title = 'Audio';
	} elseif (in_array($ext, fm_get_video_exts())) {
		$is_video = true;
		$view_title = 'Video';
	} elseif (in_array($ext, fm_get_text_exts()) || substr($mime_type, 0, 4) == 'text' || in_array($mime_type, fm_get_text_mimes())) {
		$is_text = true;
		$content = file_get_contents($file_path);
	}

	?>
    <div class="card">
    	<div class="card-header">
    		<h5 class="card-title">
    			<?php echo $view_title ?> "<?php echo fm_enc(fm_convert_win($file)) ?>"
    		</h5>
    	</div>
        <div class="card-body">
	Ruta completa: <?php echo fm_enc(fm_convert_win($file_path)) ?><br>
	Tamaño del archivo: <?php echo fm_get_filesize($filesize) ?><?php if ($filesize >= 1000): ?> (<?php echo sprintf('%s bytes', $filesize) ?>)<?php endif;?><br>
	Tipo de MIME: <?php echo $mime_type ?><br>
<?php
// ZIP info
	if ($is_zip && $filenames !== false) {
		$total_files = 0;
		$total_comp = 0;
		$total_uncomp = 0;
		foreach ($filenames as $fn) {
			if (!$fn['folder']) {
				$total_files++;
			}
			$total_comp += $fn['compressed_size'];
			$total_uncomp += $fn['filesize'];
		}
		?>
		Archivos en el paquete: <?php echo $total_files ?><br>
		Tamaño total: <?php echo fm_get_filesize($total_uncomp) ?><br>
		Tamaño del paquete: <?php echo fm_get_filesize($total_comp) ?><br>
		Compresión: <?php echo round(($total_comp / $total_uncomp) * 100) ?>%<br>
		<?php
}
	// Image info
	if ($is_image) {
		$image_size = getimagesize($file_path);
		echo 'Image sizes: ' . (isset($image_size[0]) ? $image_size[0] : '0') . ' x ' . (isset($image_size[1]) ? $image_size[1] : '0') . '<br>';
	}
	// Text info
	if ($is_text) {
		$is_utf8 = fm_is_utf8($content);
		if (function_exists('iconv')) {
			if (!$is_utf8) {
				$content = iconv(FM_ICONV_INPUT_ENC, 'UTF-8//IGNORE', $content);
			}
		}
		echo 'Charset: ' . ($is_utf8 ? 'utf-8' : '8 bit') . '<br>';
	}
	?>
	<div class="btn-group my-3">
        <a class="btn btn-outline-secondary" href="?p=<?php echo urlencode(FM_PATH) ?>&amp;dl=<?php echo urlencode($file) ?>">Descargar</a>
        <a class="btn btn-outline-secondary" href="<?php echo fm_enc($file_url) ?>" target="_blank">Abrir</a>
            <?php
// ZIP actions
	if ($is_zip && $filenames !== false) {
		$zip_name = pathinfo($file_path, PATHINFO_FILENAME);
		?>
        <a class="btn btn-outline-secondary" href="?p=<?php echo urlencode(FM_PATH) ?>&amp;unzip=<?php echo urlencode($file) ?>">Descomprimir</a>
        <a class="btn btn-outline-secondary" href="?p=<?php echo urlencode(FM_PATH) ?>&amp;unzip=<?php echo urlencode($file) ?>&amp;tofolder=1" title="Unpack to <?php echo fm_enc($zip_name) ?>">Descomprimir en carpeta</a>
    <?php }?>
        <a class="btn btn-outline-secondary" href="?p=<?php echo urlencode(FM_PATH) ?>">Volver</a>
    </div>
    <div>
        <?php
if ($is_zip) {
		// ZIP content
		if ($filenames !== false) {
			echo '<code class="maxheight">';
			foreach ($filenames as $fn) {
				if ($fn['folder']) {
					echo '<b>' . fm_enc($fn['name']) . '</b><br>';
				} else {
					echo $fn['name'] . ' (' . fm_get_filesize($fn['filesize']) . ')<br>';
				}
			}
			echo '</code>';
		} else {
			echo '<p>Error al obtener información del archivo</p>';
		}
	} elseif ($is_image) {
		// Image content
		if (in_array($ext, array('gif', 'jpg', 'jpeg', 'png', 'bmp', 'ico'))) {
			echo '<p><img src="' . fm_enc($file_url) . '" alt="" class="preview-img"></p>';
		}
	} elseif ($is_audio) {
		// Audio content
		echo '<p><audio src="' . fm_enc($file_url) . '" controls preload="metadata"></audio></p>';
	} elseif ($is_video) {
		// Video content
		echo '<div class="preview-video"><video src="' . fm_enc($file_url) . '" width="640" height="360" controls preload="metadata"></video></div>';
	} elseif ($is_text) {
		if (FM_USE_HIGHLIGHTJS) {
			// highlight
			$hljs_classes = array(
				'shtml' => 'xml',
				'htaccess' => 'apache',
				'phtml' => 'php',
				'lock' => 'json',
				'svg' => 'xml',
			);
			$hljs_class = isset($hljs_classes[$ext]) ? 'lang-' . $hljs_classes[$ext] : 'lang-' . $ext;
			if (empty($ext) || in_array(strtolower($file), fm_get_text_names()) || preg_match('#\.min\.(css|js)$#i', $file)) {
				$hljs_class = 'nohighlight';
			}
			$content = '<pre class="with-hljs"><code class="' . $hljs_class . '">' . fm_enc($content) . '</code></pre>';
		} elseif (in_array($ext, array('php', 'php4', 'php5', 'phtml', 'phps'))) {
			// php highlight
			$content = highlight_string($content, true);
		} else {
			$content = '<pre>' . fm_enc($content) . '</pre>';
		}
		echo $content;
	}
	?>
    </div></div></div>
    <?php
fm_show_footer();
	exit;
}

// chmod (not for Windows)
if (isset($_GET['chmod']) && !FM_IS_WIN) {
	$file = $_GET['chmod'];
	$file = fm_clean_path($file);
	$file = str_replace('/', '', $file);
	if ($file == '' || (!is_file($path . '/' . $file) && !is_dir($path . '/' . $file))) {
		fm_set_msg('Archivo no encontrado', 'alert-danger');
		fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
	}

	fm_show_header(); // HEADER
	fm_show_nav_path(FM_PATH); // current path

	$file_url = FM_ROOT_URL . (FM_PATH != '' ? '/' . FM_PATH : '') . '/' . $file;
	$file_path = $path . '/' . $file;

	$mode = fileperms($path . '/' . $file);

	?>
    <div class="card">
        <div class="card-header">
        	<h5 class="card-title">Cambiar permisos</h5>
        	<h6 class="card-subtitle mb-2 text-muted">Ruta completa: <?php echo fm_enc($file_path) ?></h6>
	    </div>
        <form action="" method="post">
        	<div class="card-body">
			<input type="hidden" name="p" value="<?php echo fm_enc(FM_PATH) ?>">
            <input type="hidden" name="chmod" value="<?php echo fm_enc($file) ?>">
<div class="table-responsive">
            <table class="table">
                <thead><tr>
                    <td scope="col"></td>
                    <td scope="col"><b>Dueño</b></td>
                    <td scope="col"><b>Grupo</b></td>
                    <td scope="col"><b>Otro</b></td>
                </tr></thead>
                <tbody><tr>
                    <td scope="row"><b>Leer</b></td>
                    <td><div class="form-check"><input class="form-check-input" type="checkbox" name="ur" value="1"<?php echo ($mode & 00400) ? ' checked' : '' ?>></div></td>
                    <td><div class="form-check"><input class="form-check-input" type="checkbox" name="gr" value="1"<?php echo ($mode & 00040) ? ' checked' : '' ?>></div></td>
                    <td><div class="form-check"><input class="form-check-input" type="checkbox" name="or" value="1"<?php echo ($mode & 00004) ? ' checked' : '' ?>></div></td>
                </tr>
                <tr>
                    <td scope="row"><b>Escribir</b></td>
                    <td><div class="form-check"><input class="form-check-input" type="checkbox" name="uw" value="1"<?php echo ($mode & 00200) ? ' checked' : '' ?>></div></td>
                    <td><div class="form-check"><input class="form-check-input" type="checkbox" name="gw" value="1"<?php echo ($mode & 00020) ? ' checked' : '' ?>></div></td>
                    <td><div class="form-check"><input class="form-check-input" type="checkbox" name="ow" value="1"<?php echo ($mode & 00002) ? ' checked' : '' ?>></div></td>
                </tr>
                <tr>
                    <td scope="row"><b>Ejecutar</b></td>
                    <td><div class="form-check"><input class="form-check-input" type="checkbox" name="ux" value="1"<?php echo ($mode & 00100) ? ' checked' : '' ?>></div></td>
                    <td><div class="form-check"><input class="form-check-input" type="checkbox" name="gx" value="1"<?php echo ($mode & 00010) ? ' checked' : '' ?>></div></td>
                    <td><div class="form-check"><input class="form-check-input" type="checkbox" name="ox" value="1"<?php echo ($mode & 00001) ? ' checked' : '' ?>></div></td>
                </tr></tbody>
            </table>
</div>

<div class="mt-3">
	<a class="me-3" href="?p=<?php echo urlencode(FM_PATH) ?>">Cancelar</a>
	<button class="btn btn-primary">Cambiar</button>
</div></div>
        </form>

    </div>
    <?php
fm_show_footer();
	exit;
}

//--- FILEMANAGER MAIN
fm_show_header(); // HEADER
fm_show_nav_path(FM_PATH); // current path

// messages
fm_show_message();

$num_files = count($files);
$num_folders = count($folders);
$all_files_size = 0;
?>
<form action="" method="post">
<input type="hidden" name="p" value="<?php echo fm_enc(FM_PATH) ?>">
<input type="hidden" name="group" value="1">
<table class="table table-sm table-bordered">
	<thead>
	<tr>
<th class="wcheckbox"><div class="form-check"><input class="form-check-input" type="checkbox" title="Invert selection" onclick="checkbox_toggle()"></div></th>
<th>Nombre</th><th style="width:10%">Tamaño</th>
<th style="width:12%">Modificado</th>
<?php if (!FM_IS_WIN): ?><th style="width:6%">Permisos</th><?php endif;?>
<th style="width:13%"></th></tr></thead><tbody>
<?php
// link to parent folder
if ($parent !== false) {
	?>
<tr><td></td><td colspan="<?php echo !FM_IS_WIN ? '6' : '4' ?>"><a href="?p=<?php echo urlencode($parent) ?>"><img src="../tpl/img/feather/corner-left-up.svg"> ..</a></td></tr>
<?php
}
foreach ($folders as $f) {
	$is_link = is_link($path . '/' . $f);
	$img = $is_link ? 'icon-link_folder' : 'icon-folder';
	$modif = date(FM_DATETIME_FORMAT, filemtime($path . '/' . $f));
	$perms = substr(decoct(fileperms($path . '/' . $f)), -4);
	if (function_exists('posix_getpwuid') && function_exists('posix_getgrgid')) {
		$owner = posix_getpwuid(fileowner($path . '/' . $f));
		$group = posix_getgrgid(filegroup($path . '/' . $f));
	} else {
		$owner = array('name' => '?');
		$group = array('name' => '?');
	}
	?>
<tr>
<td><div class="form-check"><input class="form-check-input" type="checkbox" name="file[]" value="<?php echo fm_enc($f) ?>"></div></td>
<td><div class="filename"><a href="?p=<?php echo urlencode(trim(FM_PATH . '/' . $f, '/')) ?>"><i class="<?php echo $img ?>"></i><?php echo fm_enc(fm_convert_win($f)) ?></a><?php echo ($is_link ? ' &rarr; <i>' . fm_enc(readlink($path . '/' . $f)) . '</i>' : '') ?></div></td>
<td>Folder</td><td><?php echo $modif ?></td>
<?php if (!FM_IS_WIN): ?>
<td><a title="Cambiar permisos" href="?p=<?php echo urlencode(FM_PATH) ?>&amp;chmod=<?php echo urlencode($f) ?>"><?php echo $perms ?></a></td>
<?php endif;?>
<td class="wbtngroup">
<div class="btn-group btn-group-sm" role="group">
<a type="button" class="btn btn-outline-danger" title="Borrar" href="?p=<?php echo urlencode(FM_PATH) ?>&amp;del=<?php echo urlencode($f) ?>" onclick="return confirm('¿Eliminar carpeta?');"><i class="bi-folder-x"></i></a>
<a type="button" class="btn btn-outline-primary" title="Copiar a..." href="?p=&amp;copy=<?php echo urlencode(trim(FM_PATH . '/' . $f, '/')) ?>"><i class="bi bi-subtract"></i></a>
<a type="button" class="btn btn-outline-primary" title="Renombrar" href="#" onclick="rename('<?php echo fm_enc(FM_PATH) ?>', '<?php echo fm_enc($f) ?>');return false;"><i class="bi bi-123"></i></a>
<a type="button" class="btn btn-outline-primary" title="Enlace directo" href="<?php echo fm_enc(FM_ROOT_URL . '/notas/edit/data/' . (FM_PATH != '' ? '/' . FM_PATH : '') . '/' . $f) ?>" target="_blank"><i class="bi bi-link-45deg"></i></a>
</div>
</td></tr>
    <?php
flush();
}
foreach ($files as $f) {
	$is_link = is_link($path . '/' . $f);
	$img = $is_link ? 'icon-link_file' : fm_get_file_icon_class($path . '/' . $f);
	$modif = date(FM_DATETIME_FORMAT, filemtime($path . '/' . $f));
	$filesize_raw = filesize($path . '/' . $f);
	$filesize = fm_get_filesize($filesize_raw);
	$filelink = '?p=' . urlencode(FM_PATH) . '&view=' . urlencode($f);
	$all_files_size += $filesize_raw;
	$perms = substr(decoct(fileperms($path . '/' . $f)), -4);
	if (function_exists('posix_getpwuid') && function_exists('posix_getgrgid')) {
		$owner = posix_getpwuid(fileowner($path . '/' . $f));
		$group = posix_getgrgid(filegroup($path . '/' . $f));
	} else {
		$owner = array('name' => '?');
		$group = array('name' => '?');
	}
	?>
<tr>
<td><div class="form-check"><input class="form-check-input" type="checkbox" name="file[]" value="<?php echo fm_enc($f) ?>"></div></td>
<td><div class="filename"><a href="<?php echo fm_enc($filelink) ?>" title="File info"><i class="<?php echo $img ?>"></i><?php echo fm_enc(fm_convert_win($f)) ?></a><?php echo ($is_link ? ' &rarr; <i>' . fm_enc(readlink($path . '/' . $f)) . '</i>' : '') ?></div></td>
<td><span class="gray" title="<?php printf('%s bytes', $filesize_raw)?>"><?php echo $filesize ?></span></td>
<td><?php echo $modif ?></td>
<?php if (!FM_IS_WIN): ?>
<td><a title="Cambiar permisos" href="?p=<?php echo urlencode(FM_PATH) ?>&amp;chmod=<?php echo urlencode($f) ?>"><?php echo $perms ?></a></td>
<?php endif;?>
<td class="wbtngroup">
	<div class="btn-group btn-group-sm" role="group">
<a type="button" class="btn btn-outline-danger" title="Borrar" href="?p=<?php echo urlencode(FM_PATH) ?>&amp;del=<?php echo urlencode($f) ?>" onclick="return confirm('¿Eliminar carpeta?');"><i class="bi-file-x"></i></a>
<a type="button" class="btn btn-outline-primary" title="Copiar a..." href="?p=<?php echo urlencode(FM_PATH) ?>&amp;copy=<?php echo urlencode(trim(FM_PATH . '/' . $f, '/')) ?>"><i class="bi bi-subtract"></i></a>
<a type="button" class="btn btn-outline-primary" title="Renombrar" href="#" onclick="rename('<?php echo fm_enc(FM_PATH) ?>', '<?php echo fm_enc($f) ?>');return false;"><i class="bi bi-123"></i></a>

<!-- echo fm_enc(FM_ROOT_URL . (FM_PATH != '' ? '/' . FM_PATH : '') . '/' . $f) -->

<a type="button" class="btn btn-outline-primary" title="Enlace directo" href="<?php echo fm_enc(FM_ROOT_URL . '/notas/edit/data/' . (FM_PATH != '' ? '/' . FM_PATH : '') . '/' . $f) ?>" target="_blank"><i class="bi bi-link-45deg"></i></a>

<a type="button" class="btn btn-outline-primary" title="Descargar" href="?p=<?php echo urlencode(FM_PATH) ?>&amp;dl=<?php echo urlencode($f) ?>"><i class="bi bi-download"></i></a>
</div>
</td></tr>
    <?php
flush();
}
?>
</tbody>


<?php if (empty($folders) && empty($files)) {?>
<tr><td></td><td colspan="<?php echo !FM_IS_WIN ? '6' : '4' ?>"><em>La carpeta está vacía</em></td></tr>
<?php
} else {
	?>
<tfoot><tr><td class="gray"></td><td class="gray" colspan="<?php echo !FM_IS_WIN ? '6' : '4' ?>">
Tamaño total: <span title="<?php printf('%s bytes', $all_files_size)?>"><?php echo fm_get_filesize($all_files_size) ?></span>,
Archivos: <?php echo $num_files ?>,
Carpetas: <?php echo $num_folders ?>
</td></tr></tfoot>
<?php
}
?>
</table>
<div class="btn-toolbar mb-3" role="toolbar">
<div class="btn-group me-auto" role="group">
	<button type="button" class="btn btn-outline-secondary" onclick="select_all();return false;"><i class="bi-check-square"></i>&nbsp;Seleccionar todo</button>
	<button type="button" class="btn btn-outline-secondary" onclick="unselect_all();return false;"><i class="bi-x-square"></i>&nbsp;Deseleccionar todo</button>
	<button type="button" class="btn btn-outline-secondary" onclick="invert_all();return false;"><i class="bi-exclude"></i>&nbsp;Invertir selección
	</button>
</div>
<div>
	<input type="submit" class="btn btn-danger" name="delete" value="Eliminar" onclick="return confirm('¿Eliminar archivos y carpetas seleccionados?')">
	<input type="submit" class="btn btn-secondary" name="zip" value="Paquete" onclick="return confirm('¿Crear archivo?')">
	<input type="submit" class="btn btn-primary" name="copy" value="Copiar"></div>
</div>
</form>

<?php
fm_show_footer();

//--- END

// Functions

/**
 * Delete  file or folder (recursively)
 * @param string $path
 * @return bool
 */
function fm_rdelete($path) {
	if (is_link($path)) {
		return unlink($path);
	} elseif (is_dir($path)) {
		$objects = scandir($path);
		$ok = true;
		if (is_array($objects)) {
			foreach ($objects as $file) {
				if ($file != '.' && $file != '..') {
					if (!fm_rdelete($path . '/' . $file)) {
						$ok = false;
					}
				}
			}
		}
		return ($ok) ? rmdir($path) : false;
	} elseif (is_file($path)) {
		return unlink($path);
	}
	return false;
}

/**
 * Recursive chmod
 * @param string $path
 * @param int $filemode
 * @param int $dirmode
 * @return bool
 * @todo Will use in mass chmod
 */
function fm_rchmod($path, $filemode, $dirmode) {
	if (is_dir($path)) {
		if (!chmod($path, $dirmode)) {
			return false;
		}
		$objects = scandir($path);
		if (is_array($objects)) {
			foreach ($objects as $file) {
				if ($file != '.' && $file != '..') {
					if (!fm_rchmod($path . '/' . $file, $filemode, $dirmode)) {
						return false;
					}
				}
			}
		}
		return true;
	} elseif (is_link($path)) {
		return true;
	} elseif (is_file($path)) {
		return chmod($path, $filemode);
	}
	return false;
}

/**
 * Safely rename
 * @param string $old
 * @param string $new
 * @return bool|null
 */
function fm_rename($old, $new) {
	return (!file_exists($new) && file_exists($old)) ? rename($old, $new) : null;
}

/**
 * Copy file or folder (recursively).
 * @param string $path
 * @param string $dest
 * @param bool $upd Update files
 * @param bool $force Create folder with same names instead file
 * @return bool
 */
function fm_rcopy($path, $dest, $upd = true, $force = true) {
	if (is_dir($path)) {
		if (!fm_mkdir($dest, $force)) {
			return false;
		}
		$objects = scandir($path);
		$ok = true;
		if (is_array($objects)) {
			foreach ($objects as $file) {
				if ($file != '.' && $file != '..') {
					if (!fm_rcopy($path . '/' . $file, $dest . '/' . $file)) {
						$ok = false;
					}
				}
			}
		}
		return $ok;
	} elseif (is_file($path)) {
		return fm_copy($path, $dest, $upd);
	}
	return false;
}

/**
 * Safely create folder
 * @param string $dir
 * @param bool $force
 * @return bool
 */
function fm_mkdir($dir, $force) {
	if (file_exists($dir)) {
		if (is_dir($dir)) {
			return $dir;
		} elseif (!$force) {
			return false;
		}
		unlink($dir);
	}
	return mkdir($dir, 0777, true);
}

/**
 * Safely copy file
 * @param string $f1
 * @param string $f2
 * @param bool $upd
 * @return bool
 */
function fm_copy($f1, $f2, $upd) {
	$time1 = filemtime($f1);
	if (file_exists($f2)) {
		$time2 = filemtime($f2);
		if ($time2 >= $time1 && $upd) {
			return false;
		}
	}
	$ok = copy($f1, $f2);
	if ($ok) {
		touch($f2, $time1);
	}
	return $ok;
}

/**
 * Get mime type
 * @param string $file_path
 * @return mixed|string
 */
function fm_get_mime_type($file_path) {
	if (function_exists('finfo_open')) {
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mime = finfo_file($finfo, $file_path);
		finfo_close($finfo);
		return $mime;
	} elseif (function_exists('mime_content_type')) {
		return mime_content_type($file_path);
	} elseif (!stristr(ini_get('disable_functions'), 'shell_exec')) {
		$file = escapeshellarg($file_path);
		$mime = shell_exec('file -bi ' . $file);
		return $mime;
	} else {
		return '--';
	}
}

/**
 * HTTP Redirect
 * @param string $url
 * @param int $code
 */
function fm_redirect($url, $code = 302) {
	header('Location: ' . $url, true, $code);
	exit;
}

/**
 * Clean path
 * @param string $path
 * @return string
 */
function fm_clean_path($path) {
	$path = trim($path);
	$path = trim($path, '\\/');
	$path = str_replace(array('../', '..\\'), '', $path);
	if ($path == '..') {
		$path = '';
	}
	return str_replace('\\', '/', $path);
}

/**
 * Get parent path
 * @param string $path
 * @return bool|string
 */
function fm_get_parent_path($path) {
	$path = fm_clean_path($path);
	if ($path != '') {
		$array = explode('/', $path);
		if (count($array) > 1) {
			$array = array_slice($array, 0, -1);
			return implode('/', $array);
		}
		return '';
	}
	return false;
}

/**
 * Get nice filesize
 * @param int $size
 * @return string
 */
function fm_get_filesize($size) {
	if ($size < 1000) {
		return sprintf('%s B', $size);
	} elseif (($size / 1024) < 1000) {
		return sprintf('%s KiB', round(($size / 1024), 2));
	} elseif (($size / 1024 / 1024) < 1000) {
		return sprintf('%s MiB', round(($size / 1024 / 1024), 2));
	} elseif (($size / 1024 / 1024 / 1024) < 1000) {
		return sprintf('%s GiB', round(($size / 1024 / 1024 / 1024), 2));
	} else {
		return sprintf('%s TiB', round(($size / 1024 / 1024 / 1024 / 1024), 2));
	}
}

/**
 * Get info about zip archive
 * @param string $path
 * @return array|bool
 */
function fm_get_zif_info($path) {
	if (function_exists('zip_open')) {
		$arch = zip_open($path);
		if ($arch) {
			$filenames = array();
			while ($zip_entry = zip_read($arch)) {
				$zip_name = zip_entry_name($zip_entry);
				$zip_folder = substr($zip_name, -1) == '/';
				$filenames[] = array(
					'name' => $zip_name,
					'filesize' => zip_entry_filesize($zip_entry),
					'compressed_size' => zip_entry_compressedsize($zip_entry),
					'folder' => $zip_folder,
					//'compression_method' => zip_entry_compressionmethod($zip_entry),
				);
			}
			zip_close($arch);
			return $filenames;
		}
	}
	return false;
}

/**
 * Encode html entities
 * @param string $text
 * @return string
 */
function fm_enc($text) {
	return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Save message in session
 * @param string $msg
 * @param string $status
 */
function fm_set_msg($msg, $status = 'ok') {
	$_SESSION['message'] = $msg;
	$_SESSION['status'] = $status;
}

/**
 * Check if string is in UTF-8
 * @param string $string
 * @return int
 */
function fm_is_utf8($string) {
	return preg_match('//u', $string);
}

/**
 * Convert file name to UTF-8 in Windows
 * @param string $filename
 * @return string
 */
function fm_convert_win($filename) {
	if (FM_IS_WIN && function_exists('iconv')) {
		$filename = iconv(FM_ICONV_INPUT_ENC, 'UTF-8//IGNORE', $filename);
	}
	return $filename;
}

/**
 * Get CSS classname for file
 * @param string $path
 * @return string
 */
function fm_get_file_icon_class($path) {
	// get extension
	$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

	switch ($ext) {
	case 'ico':case 'gif':case 'jpg':case 'jpeg':case 'jpc':case 'jp2':
	case 'jpx':case 'xbm':case 'wbmp':case 'png':case 'bmp':case 'tif':
	case 'tiff':
		$img = 'bi-file-image';
		break;
	case 'txt':case 'css':case 'ini':case 'conf':case 'log':case 'htaccess':
	case 'passwd':case 'ftpquota':case 'sql':case 'js':case 'json':case 'sh':
	case 'config':case 'twig':case 'tpl':case 'md':case 'gitignore':
	case 'less':case 'sass':case 'scss':case 'c':case 'cpp':case 'cs':case 'py':
	case 'map':case 'lock':case 'dtd':
		$img = 'bi-file-text';
		break;
	case 'zip':case 'rar':case 'gz':case 'tar':case '7z':
		$img = 'bi-file-zip';
		break;
	case 'php':case 'php4':case 'php5':case 'phps':case 'phtml':
		$img = 'bi-filetype-php';
		break;
	case 'htm':case 'html':case 'shtml':case 'xhtml':
		$img = 'bi-filetype-html';
		break;
	case 'xml':case 'xsl':case 'svg':
		$img = 'bi-file-excel';
		break;
	case 'wav':case 'mp3':case 'mp2':case 'm4a':case 'aac':case 'ogg':
	case 'oga':case 'wma':case 'mka':case 'flac':case 'ac3':case 'tds':
		$img = 'bi-file-music';
		break;
	case 'm3u':case 'm3u8':case 'pls':case 'cue':
		$img = 'bi-file-music';
		break;
	case 'avi':case 'mpg':case 'mpeg':case 'mp4':case 'm4v':case 'flv':
	case 'f4v':case 'ogm':case 'ogv':case 'mov':case 'mkv':case '3gp':
	case 'asf':case 'wmv':
		$img = 'icon-file_film';
		break;
	case 'eml':case 'msg':
		$img = 'bi-mailbox';
		break;
	case 'xls':case 'xlsx':
		$img = 'bi-file-x';
		break;
	case 'csv':
		$img = 'bi-filetype-csv';
		break;
	case 'doc':case 'docx':
		$img = 'bi-file-word';
		break;
	case 'ppt':case 'pptx':
		$img = 'bi-file-ppt';
		break;
	case 'ttf':case 'ttc':case 'otf':case 'woff':case 'woff2':case 'eot':case 'fon':
		$img = 'bi-file-font';
		break;
	case 'pdf':
		$img = 'bi-file-pdf';
		break;
	case 'psd':
		$img = 'bi-filetype-psd';
		break;
	case 'ai':case 'eps':
		$img = 'icon-file_illustrator';
		break;
	case 'exe':case 'msi':
		$img = 'bi-filetype-exe';
		break;
	case 'bat':
		$img = 'bi-terminal';
		break;
	default:
		$img = 'bi-file';
	}

	return $img;
}

/**
 * Get image files extensions
 * @return array
 */
function fm_get_image_exts() {
	return array('ico', 'gif', 'jpg', 'jpeg', 'jpc', 'jp2', 'jpx', 'xbm', 'wbmp', 'png', 'bmp', 'tif', 'tiff', 'psd');
}

/**
 * Get video files extensions
 * @return array
 */
function fm_get_video_exts() {
	return array('webm', 'mp4', 'm4v', 'ogm', 'ogv', 'mov');
}

/**
 * Get audio files extensions
 * @return array
 */
function fm_get_audio_exts() {
	return array('wav', 'mp3', 'ogg', 'm4a');
}

/**
 * Get text file extensions
 * @return array
 */
function fm_get_text_exts() {
	return array(
		'txt', 'css', 'ini', 'conf', 'log', 'htaccess', 'passwd', 'ftpquota', 'sql', 'js', 'json', 'sh', 'config',
		'php', 'php4', 'php5', 'phps', 'phtml', 'htm', 'html', 'shtml', 'xhtml', 'xml', 'xsl', 'm3u', 'm3u8', 'pls', 'cue',
		'eml', 'msg', 'csv', 'bat', 'twig', 'tpl', 'md', 'gitignore', 'less', 'sass', 'scss', 'c', 'cpp', 'cs', 'py',
		'map', 'lock', 'dtd', 'svg',
	);
}

/**
 * Get mime types of text files
 * @return array
 */
function fm_get_text_mimes() {
	return array(
		'application/xml',
		'application/javascript',
		'application/x-javascript',
		'image/svg+xml',
		'message/rfc822',
	);
}

/**
 * Get file names of text files w/o extensions
 * @return array
 */
function fm_get_text_names() {
	return array(
		'license',
		'readme',
		'authors',
		'contributors',
		'changelog',
	);
}

/**
 * Class to work with zip files (using ZipArchive)
 */
class FM_Zipper {
	private $zip;

	public function __construct() {
		$this->zip = new ZipArchive();
	}

	/**
	 * Create archive with name $filename and files $files (RELATIVE PATHS!)
	 * @param string $filename
	 * @param array|string $files
	 * @return bool
	 */
	public function create($filename, $files) {
		$res = $this->zip->open($filename, ZipArchive::CREATE);
		if ($res !== true) {
			return false;
		}
		if (is_array($files)) {
			foreach ($files as $f) {
				if (!$this->addFileOrDir($f)) {
					$this->zip->close();
					return false;
				}
			}
			$this->zip->close();
			return true;
		} else {
			if ($this->addFileOrDir($files)) {
				$this->zip->close();
				return true;
			}
			return false;
		}
	}

	/**
	 * Extract archive $filename to folder $path (RELATIVE OR ABSOLUTE PATHS)
	 * @param string $filename
	 * @param string $path
	 * @return bool
	 */
	public function unzip($filename, $path) {
		$res = $this->zip->open($filename);
		if ($res !== true) {
			return false;
		}
		if ($this->zip->extractTo($path)) {
			$this->zip->close();
			return true;
		}
		return false;
	}

	/**
	 * Add file/folder to archive
	 * @param string $filename
	 * @return bool
	 */
	private function addFileOrDir($filename) {
		if (is_file($filename)) {
			return $this->zip->addFile($filename);
		} elseif (is_dir($filename)) {
			return $this->addDir($filename);
		}
		return false;
	}

	/**
	 * Add folder recursively
	 * @param string $path
	 * @return bool
	 */
	private function addDir($path) {
		if (!$this->zip->addEmptyDir($path)) {
			return false;
		}
		$objects = scandir($path);
		if (is_array($objects)) {
			foreach ($objects as $file) {
				if ($file != '.' && $file != '..') {
					if (is_dir($path . '/' . $file)) {
						if (!$this->addDir($path . '/' . $file)) {
							return false;
						}
					} elseif (is_file($path . '/' . $file)) {
						if (!$this->zip->addFile($path . '/' . $file)) {
							return false;
						}
					}
				}
			}
			return true;
		}
		return false;
	}
}

//--- templates functions

/**
 * Show nav block
 * @param string $path
 */
function fm_show_nav_path($path) {
	?>

<nav class="navbar navbar-light bg-light mb-2">
  <div class="container d-flex flex-wrap justify-content-center">
    <a title="<?php echo FM_ROOT_PATH; ?>" class="navbar-brand" href="?p=">
    	<img src="<?php echo URL_TPL; ?>img/feather/home.svg" alt="Home">
    </a>
    <ul class="nav me-auto">
        <?php
$path = fm_clean_path($path);
	$sep = ' ';
	if ($path != '') {
		$exploded = explode('/', $path);
		$count = count($exploded);
		$array = array();
		$parent = '';
		for ($i = 0; $i < $count; $i++) {
			$parent = trim($parent . '/' . $exploded[$i], '/');
			$parent_enc = urlencode($parent);
			$array[] = "<li class='nav-item'><a class='nav-link link-dark px-1' href='?p={$parent_enc}'> <i class='bi bi-slash-lg'></i>" . fm_enc(fm_convert_win($exploded[$i])) . "</a></li>";
		}
		echo implode($sep, $array);
	}
	?>
	</ul>
	<div class="d-flex">
	      	<a class="nav-link" title="Upload" href="?p=<?php echo urlencode(FM_PATH) ?>&amp;upload">
	      		<img src="<?php echo URL_TPL; ?>img/feather/upload.svg" alt="Upload">
	    	</a>
	      	<a class="nav-link" title="New folder" href="#" onclick="newfolder('<?php echo fm_enc(FM_PATH) ?>');return false;">
	      		<img src="<?php echo URL_TPL; ?>img/feather/folder-plus.svg" alt="New folder">
	      	</a>
	    <?php if (FM_USE_AUTH): ?>
	      	<a class="nav-link" title="Logout" href="?logout=1"><i class="icon-logout"></i></a>
      	<?php endif;?>
    </div>
  </div>
</nav>

<?php
}

/**
 * Show message from session
 */
function fm_show_message() {
	if (isset($_SESSION['message'])) {
		$class = isset($_SESSION['status']) ? $_SESSION['status'] : 'ok';
		echo '<p class="alert ' . $class . '">' . $_SESSION['message'] . '</p>';
		unset($_SESSION['message']);
		unset($_SESSION['status']);
	}
}

/**
 * Show page header
 */
function fm_show_header() {
	$sprites_ver = '20160315';
	header("Content-Type: text/html; charset=utf-8");
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
	header("Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0");
	header("Pragma: no-cache");
	?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>PHP File Manager</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
<link rel="stylesheet" href="../../dist/bootstrap-icons/font/bootstrap-icons.css">
<style>
.table-sm td.wbtngroup {
	padding-top: 2px;
	padding-bottom: 2px;
}
.wbtngroup>.btn-group-sm>.btn {
	padding: 0 0.25em;
    font-size: 1.25em;
}
.table thead th.wcheckbox {
	width:3%;
	vertical-align: top;
}
input[type=file] {
	padding: 3px;
	margin-bottom: 3px;

}
.preview-img{max-width:100%;background:url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAIAAACQkWg2AAAAKklEQVR42mL5//8/Azbw+PFjrOJMDCSCUQ3EABZc4S0rKzsaSvTTABBgAMyfCMsY4B9iAAAAAElFTkSuQmCC") repeat 0 0}
.preview-video{position:relative;max-width:100%;height:0;padding-bottom:62.5%;margin-bottom:10px}
.preview-video video{position:absolute;width:100%;height:100%;left:0;top:0;background:#000}
.btn > img{
	width: 16px;
	height: 16px;
}
i[class^='bi-'] {
	margin-right: 11px;
	margin-left: 3px;
}
.icon-folder{
	width: 22px;
	height: 22px;
	margin-right: 8px;
	vertical-align: bottom;
	display: inline-block;
	background-image: url(<?php echo URL_TPL; ?>img/fa-folder.svg); }
.icon-folder svg {
    color: #F7D774;
}
</style>
<link rel="icon" href="<?php echo FM_SELF_URL ?>?img=favicon" type="image/png">
<link rel="shortcut icon" href="<?php echo FM_SELF_URL ?>?img=favicon" type="image/png">
<?php if (isset($_GET['view']) && FM_USE_HIGHLIGHTJS): ?>
<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/highlight.js/9.2.0/styles/<?php echo FM_HIGHLIGHTJS_STYLE ?>.min.css">
<?php endif;?>
</head>
<body>
	<header class="bg-dark text-white py-3 mb-3 border-bottom">
			<div class="container-fluid d-grid align-items-center">
				<div class="d-flex align-items-center">
					<div class="d-flex align-items-center mb-2 mb-lg-0 text-white text-decoration-none me-auto">
						<img src="../../view/dist/y-dark.svg" alt="" width="40" height="40" class="me-2">
						<span class="fs-4 px-2">Administrador de archivos</span>
					</div>
					<a href="../" class="btn btn-outline-light text-end">Editor</a>
				</div>
			</div>
		</header>

<div class="container">
<?php
}

/**
 * Show page footer
 */
function fm_show_footer() {
	?>
</div>
<script>
function newfolder(p){var n=prompt('Nuevo nombre de carpeta','carpeta');if(n!==null&&n!==''){window.location.search='p='+encodeURIComponent(p)+'&new='+encodeURIComponent(n);}}
function rename(p,f){var n=prompt('Nuevo nombre',f);if(n!==null&&n!==''&&n!=f){window.location.search='p='+encodeURIComponent(p)+'&ren='+encodeURIComponent(f)+'&to='+encodeURIComponent(n);}}
function change_checkboxes(l,v){for(var i=l.length-1;i>=0;i--){l[i].checked=(typeof v==='boolean')?v:!l[i].checked;}}
function get_checkboxes(){var i=document.getElementsByName('file[]'),a=[];for(var j=i.length-1;j>=0;j--){if(i[j].type='checkbox'){a.push(i[j]);}}return a;}
function select_all(){var l=get_checkboxes();change_checkboxes(l,true);}
function unselect_all(){var l=get_checkboxes();change_checkboxes(l,false);}
function invert_all(){var l=get_checkboxes();change_checkboxes(l);}
function checkbox_toggle(){var l=get_checkboxes();l.push(this);change_checkboxes(l);}
</script>
<?php if (isset($_GET['view']) && FM_USE_HIGHLIGHTJS): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.2.0/highlight.min.js"></script>
<script>hljs.initHighlightingOnLoad();</script>
<?php endif;?>
</body>
</html>

<?php
}

/**
 * Show image
 * @param string $img
 */
function fm_show_image($img) {
	$modified_time = gmdate('D, d M Y 00:00:00') . ' GMT';
	$expires_time = gmdate('D, d M Y 00:00:00', strtotime('+1 day')) . ' GMT';

	$img = trim($img);
	$images = fm_get_images();
	$image = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAEElEQVR42mL4//8/A0CAAQAI/AL+26JNFgAAAABJRU5ErkJggg==';
	if (isset($images[$img])) {
		$image = $images[$img];
	}
	$image = base64_decode($image);
	if (function_exists('mb_strlen')) {
		$size = mb_strlen($image, '8bit');
	} else {
		$size = strlen($image);
	}

	if (function_exists('header_remove')) {
		header_remove('Cache-Control');
		header_remove('Pragma');
	} else {
		header('Cache-Control:');
		header('Pragma:');
	}

	header('Last-Modified: ' . $modified_time, true, 200);
	header('Expires: ' . $expires_time);
	header('Content-Length: ' . $size);
	header('Content-Type: image/png');
	echo $image;

	exit;
}

/**
 * Get base64-encoded images
 * @return array
 */
function fm_get_images() {
	return array(
		'favicon' => 'iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJ
bWFnZVJlYWR5ccllPAAAAZVJREFUeNqkk79Lw0AUx1+uidTQim4Waxfpnl1BcHMR6uLkIF0cpYOI
f4KbOFcRwbGTc0HQSVQQXCqlFIXgFkhIyvWS870LaaPYH9CDy8vdfb+fey930aSUMEvT6VHVzw8x
rKUX3N3Hj/8M+cZ6GcOtBPl6KY5iAA7KJzfVWrfbhUKhALZtQ6myDf1+X5nsuzjLUmUOnpa+v5r1
Z4ZDDfsLiwER45xDEATgOI6KntfDd091GidzC8vZ4vH1QQ09+4MSMAMWRREKPMhmsyr6voYmrnb2
PKEizdEabUaeFCDKCCHAdV0wTVNFznMgpVqGlZ2cipzHGtKSZwCIZJgJwxB38KHT6Sjx21V75Jcn
LXmGAKTRpGVZUx2dAqQzSEqw9kqwuGqONTufPrw37D8lQFxCvjgPXIixANLEGfwuQacMOC4kZz+q
GdhJS550BjpRCdCbAJCMJRkMASEIg+4Bxz4JwAwDSEueAYDLIM+QrOk6GHiRxjXSkJY8KUCvdXZ6
kbuvNx+mOcbN9taGBlpLAWf9nX8EGADoCfqkKWV/cgAAAABJRU5ErkJggg==',
		'sprites' => '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
RK5CYII=',
	);
}
