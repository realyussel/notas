<?php
require_once '../vendor/autoload.php';

date_default_timezone_set('UTC');
setlocale(LC_ALL, 'en_US.UTF-8');
error_reporting(0); // Desactivar toda notificación de error
set_time_limit(20);
class Deepwiki {
	const AUTH_LOGGED_IN = 11;
	const AUTH_NOT_LOGGED_IN = 12;
	const AUTH_WRONG_PASSWORD = 13;
	private $request;
	private $response;
	private $template;
	private $config = array();
	private $authenticated;
	private $docs_items = array();
	private $queried_docs;
	private $first_route; // En caso de que no haya 'home_route'
	public function __construct() {

		// Environment

		define('SITE_URI', '/' . trim(dirname($_SERVER['PHP_SELF']), '/'));
		define('APP_ROOT', __DIR__);
		$offset = strlen(SITE_URI) - strpos(SITE_URI, '/', '1');
		define('PARENT_ROOT', substr(APP_ROOT, 0, -$offset));
		define('CONFIG_ROOT', PARENT_ROOT . '/edit/data'); // Módulo "Editor"

		// DOCS_ROOT se define en función de GET

		if (isset($_GET["usr"]) && isset($_GET["nb"])) {
			// Obtener los valores de 'usr' y 'nb' de $_GET
			$usr = $_GET["usr"];
			$nb = $_GET["nb"];

			// Aplicar rawurlencode a nb y definir la constante BASE_ROOT
			define('BASE_ROOT', '/' . $usr . '/' . $nb);
			define('DOCS_ROOT', CONFIG_ROOT . BASE_ROOT);
		}

		define('THEMES_ROOT', APP_ROOT . '/deepwiki-themes');
		define('THEMES_ROOT_URI', rtrim(SITE_URI, '/') . '/deepwiki-themes');

		require 'dist/Parsedown.1.7.4.php';
		require 'dist/ParsedownExtra.0.8.1.php';
		require 'dist/ParsedownExtended.php';
		$this->loadConfig();
		// template instance
		$this->template = new DeepwikiTemplate($this->config['theme']);
		// constants based on configuration
		define('ASSETS_ROOT_URI', trim($this->config['assets_path'], '/'));
		$this->loadDocs();
	}

	public function handle(DeepwikiRequest $request) {
		// request instance
		$this->request = $request;
		// response instance
		$this->response = new DeepwikiResponse;
		if ($this->authenticate()) {
			$this->compileDocs();
			$this->handleRequest();
		}
		$this->fillTemplate();
		$this->response->setBody($this->template->compile());
		return $this->response;
	}
	public function terminate() {
		exit();
	}

	private function generateCookieSalt($length = 32) {
		// Generar una cadena aleatoria usando openssl_random_pseudo_bytes
		$random_bytes = openssl_random_pseudo_bytes($length);
		// Convertir los bytes aleatorios a una cadena hexadecimal
		$salt = bin2hex($random_bytes);
		// Asegurarse de que la longitud del resultado sea la deseada
		return substr($salt, 0, $length);
	}

	private function loadConfig() {
		$safeDocsRoot = $this->safeEncodePath(DOCS_ROOT);
		$config_fullpath = $safeDocsRoot . '/notebook.json';

		if (file_exists($config_fullpath)) {
			$config_json = file_get_contents($config_fullpath);
			$this->config = json_decode($config_json, true);
		} else {
			// Error: 404
		}
		if (!is_array($this->config)) {
			$this->config = array();
			// Error
		}
		// fill defaults
		$this->config = array_merge(array(
			'copyright_link' => PARENT_ROOT,
			'copyright' => $_GET['usr'],
			'theme' => 'yussel',
			'assets_path' => '../edit/data/assets/',
			'footer_code' => '',
			'cookie_salt' => '738b3d3029832b2fdad101185c7154bb',
		), $this->config);
	}

	private function normalizeString($string) {
		return \Normalizer::normalize($string, \Normalizer::FORM_C);
	}

	// Definir la función de reducción dentro de safeEncodePath
	private function reduce_path($carry, $segment) {
		return $carry . '/' . $segment;
	}

	// Función para codificar cada segmento del path
	public function safeEncodePath($path) {

		// Verificar si la ruta comienza con una barra
		$has_leading_slash = (substr($path, 0, 1) === '/');
		if ($has_leading_slash) {
			$path = ltrim($path, '/');
		}

		// Dividir la ruta en segmentos
		$segments = explode('/', $path);

		// Codificar cada segmento
		$encoded_segments = array_map('urlencode', $segments);

		// Reunir los segmentos codificados
		$encoded_path = array_reduce($encoded_segments, [$this, 'reduce_path'], '');

		// Volver a agregar la barra inicial si fue eliminada
		if ($has_leading_slash) {
			$encoded_path = '/' . ltrim($encoded_path, '/');
		}

		return $encoded_path;
	}

