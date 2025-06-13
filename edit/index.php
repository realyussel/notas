<?php
require '../vendor/autoload.php';

// $handler = PhpConsole\Handler::getInstance();
// $handler->start(); // inicializar manejadores
// PhpConsole\Helper::register(); // registrará la clase global PC

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
$jotter       = new Jotter();
$errors       = [];
$isNote       = false;
$isConfigMode = false;
$isEditMode   = false;
$isDir        = false;
$appInstalled = file_exists(DIR_DATA . 'users.json');

// Función para codificar cada segmento del path
function safeEncodePath($path)
{
    $segments         = explode('/', $path);
    $encoded_segments = array_map('urlencode', $segments);
    return implode('/', $encoded_segments);
}

function alreadyExists($nbs, $usr, $name)
{
    return isset($nbs[urlencode($usr)][urlencode($name)]);
}

function sanitizeName($name)
{
    // Eliminar la extensión del archivo, si existe
    $dotPosition = strrpos($name, '.');
    if ($dotPosition !== false) {
        $nameWithoutExtension = substr($name, 0, $dotPosition);
    } else {
        $nameWithoutExtension = $name;
    }

    // Eliminar caracteres no válidos para nombres de archivos y carpetas, excepto espacios
    $sanitized = preg_replace('/[^a-zA-Z0-9_\- ñÑáÁéÉíÍóÓúÚüÜ]/u', '', $nameWithoutExtension);

    // Eliminar espacios en blanco al principio y al final del nombre
    $sanitized = trim($sanitized);

    // Reemplazar múltiples espacios intermedios por un solo espacio
    $sanitized = preg_replace('/\s+/', ' ', $sanitized);

    return $sanitized;
}

