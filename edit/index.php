<?php
require '../vendor/autoload.php';
$handler = PhpConsole\Handler::getInstance();
$handler->start(); // inicializar manejadores
PhpConsole\Helper::register(); // registrará la clase global PC
date_default_timezone_set('UTC');
setlocale(LC_ALL, 'en_US.UTF8');
error_reporting(0); // Desactivar toda notificación de error
set_time_limit(20);
define('VERSION', '2.0');
define('ROOT', __DIR__ . '/');
define('DIR_DATA', ROOT . 'data/');
define('DIR_TPL', ROOT . 'tpl/');
define('URL',
	(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http')
	. '://'
	. $_SERVER['HTTP_HOST']
	. rtrim(dirname($_SERVER['SCRIPT_NAME']), '/')
	. '/'
);
define('URL_TPL', URL . 'tpl/');
define('ENV_DEMO', 'demo');
define('ENV_DEV', 'dev');
define('ENV_PROD', 'prod');
define('ENV_CURRENT', ENV_DEV);
//display errors & warnings
if (ENV_CURRENT == ENV_DEV) {
	error_reporting(E_ALL | E_STRICT);
	ini_set('display_errors', 'On');
	ini_set('log_errors', 'On');
	ini_set('error_log', ROOT . 'errors.log');
} else {
	ini_set('display_errors', 'Off');
}
// external libraries
// https://github.com/michelf/php-markdown/
require_once ROOT . 'lib/ext/Markdown.php';
require_once ROOT . 'lib/ext/MarkdownExtra.php';
// https://github.com/nickcernis/html-to-markdown
require_once ROOT . 'lib/ext/HTML_To_Markdown.php';
// https://github.com/yosko/yoslogin/
require_once ROOT . 'lib/ext/yoslogin.lib.php';
//Jotter libraries
require_once ROOT . 'lib/utils.class.php';
require_once ROOT . 'lib/jotter.class.php';
require_once ROOT . 'lib/login.class.php';
$jotter = new Jotter();
$errors = array();
$isNote = false;
$isConfigMode = false;
$isEditMode = false;
$isWysiwyg = false;
$isDir = false;
$appInstalled = file_exists(DIR_DATA . 'users.json');
//check if user is logged in
$logger = new Login('jotter');
//user is trying to log in
if (!empty($_POST['submitLoginForm'])) {
	//install app and create first user
	if (!$appInstalled) {
		$logger->createUser(
			htmlspecialchars(trim($_POST['login'])),
			htmlspecialchars(trim($_POST['password']))
		);
	}
	$user = $logger->logIn(
		htmlspecialchars(trim($_POST['login'])),
		htmlspecialchars(trim($_POST['password'])),
		isset($_POST['remember'])
	);
//logging out
} elseif (!empty($_GET['action']) && $_GET['action'] == 'logout') {
	$logger->logOut();
} else {
	$user = $logger->authUser();
}
// ajax calls
if (!empty($_GET['action']) && $_GET['action'] == 'ajax') {
	$data = false;
	if (ENV_CURRENT == ENV_DEMO) {
		$data = true;
	}
	// always return false if user is not authenticated
	if ($user['isLoggedIn'] && ENV_CURRENT != ENV_DEMO) {
		$option = isset($_GET['option']) ? $_GET['option'] : false;
		$notebookName = isset($_GET['nb']) ? urlencode($_GET['nb']) : false;
		$itemPath = isset($_GET['item']) ? $_GET['item'] : false;
		//load the complete list of notebooks
		$notebooks = $jotter->loadNotebooks();
		$notebook = ($notebookName !== false) ? $jotter->loadNotebook($notebookName, $user['login']) : false;
		//move an item into another directory
		if ($option == 'moveItem') {
			$sourcePath = $_GET['source'];
			$destPath = $_GET['destination'];
			if (!is_dir(DIR_DATA . $user['login'] . '/' . $notebookName . '/' . $destPath)) {
				$destPath = dirname($destPath);
			}
			if ($sourcePath == '.') {$sourcePath = '';}
			if ($destPath == '.') {$destPath = '';}
			//TODO: check if source parent of destination
			//TODO: check if source & destination are "identical"
			//make sure the requested move is possible and safe
			$error = strpos($notebookName, '..') !== false
			|| strpos($sourcePath, '..') !== false
			|| strpos($destPath, '..') !== false
			|| !isset($notebooks[$user['login']][$notebookName])
			|| !file_exists(DIR_DATA . $user['login'] . '/' . $notebookName . '/' . $sourcePath)
			|| !file_exists(DIR_DATA . $user['login'] . '/' . $notebookName . '/' . $destPath);
			if (!$error) {
				$notebook = $jotter->loadNotebook($notebookName, $user['login']);
				$error = !$jotter->moveItem($sourcePath, $destPath);
			}
			$data = !$error;
			// save current note
		} elseif ($option == 'save') {
			//only load notebook if it is owned by current user
			if (isset($notebooks[$user['login']][$notebookName])) {
				$itemData = Utils::getArrayItem($notebook['tree'], $itemPath);
				$isNote = $itemData === true;
				if ($isNote && isset($_POST['text'])) {
					if (ENV_CURRENT != ENV_DEMO) {
						//save the note
						$data = $jotter->setNoteText($itemPath, $_POST['text']);
					} else {
						$data = true;
					}
				}
			}
			// preview a markdown note in HTML
		} elseif ($option == 'preview') {
			$data = $jotter->loadNote($itemPath, true);
		}
	}
	header('Content-type: application/json');
	echo json_encode($data);
	exit;
//login form
} elseif (!$user['isLoggedIn']) {
	//display form as an installation process
	if (!$appInstalled) {
		$phpMinVersion = '5.3';
		$phpIsMinVersion = (version_compare(PHP_VERSION, $phpMinVersion) >= 0);
		$isWritable = is_writable(DIR_DATA) && is_writable(ROOT . 'cache/')
		|| !file_exists(DIR_DATA) && !file_exists(ROOT . 'cache/')
		&& is_writable(dirname(DIR_DATA));
	}
	include DIR_TPL . 'loginForm.tpl.php';
//notebook pages
} elseif (!empty($_GET['nb'])) {
	$itemPath = '';
	$notebook = false;
	$notebookName = urlencode($_GET['nb']);
	//load the complete list of notebooks
	$notebooks = $jotter->loadNotebooks();
	//only load notebook if it is owned by current user
	if (isset($notebooks[$user['login']][$notebookName])) {
		$notebook = $jotter->loadNotebook($notebookName, $user['login']);
	}
	// notebook wasn't loaded
	if ($notebook == false) {
		include DIR_TPL . 'error.tpl.php';
		// rename current notebook
	} elseif (!empty($_GET['action']) && $_GET['action'] == 'edit' && empty($_GET['item'])) {
		// d('edit notebook'); // Función d?
		if (isset($_POST['name'])) {
			$notebook = array(
				'name' => urlencode($_POST['name']),
				'user' => $user['login'],
				'editor' => (isset($_POST['editor']) && $_POST['editor'] == 'wysiwyg') ? $_POST['editor'] : 'markdown',
				'safe' => isset($_POST['safe-wysiwyg']),
				'site_name' => $_POST['site_name'],
				'site_description' => $_POST['site_description'],
				'home_route' => $_POST['home_route'],
				'password' => $_POST['password'],
				'public_view' => isset($_POST['public_view']),
				'display_chapter' => isset($_POST['display_chapter']),
				'display_index' => isset($_POST['display_index']),
				'color' => $_POST['color'],
			);
			// $errors['empty'] = empty($notebook['name']);
			// $errors['alreadyExists'] = isset($notebooks[$user['login']][$notebook['name']]);
			if (!in_array(true, $errors)) {
				if (ENV_CURRENT != ENV_DEMO) {
					$notebooks = $jotter->setNotebook($notebook['name'], $notebook['user'], $notebook['editor'], $notebook['safe'], $notebook['color'], $notebook['site_name'], $notebook['site_description'], $notebook['home_route'], $notebook['password'], $notebook['public_view'], $notebook['display_chapter'], $notebook['display_index']);
				}
				header('Location: ' . URL . '?nb=' . $notebookName);
				exit;
			}
		}
		include DIR_TPL . 'notebookForm.tpl.php';
		// delete current notebook
	} elseif (!empty($_GET['action']) && $_GET['action'] == 'delete' && empty($_GET['item'])) {
		//confirmation was sent
		if (isset($_POST['delete'])) {
			if (ENV_CURRENT != ENV_DEMO) {
				$jotter->unsetNotebook($notebookName, $user['login']);
			}
			header('Location: ' . URL);
			exit;
		}
		include DIR_TPL . 'itemDelete.tpl.php';
		// add a subdirectory or a note to the current directory
	} elseif (!empty($_GET['action']) && ($_GET['action'] == 'adddir' || $_GET['action'] == 'addnote')) {
		if (isset($_POST['name'])) {
			$item['name'] = $_POST['name'];
			$path = $item['name'];
			if (!empty($_GET['item'])) {
				if (!is_dir(DIR_DATA . $user['login'] . '/' . $notebookName . '/' . $_GET['item'])) {
					if (dirname($_GET['item']) != '.') {
						$path = dirname($_GET['item']) . '/' . $path;
					}
				} else {
					if (!empty($_GET['item'])) {
						$path = $_GET['item'] . '/' . $path;
					}
				}
			}
			$errors['empty'] = empty($item['name']);
			$errors['alreadyExists'] = !is_null(Utils::getArrayItem($notebook['tree'], $path));
			if (!in_array(true, $errors)) {
				if (ENV_CURRENT != ENV_DEMO) {
					if ($_GET['action'] == 'addnote') {
						$path .= '.md';
						$jotter->setNote($path);
					} else {
						$jotter->setDirectory($path);
					}
				}
				header('Location: ' . URL . '?nb=' . $notebookName . '&item=' . $path);
				exit;
			}
		}
		include DIR_TPL . 'itemForm.tpl.php';
		// notebook item
	} elseif (!empty($_GET['item']) && strpos($itemPath, '..') === false) {
		$itemPath = $_GET['item'];
		$itemData = Utils::getArrayItem($notebook['tree'], $itemPath);
		$isNote = $itemData === true;
		if (!$isNote) {
			$dirPath = DIR_DATA . $user['login'] . '/' . $notebookName . '/' . $itemPath;
			$isDir = file_exists($dirPath) && is_dir($dirPath);
		}
		//item not found: show notebook root
		if (!$isDir && !$isNote) {
			include DIR_TPL . 'notebook.tpl.php';
			// rename current item
		} elseif (!empty($_GET['action']) && $_GET['action'] == 'edit') {
			//confirmation was sent
			if (isset($_POST['name'])) {
				$item['name'] = $_POST['name'];
				$path = $item['name'];
				$path = (dirname($itemPath) != '.' ? dirname($itemPath) . '/' : '') . $path;
				$errors['empty'] = empty($item['name']);
				$errors['sameName'] = $itemPath == $path . '.md';
				$errors['alreadyExists'] = !is_null(Utils::getArrayItem($notebook['tree'], $path));
				if (!in_array(true, $errors)) {
					if (ENV_CURRENT != ENV_DEMO) {
						if ($isNote) {
							$path .= '.md';
							$item['name'] .= '.md';
							$jotter->setNote($itemPath, $item['name']);
						} elseif ($isDir) {
							$jotter->setDirectory($itemPath, $item['name']);
						}
					}
					header('Location: ' . URL . '?nb=' . $notebookName . '&item=' . $path);
					exit;
				}
			}
			include DIR_TPL . 'itemForm.tpl.php';
			// delete current item
		} elseif (!empty($_GET['action']) && $_GET['action'] == 'delete') {
			//confirmation was sent
			if (isset($_POST['delete'])) {
				if (ENV_CURRENT != ENV_DEMO) {
					if ($isNote) {
						$jotter->unsetNote($itemPath);
					} elseif ($isDir) {
						$jotter->unsetDirectory($itemPath);
					}
				}
				header('Location: ' . URL . '?nb=' . $notebookName . '&item=' . (dirname($itemPath) != '.' ? dirname($itemPath) : ''));
				exit;
			}
			include DIR_TPL . 'itemDelete.tpl.php';
			//show item
		} else {
			if ($isNote) {
				//we are dealing with a note: load it
				$note = $jotter->loadNote($_GET['item']);
				// show editor toolbar
				$isEditMode = true;
				$isWysiwyg = !isset($notebook['editor']) || $notebook['editor'] == 'wysiwyg';
				include DIR_TPL . 'note.tpl.php';
			} elseif ($isDir) {
				//for a directory, just show the notebook's "hompage"
				include DIR_TPL . 'notebook.tpl.php';
			}
		}
		//default: show notebook root
	} else {
		include DIR_TPL . 'notebook.tpl.php';
	}
} elseif (!empty($_GET['action']) && $_GET['action'] == 'search') {
	include DIR_TPL . 'search.tpl.php';
} elseif (!empty($_GET['action']) && $_GET['action'] == 'config') {
	$isConfigMode = true;
	$users = $logger->getUsers();
	$option = isset($_GET['option']) ? $_GET['option'] : false;
	if ($option == 'myPassword') {
		if (isset($_POST['password'])) {
			$password = htmlspecialchars(trim($_POST['password']));
			$errors['emptyPassword'] = (!isset($_POST['password']) || trim($_POST['password']) == "");
			if (!in_array(true, $errors)) {
				if (ENV_CURRENT != ENV_DEMO) {
					//save password
					$errors['save'] = !$logger->setUser($user['login'], $password);
				}
				header('Location: ' . URL . '?action=config&option=myPassword');
				exit;
			}
		}
	} elseif ($option == 'addUser') {
		if (isset($_POST['login']) && isset($_POST['password'])) {
			$login = htmlspecialchars(trim($_POST['login']));
			$password = htmlspecialchars(trim($_POST['password']));
			$errors['emptyLogin'] = $login == '';
			$errors['emptyPassword'] = $password == '';
			$errors['notAvailable'] = false;
			foreach ($users as $key => $value) {
				if ($value['login'] == $login) {
					$errors['notAvailable'] = true;
				}
			}
			if (!in_array(true, $errors)) {
				if (ENV_CURRENT != ENV_DEMO) {
					$logger->createUser($login, $password);
				}
				header('Location: ' . URL . '?action=config');
				exit;
			}
		}
	} elseif ($option == 'deleteUser') {
		$login = htmlspecialchars(trim($_GET['user']));
		$password = null;
		if (isset($_POST['deleteUserSubmit'])) {
			//delete user's notebooks
			$notebooks = $jotter->loadNotebooks();
			foreach ($notebooks[$user['login']] as $key => $value) {
				if ($value['user'] == $login && ENV_CURRENT != ENV_DEMO) {
					$jotter->unsetNotebook($key);
				}
			}
			//delete user
			$logger->deleteUser($login, $password);
			header('Location: ' . URL . '?action=config');
			exit;
		}
	}
	include DIR_TPL . 'config.tpl.php';
//markdown syntax page
} elseif (!empty($_GET['action']) && $_GET['action'] == 'add') {
	//add a notebook
	// user wants to make a new notebook
	if (isset($_POST['name'])) {
		$notebook = array(
			'name' => urlencode($_POST['name']),
			'user' => $user['login'],
			'editor' => (isset($_POST['editor']) && $_POST['editor'] == 'wysiwyg') ? $_POST['editor'] : 'markdown',
			'safe' => isset($_POST['safe-wysiwyg']),
			'color' => '#0066CC',
		);
		$errors['empty'] = empty($notebook['name']);
		$errors['alreadyExists'] = isset($notebooks[$user['login']][$notebook['name']]);
		if (!in_array(true, $errors)) {
			if (ENV_CURRENT != ENV_DEMO) {
				$notebooks = $jotter->setNotebook($notebook['name'], $notebook['user'], $notebook['editor'], $notebook['safe'], $notebook['color']);
			}
			header('Location: ' . URL . '?nb=' . $notebook['name']);
			exit;
		}
	}
	include DIR_TPL . 'notebookForm.tpl.php';
//configuration page
} elseif (!empty($_GET['action']) && $_GET['action'] == 'markdown') {
	include DIR_TPL . 'markdown.tpl.php';
//file-manager
} elseif (!empty($_GET['file-manager'])) {
	include DIR_TPL . 'file-manager.tpl.php';
//homepage: notebooks list
} else {
	$notebooks = $jotter->loadNotebooks();
	include DIR_TPL . 'notebooks.tpl.php';
}
?>