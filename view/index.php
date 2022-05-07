<?php
require_once '../vendor/autoload.php';

ChromePhp::log('Hello console!');
ChromePhp::log($_SERVER);
ChromePhp::warn('something went wrong!');

date_default_timezone_set('UTC');
setlocale(LC_ALL, 'en_US.UTF8');
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
	private $docs_index;
	private $docs_items = array();
	private $queried_docs;

	private $first_route; // Se usará en caso de que: empty($this->config['home_route']) == true

	public function __construct() {

		// environment

		define('SITE_URI', '/' . trim(dirname($_SERVER['PHP_SELF']), '/'));
		define('APP_ROOT', __DIR__);

		$offset = strlen(SITE_URI) - strpos(SITE_URI, '/', '1');
		define('PARENT_ROOT', substr(APP_ROOT, 0, -$offset));

		define('CONFIG_ROOT', PARENT_ROOT . '/edit/data'); // Módulo "Editor"
		// DOCS_ROOT se define en función de GET
		define('DOCS_ROOT', CONFIG_ROOT . '/' . urlencode($_GET["usr"]) . '/' . urlencode($_GET["nb"]));

		define('VENDOR_ROOT', APP_ROOT . '/deepwiki-vendor');
		define('THEMES_ROOT', APP_ROOT . '/deepwiki-themes');
		define('THEMES_ROOT_URI', rtrim(SITE_URI, '/') . '/deepwiki-themes');

		// components

		// require 'vendor/erusev/parsedown/Parsedown.php';
		require 'dist/Parsedown.php';
		// require 'vendor/erusev/parsedown-extra/ParsedownExtra.php';
		require 'dist/ParsedownExtra.php';
		// require 'vendor/benjaminhoegh/parsedown-extended/ParsedownExtended.php';
		require 'dist/ParsedownExtended.php';

		$this->loadConfig();

		// template instance

		$this->template = new DeepwikiTemplate($this->config['theme']);

		// constants based on configuration

		define('ASSETS_ROOT_URI', rtrim(SITE_URI, '/') . '/' . trim($this->config['assets_path'], '/'));

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

	private function loadConfig() {

		$config_fullpath = DOCS_ROOT . '/notebook.json';

		if (!file_exists($config_fullpath)) {

			// Error: 404

		}
		if (file_exists($config_fullpath)) {
			$config_json = file_get_contents($config_fullpath);
			$this->config = json_decode($config_json, true);
		}

		if (!is_array($this->config)) {
			$this->config = array();
		}

		// fill defaults
		$this->config = array_merge(array(
			'copyright_link' => PARENT_ROOT,
			'copyright' => urlencode($_GET['usr']),
			'theme' => 'yussel',
			'assets_path' => 'deepwiki-docs-example/assets',
			'rewrite' => false,
			'footer_code' => '<a href="https://github.com/ychongsaytc/deepwiki" target="_blank" rel"nofollow" class="hidden-xs"><img style="position: absolute; top: 0; right: 0; border: 0; z-index: 1000;" src="https://camo.githubusercontent.com/38ef81f8aca64bb9a64448d0d70f1308ef5341ab/68747470733a2f2f73332e616d617a6f6e6177732e636f6d2f6769746875622f726962626f6e732f666f726b6d655f72696768745f6461726b626c75655f3132313632312e706e67" alt="Fork me on GitHub" data-canonical-src="https://s3.amazonaws.com/github/ribbons/forkme_right_darkblue_121621.png"></a>',
			'cookie_salt' => 'REPLACE_THIS_WITH_A_RANDOM_STRING',
			'docs' => array(), // backward compatibility
		), $this->config);

	}

	private function loadDocs() {
		$docs_index_fullpath = CONFIG_ROOT . '/index.json';

		if (file_exists($docs_index_fullpath)) {
			$config_json = file_get_contents($docs_index_fullpath);
			$this->docs_index = json_decode($config_json, true);
		} else {
			$this->docs_index = $this->config['docs']; // backward compatibility
		}

		// Recorrer todos los documentos

		if (empty($this->docs_index)):

			// Escanear el directorio de documentos si no hay una configuración definida

			foreach (scandir(DOCS_ROOT) as $filename) {
				if (in_array($filename, array('.', '..', '.gitignore'))) {
					continue;
				}

				$type = $this->docFileType($filename);
				if (false === $type) {
					continue;
				}

				$filename_pure = substr($filename, 0, strrpos($filename, '.'));
				$matches = array();
				preg_match_all('#^(([0-9a-z]+\.)+\ +)?(.+?)(\ +\[(\S+)\])?$#', $filename_pure, $matches);
				$title = $matches[3][0];
				$chapter = rtrim($matches[1][0], ' ');
				if (empty($matches[5][0])) {
					$slug = $this->sanitizeTitle($title);
				} else {
					$slug = $this->sanitizeTitle($matches[5][0]);
				}

				$chapter_tree = explode('.', rtrim($chapter, '.'));
				$depth = count($chapter_tree);
				array_pop($chapter_tree);
				if (empty($chapter_tree)) {
					$parent = '';
				} else {
					$parent = implode('.', $chapter_tree) . '.';
				}

				$this->docs_items[] = compact('title', 'slug', 'chapter', 'filename', 'type', 'depth', 'parent');
			}

			// sort by chapter
			uasort($this->docs_items, function ($a, $b) {
				$chapter = array(
					$a['chapter'],
					$b['chapter'],
				);
				foreach (array_keys($chapter) as $k) {
					if (empty($chapter[$k])) {
						continue;
					}
					$chapter[$k] = explode('.', trim($chapter[$k], '.'));
					$chapter[$k] = array_map(function ($v) {
						return str_pad($v, 10, '0', STR_PAD_LEFT);
					}, $chapter[$k]);
					$chapter[$k] = implode('.', $chapter[$k]);
				}
				$sorted = $chapter;
				sort($sorted);
				return ($chapter === $sorted ? 0 : 1);
			});

		else:

			// read from docs configuration
			function _walk_config_docs_tree($docs, &$items, $parent = '') {
				$i = 1;
				foreach ($docs as $slug => $item) {
					$item = array_merge(array(
						'title' => null,
						'file' => null,
						'children' => array(),
					), $item);
					$chapter = $parent . $i . '.';
					$items[] = array(
						'title' => $item['title'],
						'slug' => $slug,
						'chapter' => $chapter,
						'filename' => $item['file'],
						'type' => $this->docFileType($item['file']),
						'depth' => substr_count($parent, '.') + 1,
						'parent' => $parent,
					);
					if (!empty($item['children'])) {
						_walk_config_docs_tree($item['children'], $items, $chapter);
					}

					$i++;
				}
			}
			_walk_config_docs_tree($this->docs_index, $this->docs_items);

		endif;

		// generate paths

		foreach (array_keys($this->docs_items) as $k) {
			if ('url' === $this->docs_items[$k]['type']) {
				$this->docs_items[$k]['path'] = $this->docs_items[$k]['filename'];
				continue;
			}
			$path = '/' . $this->docs_items[$k]['slug'];
			$current_pos = $this->docs_items[$k]['parent'];
			for ($i = $this->docs_items[$k]['depth'] - 1; $i >= 1; $i--) {
				foreach ($this->docs_items as $entry) {
					if ($entry['depth'] === $i && $current_pos === $entry['chapter']) {
						$current_pos = $entry['parent'];
						$path = '/' . $entry['slug'] . $path;
						break;
					}
				}
			}
			$this->docs_items[$k]['path'] = trim($path, '/');
		}

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

	private function compileDocs() {

		// compile document content

		foreach ($this->docs_items as $entry) {

			// $entry cada Capitulo

			if ($entry['path'] !== $this->request->query[0]) {
				continue;
			}

			$origin = file_get_contents(DOCS_ROOT . '/' . $entry['filename']);
			switch ($entry['type']) {
			case 'markdown':
				// $Parsedown = new Parsedown();
				$Parsedown = new ParsedownExtended([
					"task" => true,
					"kbd" => true,
					"math" => false,
					"mark" => true,
					"insert" => false,
					"smartTypography" => false,
					"scripts" => true,
					"emojis" => true,
					"diagrams" => true,
					'sup' => true,
					'sub' => true,
					"tables" => [
						"tablespan" => false,
					],
					"headings" => [
						"auto_anchors" => true,
					],
				]);

				$Parsedown->setSafeMode(false);
				// $Parsedown->setMarkupEscaped(false);
				// Si desea escapar de HTML
				// $Parsedown->setUrlsLinked(false);
				// Si desea habilitar/deshabilitar el enlace automático (default: true)
				$content = $Parsedown->text($origin);
				break;
			case 'html':
				$content = $origin;
				break;
			default:
				$content = nl2br(htmlspecialchars($origin));
				break;
			}

			/** intergrate dedicated translation */

			if (in_array($entry['type'], array('markdown', 'html'))) {

				/** Reemplazar el enlace interno de la página */

				$matches = array();
				preg_match_all('#\ (href|src)="\#\/([^\"]+)"#ui', $content, $matches);
				if ($matches[0]) {

					foreach (array_keys($matches[0]) as $i) {
						$content = str_replace($matches[0][$i], ' ' . $matches[1][$i] . '="' . $this->uri($matches[2][$i]) . '"', $content);
					}
				}

				/** Reemplazar URL de activos asset */

				$matches = array();
				preg_match_all('#\ (href|src)="\!\/([^\"]+)"#ui', $content, $matches);
				if ($matches[0]) {
					foreach (array_keys($matches[0]) as $i) {
						$content = str_replace($matches[0][$i], ' ' . $matches[1][$i] . '="' . $this->assetUri($matches[2][$i]) . '"', $content);
					}
				}

				/** Integrar propiedades de etiqueta */

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
			}

			$this->queried_docs = array(
				'title' => $entry['title'],
				'slug' => $entry['slug'],
				'chapter' => $entry['chapter'],
				'filename' => $entry['filename'],
				'content' => $content,
			);
			break;

		}

	}

	private function handleRequest() {

		if (empty($this->request->query[0]) && !empty($this->request->query[1]) && !empty($this->request->query[1])) {
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

		$part_nav = $part_doc_index = array();

		// generate navigation menu
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

		$this->template
			->setPart('nav', implode(PHP_EOL, $part_nav))
			->setPart('doc_index', implode(PHP_EOL, $part_doc_index));

		$site_name = urldecode($this->request->query[1]);
		if (!empty($this->config['site_name'])) {
			$site_name = $this->config['site_name'];
		}

		$this->template
			->setPart('site_name', htmlspecialchars($site_name))
			->setPart('site_description', htmlspecialchars($this->config['site_description']))
			->setPart('site_uri', $this->uri())
			->setPart('copyright', $this->config['copyright'])
			->setPart('copyright_link', $this->config['copyright_link'])
			->setPart('body_footer', $this->config['footer_code']);

		$this->template->setPart('doc_title', $this->queried_docs['title']);
		$this->template->setPart('doc_heading', ($this->config['display_chapter'] ? $this->queried_docs['chapter'] . ' ' : null) . $this->queried_docs['title']);
		$this->template->setPart('doc_content', $this->queried_docs['content']);

		if (self::AUTH_LOGGED_IN == $this->authenticated) {
			$this->template->setPart('logout_link', sprintf('<a class="btn btn-sm btn-outline-secondary" href="%s">Cerrar sesión</a>', $this->uri('_logout')));
		}

	}

	private function _display_nav_item($item, &$children_elements, &$output, &$submenu_number) {
		$item['has_children'] = array_key_exists($item['chapter'], $children_elements);
		$item['is_current'] = 0 === strpos($this->request->query[0], $item['path'] . '/');
		$output .= sprintf('<li><a class="%s%s" href="%s"%s%s>%s</a></li>',
			'd-inline-flex align-items-center rounded' . ($this->request->query[0] === $item['path'] ? ' active' : null),
			($item['has_children'] ? ' d-flex' : null),
			($this->uri($item['path'])),
			($item['is_current'] ? ' aria-expanded="true"' : null),
			('url' === $item['type'] ? ' target="_blank"' : null),
			($item['has_children'] ? ' <button class="btn d-inline-flex align-items-center rounded collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#wiki-nav-' . $submenu_number . '" aria-expanded="false" aria-controls="wiki-nav-' . $submenu_number . '"></button>' : null) . ($this->config['display_chapter'] ? $item['chapter'] . ' ' : null) . $item['title']);
		if ($item['has_children']) {
			foreach ($children_elements[$item['chapter']] as $entry) {
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

	private function uri($path = null, $absolute = false) {
		if (strpos($path, '://') > 0) {
			return $path;
		}

		if (empty($path)) {
			$uri = rtrim(SITE_URI, '/') . '/index.php?usr=' . $this->request->query[2] . '&nb=' . $this->request->query[1];
		} else {
			if ($path == '!') {
				$uri = rtrim(SITE_URI, '/') . '/index.php';
			} elseif ($this->config['rewrite']) {
				$uri = rtrim(SITE_URI, '/') . '/' . $path . (false === strpos($path, '#') ? '/' : null);
			} else {
				$uri = rtrim(SITE_URI, '/') . '/index.php?usr=' . $this->request->query[2] . '&nb=' . $this->request->query[1] . '&p=' . trim($path, '/');
			}
		}

		if ($absolute) {
			$uri = $this->absoluteUri($uri);
		}

		return $uri;
	}

	private function assetUri($path = null, $absolute = false) {
		if (empty($path)) {
			return null;
		} else {
			$uri = ASSETS_ROOT_URI . '/' . ltrim($path, '/');
		}
		if ($absolute) {
			$uri = $this->absoluteUri($uri);
		}
		return $uri;
	}

	private function absoluteUri($uri) {
		$protocol = (array_key_exists('HTTPS', $_SERVER) && 'on' == $_SERVER['HTTPS']) ? 'https://' : 'http://';
		$port = (
			array_key_exists('SERVER_PORT', $_SERVER) &&
			(('http' == $protocol && $_SERVER['SERVER_PORT'] != '80') ||
				('https' == $protocol && $_SERVER['SERVER_PORT'] != '443'))
		) ? ':' . $_SERVER['SERVER_PORT'] : null;
		return $protocol . $_SERVER['SERVER_NAME'] . $port . $uri;
	}

	private function docFileType($filename) {
		if (strpos($filename, '://') > 0) {
			return 'url';
		}
		$extension_name = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		if (in_array($extension_name, array('markdown', 'md', 'mdml', 'mdown'))) {
			return 'markdown';
		}
		if (in_array($extension_name, array('html', 'htm'))) {
			return 'html';
		}
		if (in_array($extension_name, array('txt'))) {
			return 'plain';
		}
		return false;
	}

	public function getSlug($string) {
		$slug = null;
		$filename_pure = substr($string, 0, strrpos($string, '.'));
		$matches = array();
		preg_match_all('#^(([0-9a-z]+\.)+\ +)?(.+?)(\ +\[(\S+)\])?$#', $filename_pure, $matches);
		$title = $matches[3][0];
		$chapter = rtrim($matches[1][0], ' ');
		if (empty($matches[5][0])) {
			$slug = $this->sanitizeTitle($title);
		} else {
			$slug = $this->sanitizeTitle($matches[5][0]);
		}

		$chapter_tree = explode('.', rtrim($chapter, '.'));
		$depth = count($chapter_tree);
		array_pop($chapter_tree);
		if (!empty($chapter_tree)) {
			$slug = null;
		}

		return $slug;
	}

	private function goHome() {
		$home_route = $this->config['home_route'];
		if (empty($home_route)) {
			// Generar el "slug" del primer archivo en el cuaderno (Sin Sub-Capitulos)
			$slug = null;
			foreach ($this->config['tree'] as $key => $value) {
				$extension_name = strtolower(pathinfo($key, PATHINFO_EXTENSION));
				if ($extension_name == 'md') {
					$slug = $this->getSlug($key);
					if ($slug == null) {
						continue; // Es un Sub-Capitulo
					} else {
						break;
					}
				} else {
					continue; // Es una Carpeta
				}
			}
			$home_route = $slug;
		}
		$home = $this->uri($home_route, true);
		$this->response->redirect($home);
	}

	private function sanitizeTitle($string) {
		$output = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
		$output = strtolower($output);
		$output = preg_replace('#([^0-9a-z]+)#', '-', $output);
		$output = trim($output, '-');
		if (empty($output)) {
			return 'title';
		}
		return $output;
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
			$this->query[1] = urlencode($_GET['nb']);
		}
		if (array_key_exists('usr', $_GET)) {
			$this->query[2] = urlencode($_GET['usr']);
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
			->setPart('login_form', '')
			->setPart('logout_link', '');
	}

	public function compile() {
		// fill the rest of template parts
		$part_html_head = $part_body_footer = $part_head_404 = $part_login_form = array();

		foreach ($this->config['assets']['css'] as $entry) {
			$part_html_head[] = sprintf('<link rel="stylesheet" type="text/css" href="%s" />' . PHP_EOL, $this->root_uri . '/' . $entry);
		}

		foreach ($this->config['assets']['head-js'] as $entry) {
			$part_html_head[] = sprintf('<script type="text/javascript" src="%s"></script>' . PHP_EOL, $this->root_uri . '/' . $entry);
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
			'<img class="mb-4" src="tpl/img/yussel.svg" alt="" width="72" height="72">',
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