//check if user is logged in
$logger = new Login('jotter');
//user is trying to log in
if (! empty($_POST['submitLoginForm'])) {
    //install app and create first user
    if (! $appInstalled) {
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
} elseif (! empty($_GET['action']) && $_GET['action'] == 'logout') {
    $logger->logOut();
} else {
    $user = $logger->authUser();
}
// ajax calls

if (! empty($_GET['action']) && $_GET['action'] == 'ajax') {
    $data = false;
    if (ENV_CURRENT == ENV_DEMO) {
        $data = true;
    }

    // always return false if user is not authenticated

    if ($user['isLoggedIn'] && ENV_CURRENT != ENV_DEMO) {
        $option = isset($_GET['option']) ? $_GET['option'] : false;

        // NOTE: No uso urlencode para evitar una doble codificación

        $notebookName = isset($_GET['nb']) ? $_GET['nb'] : false;

        if ($option == 'moveItem') {

            $notebookName = $_GET['nb'];
            $notebooks    = $jotter->loadNotebooks();

            // Mover un elemento a otro directorio

            $safeNbPath = DIR_DATA . urlencode($user['login']) . '/' . urlencode($notebookName);
            $sourcePath = safeEncodePath($_GET['source']);
            $destPath   = safeEncodePath($_GET['destination']);

            if (! is_dir($safeNbPath . '/' . $destPath)) {
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
            || ! alreadyExists($notebooks, $user['login'], $notebookName)
            || ! file_exists($safeNbPath . '/' . $sourcePath)
            || ! file_exists($safeNbPath . '/' . $destPath);
            if (! $error) {
                $notebook = $jotter->loadNotebook($notebookName, $user['login']);

                # NOTE: Se envian los Paths codificados

                $error = ! $jotter->moveItem($sourcePath, $destPath);
            }
            $data = ! $error;

        } elseif ($option == 'save') {

            // Save current note

            $notebookName = $_GET['nb'];
            $notebook     = $jotter->loadNotebook($notebookName, $user['login']);

            if (isset($_POST['text'])) {
                if (ENV_CURRENT != ENV_DEMO) {
                    //save the note
                    $data = $jotter->setNote($_GET['item'], $_POST['text']);
                } else {
                    $data = true;
                }
            }
        } elseif ($option == 'preview') {
            $data = $jotter->loadNote($_GET['item'], true); // preview a markdown note in HTML
        }
    }
    header('Content-type: application/json');
    echo json_encode($data);
    exit;
//login form
} elseif (! $user['isLoggedIn']) {
    //display form as an installation process
    if (! $appInstalled) {
        $phpMinVersion   = '8.2';
        $phpIsMinVersion = (version_compare(PHP_VERSION, $phpMinVersion) >= 0);
        $isWritable      = is_writable(DIR_DATA) && is_writable(ROOT . 'cache/')
        || ! file_exists(DIR_DATA) && ! file_exists(ROOT . 'cache/')
        && is_writable(dirname(DIR_DATA));
    }
    include DIR_TPL . 'loginForm.tpl.php';
//notebook pages
} elseif (! empty($_GET['nb'])) {
    $itemPath     = '';
    $notebook     = false;
    $notebookName = sanitizeName($_GET['nb']);

    // load the complete list of notebooks

    $notebooks = $jotter->loadNotebooks();

    // only load notebook if it is owned by current user

    if (alreadyExists($notebooks, $user['login'], $notebookName)) {
        $notebook = $jotter->loadNotebook($notebookName, $user['login']);
    }

    // notebook wasn't loaded

    if ($notebook == false) {
        include DIR_TPL . 'error.tpl.php';
    } elseif (! empty($_GET['action']) && $_GET['action'] == 'edit' && empty($_GET['item'])) {

        if (isset($_POST['name'])) {
            $newNotebookName = sanitizeName($_POST['name']);
            $notebook        = [
                'name'             => $notebookName,
                'new_name'         => false,
                'user'             => $user['login'],
                'color'            => $_POST['color'],
                'site_name'        => $_POST['site_name'],
                'site_description' => $_POST['site_description'],
                'home_route'       => $_POST['home_route'],
                'password'         => $_POST['password'],
                'display_chapter'  => isset($_POST['display_chapter']),
                'display_index'    => isset($_POST['display_index']),
            ];

            $errors['emptyColor'] = empty($notebook['color']);

            if ($notebookName !== $newNotebookName) {
                // Rename
                $errors['empty'] = empty($newNotebookName);
                if (! empty($newNotebookName)) {
                    $notebook['new_name']    = $newNotebookName;
                    $errors['alreadyExists'] = alreadyExists($notebooks, $notebook['user'], $newNotebookName);
                }
            }

            if (! in_array(true, $errors)) {
                if (ENV_CURRENT != ENV_DEMO) {
                    $locName = $jotter->setNotebook($notebook);
                }
                header('Location: ' . URL . '?nb=' . $locName);
                exit;
            }
        }
        include DIR_TPL . 'notebookForm.tpl.php';
        // delete current notebook
    } elseif (! empty($_GET['action']) && $_GET['action'] == 'delete' && empty($_GET['item'])) {
        //confirmation was sent
        if (isset($_POST['delete'])) {
            if (ENV_CURRENT != ENV_DEMO) {
                $jotter->unsetNotebook($notebookName, $user['login']);
            }
            header('Location: ' . URL);
            exit;
        }
        include DIR_TPL . 'itemDelete.tpl.php';
    } elseif (! empty($_GET['action']) && ($_GET['action'] == 'adddir' || $_GET['action'] == 'addnote')) {

        // Agrega un subdirectorio o una apunte al directorio actual

        if (isset($_POST['name'])) {

            $name = sanitizeName($_POST['name']); // Nombre del item sin extensión
            if (! empty($_GET['item'])) {
                $pathParts = explode('/', $_GET['item']);             // Descomponer la ruta del item actual
                $lastPart  = array_pop($pathParts);                   // Extraer la última parte de la ruta
                $extension = pathinfo($lastPart, PATHINFO_EXTENSION); // Verificar si la última parte es un nombre de archivo con extensión

                if (empty($extension) && ! empty($lastPart)) {
                    $pathParts[] = $lastPart; // Si la última parte no tenía extensión, podría ser un directorio
                }

                $pathParts[] = $name;
                $path        = implode('/', $pathParts); // Unir las partes de la ruta con el nombre del item
            } else {
                $path = $name;
            }

            $item   = [];
            $plugin = '';
            if ($_GET['action'] == 'addnote') {
                $path .= '.md';
            } else {
                $pluin = '/index.md';
            }

            $item = ['name' => $path];

            $errors['empty']         = empty($name);
            $errors['alreadyExists'] = ! is_null(Utils::getArrayItem($notebook['tree'], safeEncodePath($path)));

            if (! in_array(true, $errors)) {
                if (ENV_CURRENT != ENV_DEMO) {
                    $jotter->setItem($item);
                }
                header('Location: ' . URL . '?nb=' . $notebookName . '&item=' . $path . $pluin);
                exit;
            }
        }
        include DIR_TPL . 'itemForm.tpl.php';
        // notebook item
    } elseif (! empty($_GET['item'])) {

        $itemPath = $_GET['item']; // NOTE: Es un path sin codificar; jotter usará: safeEncodePath
        $item     = $jotter->loadItem($itemPath);

        $isNote = substr($itemPath, -3) == '.md';
        $isDir  = ! $isNote;

        if (! empty($_GET['action']) && $_GET['action'] == 'edit') {

            //confirmation was sent

            if (isset($_POST['name'])) {
                $item = [
                    'name'      => $itemPath,
                    'directory' => $isDir,
                    'chapter'   => $_POST['chapter'],
                    'slug'      => $_POST['slug'],
                    'hidden'    => isset($_POST['hidden']),
                    'unindexed' => isset($_POST['unindexed']),
                ];

                $newName = sanitizeName($_POST['name']);
                $path    = (dirname($itemPath) != '.' ? dirname($itemPath) . '/' : '') . $newName;

                $plugin = '';
                if ($isNote) {
                    $path .= '.md';
                } else {
                    $pluin = '/index.md';
                }

                $errors['empty'] = empty($newName);

                $rename = ($itemPath != $path);
                if ($rename) {
                    $errors['alreadyExists'] = ! is_null(Utils::getArrayItem($notebook['tree'], safeEncodePath($path)));
                }

                if (! in_array(true, $errors)) {
                    if (ENV_CURRENT != ENV_DEMO) {
                        if ($rename) {
                            $item['new_name'] = $path;
                        }
                        $jotter->setItem($item);
                    }
                    header('Location: ' . URL . '?nb=' . $notebookName . '&item=' . $path . $pluin);
                    exit;
                }
            }
            include DIR_TPL . 'itemForm.tpl.php';
            // delete current item
        } elseif (! empty($_GET['action']) && $_GET['action'] == 'delete') {
            if (isset($_POST['delete'])) {
                if (ENV_CURRENT != ENV_DEMO) {
                    if ($jotter->unsetItem($itemPath, $isNote)) {
                        header('Location: ' . URL . '?nb=' . $notebookName . '&item=' . (($itemPath != '.') ? $itemPath : ''));
                        exit;
                    } else {
                        $errors['noSuch'] = 'No existe';
                    }
                }
            }
            include DIR_TPL . 'itemDelete.tpl.php';
        } else {
            // Show item

            $note       = $jotter->loadNote($itemPath); // Estamos ante una nota: cárgala
            $isEditMode = true;                         // Mostrar la barra de herramientas del editor
            include DIR_TPL . 'note.tpl.php';
        }
    } else {
        // Default: show notebook root

        include DIR_TPL . 'notebook.tpl.php';
    }
} elseif (! empty($_GET['action']) && $_GET['action'] == 'search') {
    include DIR_TPL . 'search.tpl.php';
} elseif (! empty($_GET['action']) && $_GET['action'] == 'config') {
    $isConfigMode = true;
    $users        = $logger->getUsers();
    $option       = isset($_GET['option']) ? $_GET['option'] : false;
    if ($option == 'myPassword') {
        if (isset($_POST['password'])) {
            $password                = htmlspecialchars(trim($_POST['password']));
            $errors['emptyPassword'] = (! isset($_POST['password']) || trim($_POST['password']) == "");
            if (! in_array(true, $errors)) {
                if (ENV_CURRENT != ENV_DEMO) {
                    //save password
                    $errors['save'] = ! $logger->setUser($user['login'], $password);
                }
                header('Location: ' . URL . '?action=config&option=myPassword');
                exit;
            }
        }
    } elseif ($option == 'addUser') {
        if (isset($_POST['login']) && isset($_POST['password'])) {
            $login                   = htmlspecialchars(trim($_POST['login']));
            $password                = htmlspecialchars(trim($_POST['password']));
            $errors['emptyLogin']    = $login == '';
            $errors['emptyPassword'] = $password == '';
            $errors['notAvailable']  = false;
            foreach ($users as $key => $value) {
                if ($value['login'] == $login) {
                    $errors['notAvailable'] = true;
                }
            }
            if (! in_array(true, $errors)) {
                if (ENV_CURRENT != ENV_DEMO) {
                    $logger->createUser($login, $password);
                }
                header('Location: ' . URL . '?action=config');
                exit;
            }
        }
    } elseif ($option == 'deleteUser') {
        $login    = htmlspecialchars(trim($_GET['user']));
        $password = null;
        if (isset($_POST['deleteUserSubmit'])) {
            //delete user's notebooks
            $notebooks = $jotter->loadNotebooks();
            $unb       = $notebooks[$user['login']]; // unb = User NoteBooks
            try {
                foreach ($unb as $key => $value) {
                    if ($value['user'] == $login && ENV_CURRENT != ENV_DEMO) {
                        $jotter->unsetNotebook($key);
                    }
                }
            } catch (TypeError $e) {
                echo '<script>console.log("' . $e . '");</script>';

            }
            //delete user
            $logger->deleteUser($login, $password);
            header('Location: ' . URL . '?action=config');
            exit;
        }
    }
    include DIR_TPL . 'config.tpl.php';
//markdown syntax page
} elseif (! empty($_GET['action']) && $_GET['action'] == 'add') {

    // Add a notebook

    if (isset($_POST['name'])) {

        $notebook = [
            'name' => sanitizeName($_POST['name']),
            'user' => $user['login'],
        ];

        $errors['empty']         = empty($notebook['name']);
        $errors['alreadyExists'] = alreadyExists($notebooks, $notebook['user'], $notebook['name']);

        if (! in_array(true, $errors)) {
            if (ENV_CURRENT != ENV_DEMO) {
                $locName = $jotter->setNotebook($notebook);
            }
            header('Location: ' . URL . '?nb=' . $locName);
            exit;
        }
    }
    include DIR_TPL . 'notebookForm.tpl.php';
//configuration page
} elseif (! empty($_GET['action']) && $_GET['action'] == 'markdown') {
    include DIR_TPL . 'markdown.tpl.php';
//file-manager
} elseif (! empty($_GET['file-manager'])) {
    include DIR_TPL . 'file-manager.tpl.php';
//homepage: notebooks list
} else {
    $notebooks = $jotter->loadNotebooks();
    include DIR_TPL . 'notebooks.tpl.php';
}