	private function safeDecodePath($path) {
		$segments = explode('/', $path);
		$decoded_segments = array_map('urldecode', $segments);
		return implode('/', $decoded_segments);
	}

	// Función recursiva para recorrer el árbol de documentos
	private function walkDocsTree($tree, &$items, $parent = 0, &$idCounter = 1, $depth = 1) {
		foreach ($tree as $key => $item) {
			// Ignorar elementos cuyo valor no sea un array
			if (!is_array($item)) {
				continue;
			}

			// Determinar si es un archivo o un directorio basado en la clave (si contiene una extensión es un archivo)
			$isDir = !strpos($key, '.');
			$id = $idCounter++; // Asignar un ID consecutivo

			$filename = $this->safeDecodePath($key);

			$chapter = isset($item['chapter']) ? $item['chapter'] : '';
			$slug = isset($item['slug']) ? $item['slug'] : '';
			$hidden = isset($item['hidden']) ? (bool) $item['hidden'] : false;
			$unindexed = isset($item['unindexed']) ? (bool) $item['unindexed'] : false;

			if (!$isDir) {
				// Es un archivo
				$title = pathinfo($filename, PATHINFO_FILENAME);
				$type = 'file';

				// Agregar el item al array
				$items[] = compact('id', 'filename', 'title', 'type', 'depth', 'parent', 'chapter', 'slug', 'hidden', 'unindexed');
			} else {
				// Es un directorio
				$title = $filename;
				$type = 'directory';

				// Agregar el item al array
				$items[] = compact('id', 'filename', 'title', 'type', 'depth', 'parent', 'chapter', 'slug', 'hidden', 'unindexed');

				// Recursividad para los elementos hijos
				$this->walkDocsTree($item, $items, $id, $idCounter, $depth + 1);
			}
		}
	}

	public function generateDocsItems() {
		// Inicializar el array de items
		$docsItems = [];
		$idCounter = 1; // Inicializar el contador de IDs

		// Llamar a la función recursiva para iniciar el recorrido del árbol
		if (isset($this->config['tree'])) {
			$this->walkDocsTree($this->config['tree'], $docsItems);
		}

		return $docsItems;
	}

	public function buildAbsolutePaths(&$docsItems) {
		// Crear un mapa para encontrar los elementos por su ID más rápidamente
		$idMap = [];
		foreach ($docsItems as $item) {
			$idMap[$item['id']] = $item;
		}

		foreach ($docsItems as &$item) {
			// Usar slug si está disponible, de lo contrario usar filename
			$path = (!empty($item['slug']) ? $item['slug'] : $item['filename']);
			$real_path = $item['filename'];
			$currentPos = $item['parent'];

			// Construir la ruta desde el elemento actual hacia la raíz
			while ($currentPos != 0) {
				if (isset($idMap[$currentPos])) {
					$parentItem = $idMap[$currentPos];

					$path = (!empty($parentItem['slug']) ? $parentItem['slug'] : $parentItem['filename']) . '/' . $path;
					$real_path = $parentItem['filename'] . '/' . $real_path;
					$currentPos = $parentItem['parent'];
				} else {
					break;
				}
			}

			$item['path'] = trim($path, '/');
			$item['real_path'] = trim($real_path, '/');
		}
	}

	// Función recursiva para asignar capítulos
	private function assignChapters(&$elements, $parent_id = 0, $parent_chapter = '') {
		// Filtrar y sacar los elementos por el padre actual
		$children = [];
		foreach ($elements as $key => &$elemento) {
			if ($elemento['parent'] == $parent_id) {
				$children[] = &$elemento;
				unset($elements[$key]);
			}
		}

		// Ordenar los hijos por capítulo (si existe) y luego por título
		usort($children, function ($a, $b) {
			// Para que los elementos sin capítulo aparezcan al final del arreglo
			// Asignar PHP_INT_MAX si el capítulo es null o una cadena vacía
			$chapterA = ($a['chapter'] === null || $a['chapter'] === '') ? PHP_INT_MAX : $a['chapter'];
			$chapterB = ($b['chapter'] === null || $b['chapter'] === '') ? PHP_INT_MAX : $b['chapter'];
			if ($chapterA == $chapterB) {
				return $a['title'] <=> $b['title'];
			}
			return $chapterA <=> $chapterB;
		});

		// Asignar capítulos consecutivos sin sobreescribir los números existentes
		$chapter_number = 1;
		foreach ($children as &$elemento) {
			if (empty($elemento['chapter'])) {
				$elemento['chapter'] = $parent_chapter ? $parent_chapter . '.' . $chapter_number : strval($chapter_number);
			} else {
				// Asegurar que el capítulo tenga el prefijo del padre
				if ($parent_chapter) {
					$elemento['chapter'] = $parent_chapter . '.' . $elemento['chapter'];
				}
			}
			$chapter_number++;

			// Llamada recursiva para procesar los hijos de este elemento
			$this->assignChapters($elements, $elemento['id'], $elemento['chapter']);
		}

		// Volver a agregar los hijos procesados a la lista principal
		foreach ($children as $child) {
			$elements[] = $child;
		}
	}

	public function buildChapters(&$elements) {
		// Ordenar elementos por profundidad de menor a mayor
		usort($elements, function ($a, $b) {
			return $a['depth'] <=> $b['depth'];
		});
		// Comenzar la asignación de capítulos desde la raíz (parent_id = 0)
		$this->assignChapters($elements);
	}

	private function compareChapters($a, $b) {
		$aParts = preg_split('/([0-9]+)/', $a['chapter'], -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
		$bParts = preg_split('/([0-9]+)/', $b['chapter'], -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
		foreach ($aParts as $index => $part) {
			if (!isset($bParts[$index])) {
				return 1;
			}
			if (is_numeric($part) && is_numeric($bParts[$index])) {
				if ((int) $part > (int) $bParts[$index]) {
					return 1;
				} elseif ((int) $part < (int) $bParts[$index]) {
					return -1;
				}
			} else {
				$result = strcmp($part, $bParts[$index]);
				if ($result !== 0) {
					return $result;
				}
			}
		}
		return (count($aParts) < count($bParts)) ? -1 : 0;
	}

	public function sortDocsByChapter(&$docsItems) {
		usort($docsItems, [$this, 'compareChapters']);
	}

	private function loadDocs() {
		$this->docs_items = $this->generateDocsItems();
		$this->buildAbsolutePaths($this->docs_items);
		$this->buildChapters($this->docs_items);
		$this->sortDocsByChapter($this->docs_items);
	}

	private function authenticate() {
		$this->authenticated = self::AUTH_NOT_LOGGED_IN;
		if (!empty($this->config['password'])) {

			if (array_key_exists('logging', $_COOKIE)) {
				// has logging cookie
				$cookie_hash = $_COOKIE['logging'];
				if ($cookie_hash === $this->getAuthHash()) {
					$this->authenticated = self::AUTH_LOGGED_IN;
				}
			} elseif (array_key_exists('password', $_POST) && !empty($_POST['password'])) {
				// post password
				if ($this->config['password'] === $_POST['password']) {
					$this->processLogin();
					$this->authenticated = self::AUTH_LOGGED_IN;
				} else {
					$this->authenticated = self::AUTH_WRONG_PASSWORD;
				}
			}

			// show logging form

			if (self::AUTH_LOGGED_IN !== $this->authenticated) {
				$this->processLogout(); // Terminar cualquier sesión abierta
				$this->template->path = 'login.html';
				// wrong password
				if (self::AUTH_WRONG_PASSWORD === $this->authenticated) {
					$this->template->setPart('login_form', '<div class="alert alert-danger" role="alert">Contraseña incorrecta.</div>' . $this->template->getPart('login_form'));
				}
				return false;
			}
		}
		return true;
	}

	private function findElementById($id) {

		$ids = array_column($this->docs_items, 'id');
		$index = array_search($id, $ids);

		if ($index !== false) {
			return $this->docs_items[$index];
		}
		return null; // Error
	}

	private function isHiddenOrParentHidden($id) {
		$currentElement = $this->findElementById($id);
		if ($currentElement['hidden']) {
			return true;
		}
		// Si el elemento tiene un padre, comprobar el padre
		if ($currentElement['parent'] != 0) {
			return $this->isHiddenOrParentHidden($currentElement['parent']);
		}
		return false;
	}

	// Función para normalizar texto a NFC
	private function normalizeText($text) {
		return Normalizer::normalize($text, Normalizer::FORM_C);
	}

	private function getFileContents($filename) {
		try {
			/*
				// Verificar si el archivo existe y es legible
				if (!file_exists($filename)) {
					echo "File does not exist: $filename";
					return null;
				}

				if (!is_readable($filename)) {
					echo "File does not readable: $filename";
					return null;
				}
			*/

			// Intentar obtener el contenido del archivo
			$content = @file_get_contents($filename);

			// Verificar si se produjo un error
			if ($content === false) {
				// Obtener el último error
				$error = error_get_last();
				throw new Exception("Error reading file: " . $error['message']);
			}

			return $content;
		} catch (Exception $e) {
			// Manejar la excepción
			echo "Caught exception: " . $e->getMessage();
			return null;
		}
	}

	private function compileDocs() {
		$query = $this->safeDecodePath($this->request->query[0]);
		$paths = array_column($this->docs_items, 'path'); // Obtener una columna de los paths

		// Normalizar la consulta y los caminos
		$normalizedQuery = $this->normalizeString($query);
		$normalizedPaths = array_map([$this, 'normalizeString'], $paths);

		$index = array_search($normalizedQuery, $normalizedPaths); // Buscar el índice del path deseado

		if ($index !== false) {

			$entry = $this->docs_items[$index]; // Encontrado

			if (!$this->isHiddenOrParentHidden($entry['id'])) {

				$file = $this->safeEncodePath(DOCS_ROOT) . '/' . $this->safeEncodePath($entry['real_path']);

				if ($this->docFileType($entry['real_path']) === false) {
					$file .= '/index.md';
				}

				$origin = $this->getFileContents($file);
				if ($origin !== null) {

					$Parsedown = new ParsedownExtended([
						"task" => true,
						"kbd" => true,
						'mark' => true,
						'images' => true,
						"math" => [
							"single_dollar" => true,
						],
						"insert" => false,
						"thematic_breaks" => true,
						"smartTypography" => false,
						"scripts" => true,
						"emojis" => true,
						"diagrams" => true,
						'sup' => true,
						'sub' => true,
						'tables' => [
							"tablespan" => true,
						],
						"headings" => [
							"lowercase" => true,
							"auto_anchors" => true,
						],
						"toc" => [
							"transliterate" => true,
						],
						"smarty" => true,
						"emphasis" => true,
						"typographer" => true,
					]);
					$Parsedown->setSafeMode(false);
// $Parsedown->setMarkupEscaped(false); // Si desea escapar de HTML
// $Parsedown->setUrlsLinked(false); // Si desea habilitar/deshabilitar el enlace automático (default: true)

					$content = $Parsedown->text($origin);

					// Reemplazar el enlace interno de la página

// NOTE: La expresión regular encuentra: href="#/path/to/resource"
// con 'href' como primer grupo capturado
// y 'path/to/resource' como segundo grupo capturado.

					$matches = array();
					preg_match_all('#\ (href|src)="\#\/([^\"]+)"#ui', $content, $matches);

					if (!empty($matches[0])) {
						foreach ($matches[0] as $i => $match) {
							// Construir la nueva URI utilizando la función uri
							$new_uri = $this->uri($matches[2][$i]);
							// Reemplazar en el contenido original
							$content = str_replace($matches[0][$i], ' ' . $matches[1][$i] . '="' . $new_uri . '"', $content);
						}
					}

					// Reemplazar URL de activos asset

					$matches = array();
					preg_match_all('#\ (href|src)="\!\/([^\"]+)"#ui', $content, $matches);
					if ($matches[0]) {
						foreach (array_keys($matches[0]) as $i) {
							$content = str_replace($matches[0][$i], ' ' . $matches[1][$i] . '="' . $this->assetUri($matches[2][$i]) . '"', $content);
						}
					}

					// Integrar propiedades de etiqueta

					$matches = array();
					preg_match_all('#\ \/>\{([^\}]+?)\}#', $content, $matches);
					if ($matches[0]) {
						foreach (array_keys($matches[0]) as $i) {
							$element = sprintf(' %s />',
								$matches[1][$i]
							);
							$element = str_replace('&quot;', '"', $element);
							$content = str_replace($matches[0][$i], $element, $content);
						}
					}

					$matches = array();
					preg_match_all('#>([^\>]*?)<\/([a-zA-Z]+)>\{([^\}]+?)\}#', $content, $matches);
					if ($matches[0]) {
						foreach (array_keys($matches[0]) as $i) {
							$element = sprintf(' %s>%s</%s>',
								$matches[3][$i],
								$matches[1][$i], // plain text, no tags
								$matches[2][$i]
							);
							$element = str_replace('&quot;', '"', $element);
							$content = str_replace($matches[0][$i], $element, $content);
						}
					}

					$this->queried_docs = array(
						'title' => $entry['title'],
						'slug' => $entry['slug'],
						'chapter' => $entry['chapter'],
						'filename' => $entry['filename'],
						'content' => $content,
					);
				}
			}
		}
	}
	private function handleRequest() {
		if (empty($this->request->query[0]) && !empty($this->request->query[1]) && !empty($this->request->query[2])) {
			$this->goHome();
			return false;
		}
		if (empty($this->config)) {
			$this->template->path = '404.html';
			$this->response->setStatus(404);
			return false;
		}
		if ('_logout' === $this->request->query[0]) {
			$this->processLogout();
			$this->goHome();
			return false;
		}
		if (!$this->queried_docs) {
			$this->template->path = '404.html';
			$this->response->setStatus(404);
			return false;
		}
		if (!$this->queried_docs && '_403' === $this->request->query[0]) {
			$this->template->path = '403.html';
			$this->response->setStatus(403);
			return false;
		}
	}
	private function fillTemplate() {
		$part_doc_index = array();
		if ($this->request->query[3] !== 'file') {
			$part_nav = array();

			// Generate navigation menu

			$part_nav[] = '<ul class="list-unstyled pb-1" id="primary-docs-nav">';
			$top_level_elements = array();
			$children_elements = array();
			foreach ($this->docs_items as $entry) {
				if (empty($entry['parent'])) {
					$top_level_elements[] = $entry;
				} else {
					$children_elements[$entry['parent']][] = $entry;
				}
			}
			$output_nav = '';
			$submenu_number = 1;
			foreach ($top_level_elements as $entry) {
				$this->_display_nav_item($entry, $children_elements, $output_nav, $submenu_number);
			}
			$part_nav[] = $output_nav;
			$part_nav[] = '</ul>';
			$this->template->setPart('nav', implode(PHP_EOL, $part_nav));
		}
		// generate outline

		if ($this->config['display_index']) {
			$matches = array();
			preg_match_all('#\<h([1-6]) id=\"([^\"]+)\"\>([^\<]+)\<\/h([1-6])\>#ui', $this->queried_docs['content'], $matches);
			if (count($matches[0])) {
				$headings = array();
				foreach (array_keys($matches[0]) as $k) {
					$headings[] = array(
						'level' => intval($matches[1][$k]),
						'anchor' => $matches[2][$k],
						'title' => $matches[3][$k],
					);
				}
				$heading_index = array();
				$last_level = 0;
				$unclosed = 0;
				foreach ($headings as $entry) {
					if ($entry['level'] > $last_level) {
						$heading_index[] = '<ul>';
						$unclosed++;
						$last_level = $entry['level'];
						$heading_index[] = '<li><a href="#' . $entry['anchor'] . '">' . $entry['title'] . '</a>';
					} elseif ($entry['level'] < $last_level) {
						if ($unclosed > 0) {
							$heading_index[] = '</li>' . str_repeat('</ul>', $last_level - $entry['level']);
							$unclosed = $unclosed - ($last_level - $entry['level']);
						}
						$last_level = $entry['level'];
						$heading_index[] = '</li>' . '<li><a href="#' . $entry['anchor'] . '">' . $entry['title'] . '</a>';
					} else {
						$heading_index[] = '</li>' . '<li><a href="#' . $entry['anchor'] . '">' . $entry['title'] . '</a>';
					}
				}
				if ($unclosed > 0) {
					$heading_index[] = '</li>' . str_repeat('</ul>', $unclosed);
					$unclosed = 0;
				}
				$heading_index = implode(null, $heading_index);
				// only display index tree when contains more than two entrys
				// solo muestra el árbol de índice cuando contiene más de dos entradas
				if (substr_count($heading_index, '<a ') >= 2) {
					$part_doc_index = array(
						'<strong class="d-block h6 my-2 pb-2 border-bottom">Contenido</strong>',
						'<nav id="TableOfContents">',
						$heading_index,
						'</nav>',
					);
				}
			}
		}
		$this->template->setPart('doc_index', implode(PHP_EOL, $part_doc_index));
		$site_name = $this->request->query[1];
		if (!empty($this->config['site_name'])) {
			$site_name = $this->config['site_name'];
		} elseif ($this->request->query[3]) {
			$this->template
				->setPart('header', '')
				->setPart('footer', '');

		} else {
			$usr_link = sprintf('<a class="nav-link p-2 text-white active" aria-current="true" href="../notebooks/?user=%s">%s</a>', $this->config['copyright'], $this->config['copyright']);
			$logout_link = '';
			if (self::AUTH_LOGGED_IN == $this->authenticated) {
				$logout_link = sprintf(' <a href="%s" class="btn ms-3 btn-outline-light text-end">Cerrar sesión</a>', $this->uri('_logout'));
			}
			$part_header = array(
				'<header class="bg-dark text-white py-3 border-bottom">',
				'<div class="container-xxl d-grid align-items-center">',
				'<div class="d-flex align-items-center">',
				'<div class="d-flex mb-2 mb-lg-0">',
				'<img src="../icon/32.svg" alt="" width="40" height="40" class="me-2">',
				'</div>',
				'<nav class="me-auto">',
				'<ul class="navbar-nav flex-row flex-wrap bd-navbar-nav pt-2 py-md-0">',
				'<li class="nav-item col-6 col-md-auto">',
				'<a class="nav-link p-2 text-white" href="../">Inicio</a>',
				'</li>',
				'<li class="nav-item col-6 col-md-auto">' . $usr_link . '</li>',
				'</ul>',
				'</nav>',
				'<a href="../edit" class="btn btn-outline-light text-end">Editor</a>' . $logout_link,
				'</div>',
				'</div>',
				'</header>',
			);
			$part_footer = array(
				'<footer class="bd-footer bg-light">',
				'<div class="container">',
				'<p class="m-0 p-3">© yussel.com.mx</p>',
				'</div>',
				'</footer>',
			);
			$this->template
				->setPart('header', implode(PHP_EOL, $part_header))
				->setPart('footer', implode(PHP_EOL, $part_footer));
		}

		$this->template
			->setPart('site_name', htmlspecialchars($site_name))
			->setPart('site_description', htmlspecialchars($this->config['site_description']))
			->setPart('site_uri', $this->uri())
			->setPart('copyright', $this->config['copyright'])
			->setPart('copyright_link', $this->config['copyright_link'])
			->setPart('body_footer', $this->config['footer_code']);
		$this->template->setPart('doc_title', $this->queried_docs['title']);
		if ($this->request->query[3] !== 'file') {
			$active = ($this->config['display_chapter'] ? $this->queried_docs['chapter'] . ' ' : null) . $this->queried_docs['title'];
			$home_link = '';
			if ($this->request->query[3] !== 'cap') {
				$home_link = sprintf('<li class="breadcrumb-item"><a href="%s">%s</a></li>', $this->uri(), htmlspecialchars($site_name));
			}
			$part_heading = array(
				'<div class="container-xxl d-flex align-items-md-center py-2">',
				'<nav aria-label="breadcrumb">',
				'<ol class="breadcrumb m-0">',
				$home_link,
				'<li class="breadcrumb-item active" aria-current="page">' . $active . '</li>',
				'</ol>',
				'</nav>',
				'<button class="btn bd-sidebar-toggle d-md-none py-0 px-1 ms-3 order-3 collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#wiki-nav" aria-controls="wiki-nav" aria-expanded="false" aria-label="Toggle docs navigation">',
				'<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" class="bi bi-expand" fill="currentColor" viewBox="0 0 16 16"><title>Expand</title><path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h13a.5.5 0 0 1 0 1h-13A.5.5 0 0 1 1 8zM7.646.146a.5.5 0 0 1 .708 0l2 2a.5.5 0 0 1-.708.708L8.5 1.707V5.5a.5.5 0 0 1-1 0V1.707L6.354 2.854a.5.5 0 1 1-.708-.708l2-2zM8 10a.5.5 0 0 1 .5.5v3.793l1.146-1.147a.5.5 0 0 1 .708.708l-2 2a.5.5 0 0 1-.708 0l-2-2a.5.5 0 0 1 .708-.708L7.5 14.293V10.5A.5.5 0 0 1 8 10z"></path></svg>',
				'<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" class="bi bi-collapse" fill="currentColor" viewBox="0 0 16 16"><title>Collapse</title><path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h13a.5.5 0 0 1 0 1h-13A.5.5 0 0 1 1 8zm7-8a.5.5 0 0 1 .5.5v3.793l1.146-1.147a.5.5 0 0 1 .708.708l-2 2a.5.5 0 0 1-.708 0l-2-2a.5.5 0 1 1 .708-.708L7.5 4.293V.5A.5.5 0 0 1 8 0zm-.5 11.707l-1.146 1.147a.5.5 0 0 1-.708-.708l2-2a.5.5 0 0 1 .708 0l2 2a.5.5 0 0 1-.708.708L8.5 11.707V15.5a.5.5 0 0 1-1 0v-3.793z"></path></svg>',
				'</button>',
				'</div>',
			);
			$this->template->setPart('doc_heading', implode(PHP_EOL, $part_heading));
		} else {
			$this->template->setPart('doc_heading', '');
		}
		$this->template->setPart('doc_content', $this->queried_docs['content']);

	}
	private function _display_nav_item($item, &$children_elements, &$output, &$submenu_number) {
		if (!$item['hidden'] && !$item['unindexed']) {
			// Decodificar la URL
			$o_path = $item['path'];
			$d_query = $this->request->query[0];

			// Normalizar ambas cadenas en la forma NFC (Normalization Form C)
			$path_c = Normalizer::normalize($o_path, Normalizer::FORM_C);
			$query_c = Normalizer::normalize($d_query, Normalizer::FORM_C);

			$item['has_children'] = array_key_exists($item['id'], $children_elements);
			// is_current
			$item['is_current'] = (0 === strpos($query_c, $path_c . '/'));

			$output .= sprintf('<li><a class="%s%s" href="%s"%s%s>%s</a></li>',
				'd-inline-flex align-items-center rounded' . ($query_c === $path_c ? ' active' : null),
				($item['has_children'] ? ' d-flex' : null),
				($this->uri($path_c)),
				($item['is_current'] ? ' aria-expanded="true"' : null),
				('url' === $item['type'] ? ' target="_blank"' : null),
				($item['has_children'] ? ' <button class="btn vr d-inline-flex align-items-center rounded collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#wiki-nav-' . $submenu_number . '" aria-expanded="false" aria-controls="wiki-nav-' . $submenu_number . '"></button>' : null) . ($this->config['display_chapter'] ? $item['chapter'] . '. ' : null) . $item['title']);
			if ($item['has_children']) {
				foreach ($children_elements[$item['id']] as $entry) {
					if (!isset($new_level)) {
						$new_level = true;
						$output .= '<ul class="list-unstyled pb-1 small collapse' . ($item['is_current'] ? ' show' : null) . '" id="wiki-nav-' . $submenu_number . '">';
						$submenu_number++;
					}
					$this->_display_nav_item($entry, $children_elements, $output, $submenu_number);
				}
			}
			if (isset($new_level)) {
				$output .= '</ul>';
			}
			$output .= '';
		}
	}

	// Construye una URI basada en varias condiciones
	private function uri($path = null) {

		$basic_uri = rtrim(SITE_URI, '/') . '/index.php?usr=' . $this->request->query[2] . '&nb=';

		// Si $path está vacío, construir la URI predeterminada

		if (empty($path)) {
			$uri = $basic_uri . $this->request->query[1] . '&aula=' . $this->request->query[3];
		} else {

			// Si el $path contiene '://', devuelve el $path tal cual (URI absoluta)

			if (strpos($path, '://') > 0) {
				return $path;
			}

			// Si $path es '!', devuelve la URI base con 'index.php'

			if ($path == '!') {
				$uri = rtrim(SITE_URI, '/') . '/index.php';
			} elseif (false === strpos($path, '%')) {
				// Enlace local

				// Normaliza la cadena en su forma de descomposición canónica (NFD)
				// La extensión intl en tu servidor PHP debe estar habilitada
				// NOTE: Desconozco porque lo requiere localhost, pero NO el servidor
				$apunte = ($_SERVER['SERVER_NAME'] === 'localhost') ? Normalizer::normalize($path, Normalizer::FORM_D) : $path;

				$uri = $basic_uri . $this->request->query[1] . '&p=' . trim($apunte, '/') . '&aula=' . $this->request->query[3];
			} else {
				// Enlace a Libro externo
				preg_match('/([^%]*)%(.*)/', trim($path, '/'), $matches);
				$uri = $basic_uri . $matches[2] . '&p=' . $matches[1] . '&aula=' . $matches[3];
			}
		}
		return $uri;
	}
	private function assetUri($path = null) {
		if (empty($path)) {
			return null;
		} else {
			$uri = ASSETS_ROOT_URI . '/' . ltrim($path, '/');
		}
		return $uri;
	}
	private function docFileType($filename) {
		$extension_name = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		if (in_array($extension_name, array('markdown', 'md', 'mdml', 'mdown'))) {
			return 'markdown';
		}
		return false;
	}

	// Obtiene el "slug" del primer archivo en el cuaderno
	private function goHome() {
		$slug = null;
		if (empty($this->config['home_route'])) {
			foreach ($this->docs_items as $item) {
				if (($item['type'] === 'file') && !$this->isHiddenOrParentHidden($item['id'])) {
					$this->response->redirect($this->uri($item['path']));
					break;
				} else {
					continue; // Es un directorio
				}
			}
		} else {
			$this->response->redirect($this->uri($this->config['home_route']));
		}
	}

	private function getAuthHash() {
		return md5(md5($this->config['password']) . ':' . $this->config['cookie_salt']);
	}
	private function processLogin() {
		setcookie('logging', $this->getAuthHash(), time() + 86400, $this->uri('!'));
	}
	private function processLogout() {
		setcookie('logging', null, time() - 86400, $this->uri('!'));
	}
}
class DeepwikiRequest {
	public $query = array();
	public function capture() {
		if (array_key_exists('p', $_GET)) {
			$this->query[0] = trim($_GET['p'], '/');
		}
		if (array_key_exists('nb', $_GET)) {
			$this->query[1] = $_GET['nb'];
		}
		if (array_key_exists('usr', $_GET)) {
			$this->query[2] = $_GET['usr'];
		}
		if (array_key_exists('aula', $_GET)) {
			$this->query[3] = $_GET['aula'];
		}
		return $this;
	}
}
class DeepwikiResponse {
	private $status = 200;
	private $body = '';
	private $headers = array();
	public function setStatus($status) {
		$this->status = $status;
	}
	public function setBody($body) {
		$this->body = $body;
	}
	public function redirect($target) {
		$this->setStatus(302);
		$this->headers['Location'] = $target;
	}
	public function send() {
		switch ($this->status) {
		case 403:
			header('HTTP/1.1 403 Forbidden');
			break;
		case 404:
			header('HTTP/1.1 404 Not Found');
			break;
		}
		foreach ($this->headers as $name => $value) {
			header(sprintf('%s: %s', $name, $value));
		}
		echo $this->body;
	}
}
class DeepwikiTemplate {
	public $name = '';
	public $config = array();
	public $root = '';
	public $root_uri = '';
	public $path = 'index.html';
	private $parts = array();
	public function __construct($name) {
		$this->name = $name;
		$this->root = THEMES_ROOT . '/' . $this->name;
		$this->root_uri = THEMES_ROOT_URI . '/' . $this->name;
		$config_fullpath = $this->root . '/theme.json';
		if (!file_exists($config_fullpath)) {
			throw new Exception(sprintf('El archivo de configuración del tema \'%s\' no existe.', $this->name), 1);
		} else {
			$this->config = json_decode(file_get_contents($config_fullpath), true);
		}
		$this
			->setPart('header', '')
			->setPart('footer', '')
			->setPart('site_name', '')
			->setPart('site_description', '')
			->setPart('site_uri', '')
			->setPart('html_head', '')
			->setPart('head_404', '')
			->setPart('nav', '')
			->setPart('doc_title', '')
			->setPart('doc_heading', '')
			->setPart('doc_content', '')
			->setPart('doc_index', '')
			->setPart('copyright', '')
			->setPart('copyright_link', '')
			->setPart('body_footer', '')
			->setPart('login_form', '');
	}
	public function compile() {
		// fill the rest of template parts
		$part_html_head = $part_body_footer = $part_head_404 = $part_login_form = array();
		foreach ($this->config['assets']['css'] as $entry) {
			$part_html_head[] = sprintf('<link rel="stylesheet" type="text/css" href="%s" />' . PHP_EOL, $this->root_uri . '/' . $entry);
		}
		foreach ($this->config['assets']['head-js'] as $entry) {
			$part_html_head[] = sprintf('<script defer type="text/javascript" src="%s"></script>' . PHP_EOL, $this->root_uri . '/' . $entry);
		}
		foreach ($this->config['assets']['js'] as $entry) {
			$part_body_footer[] = sprintf('<script type="text/javascript" src="%s"></script>' . PHP_EOL, $this->root_uri . '/' . $entry);
		}
		foreach ($this->config['assets']['404-js'] as $entry) {
			$part_head_404[] = sprintf('<script type="text/javascript" src="%s"></script>' . PHP_EOL, $this->root_uri . '/' . $entry);
		}
		foreach ($this->config['assets']['404-css'] as $entry) {
			$part_head_404[] = sprintf('<link rel="stylesheet" type="text/css" href="%s" />' . PHP_EOL, $this->root_uri . '/' . $entry);
		}
		$part_login_form = array(
			$this->getPart('login_form'),
			'<section id="content" class="my-2">',
			'<div class="body-signin text-center">',
			'<form id="loginForm" class="form-signin" method="post" role="form">',
			'<img class="mb-4" src="../icon/n.svg" alt="" width="72" height="72">',
			'<label for="password" class="sr-only">Contraseña</label>',
			'<input type="password" id="password" name="password" class="form-control" placeholder="Contraseña" required="">',
			'<input type="submit" id="submitLoginForm" name="submitLoginForm" class="btn btn-lg btn-primary btn-block mt-3" value="Conectarse">',
			'</form></div></section>',
		);
		$this
			->setPart('html_head', implode(PHP_EOL, $part_html_head))
			->setPart('head_404', implode(PHP_EOL, $part_head_404))
			->setPart('body_footer', implode(PHP_EOL, $part_body_footer))
			->setPart('login_form', implode(PHP_EOL, $part_login_form));
		// compile template
		$template_filename = $this->root . '/' . ltrim($this->path, '/');
		if (!file_exists($template_filename)) {
			throw new Exception(sprintf('El archivo de plantilla \'$s\' no existe.', $this->path), 1);
		}
		$template_content = file_get_contents($template_filename);
		return str_replace(array_keys($this->parts), $this->parts, $template_content);
	}
	public function getPart($slug) {
		return $this->parts['{{' . $slug . '}}'];
	}
	public function setPart($slug, $content) {
		$this->parts['{{' . $slug . '}}'] = $content;
		return $this;
	}
}
$request = new DeepwikiRequest;
$app = new Deepwiki($request);
$response = $app->handle(
	$request->capture()
);
$response->send();
$app->terminate();