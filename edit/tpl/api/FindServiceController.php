<?php

class Router {
	protected $request;
	protected $server;
	protected $helper;
	protected $routes = [];
	protected $errors = [];
	protected $params = [];

	protected $uri;
	protected $routeUrl;
	protected $controller;
	protected $action;

	public function __construct($routes, $helper) {
		$this->server = $_SERVER;
		$this->request = $_REQUEST;
		$this->routes = $routes;
		$this->helper = $helper;

		$this->routeUrl = trim($this->has('PATH_INFO', $this->server));
		$this->uri = $this->has('REQUEST_URI', $this->server);

		$this->init();
	}

	protected function init() {

		$currentRoute = $this->getRoute();

		if (empty($currentRoute['route'])) {
			$this->errors[] = ' ERROR : Ruta no encontrada';
			return false;
		}

		$this->params = $this->has('params', $currentRoute);
		$metaData = explode('@', $currentRoute['route']);

		if (!$this->controller = $this->has(0, $metaData)) {
			throw new \Exception('ERROR : Clase no encontrada en la ruta URL');
		}

		if (!$this->action = $this->has(1, $metaData)) {
			throw new \Exception('ERROR : Método no encontrado en la ruta URL');
		}

		return true;
	}

	protected function getRoute($url = null) {
		$url = (!$url) ? $this->routeUrl : $url;
		foreach ($this->routes as $key => $route) {
			$args = $this->getRouteArgs($key);
			$regex = str_replace($args, '([^\/]+)', $key);
			if (preg_match('@^' . $regex . '$@', $url, $matches)) {
				array_shift($matches);
				$params = (!empty($matches)) ? $matches : [];
				return ['route' => $route, 'params' => $params];
			}
		}
		return false;
	}

	protected function getRouteArgs($uri) {
		$segments = explode('/', trim($uri, '/'));
		$result = [];
		foreach ($segments as $key => $value) {
			$pos = strpos($value, ':');
			if ($pos !== false) {
				$result[] = $value;
			}

		}
		return $result;
	}

	public function run() {

		$class = $this->controller;
		$arguments = $this->params;
		$action = $this->action;

		// lg([$class, $arguments, $action]);

		if (!class_exists($class)) {
			throw new \Exception("Not Found Controller-'{$class}' ");
		}

		$request = $this;

		$controller = new $class($arguments, $request);

		if (!method_exists($controller, $action)) {
			throw new \Exception("Not Found Action -'{$action}' ");
		}

		$this->helper->measurePerform('start');

		if (!empty($arguments)) {
			$urlParams = array();
			foreach ($arguments as $key => $value) {
				$urlParams[] = $value;
			}

			$response = $controller->$action(...$urlParams);
		} else {
			$response = $controller->$action();
		}

		$this->helper->measurePerform('end');
		$info['perform'] = $this->helper->performCast();

		$result = (!empty($response['result'])) ? $response['result'] : array();
		$error = (!empty($response['error'])) ? array_merge($this->errors, $response['error']) : array();

		return array(
			'info' => $info,
			'result' => $result,
			'response' => $response,
			'error' => $error,
			'router' => $this,
			'warn' => [],
		);
	}

	protected function has($fieldName, $data) {
		return (!empty($data[$fieldName])) ? $data[$fieldName] : null;
	}

}

abstract class AbstractBaseController {

	protected $data;
	protected $inputData;
	protected $args;
	protected $helper;

	protected $path;
	protected $text;
	protected $type;

	protected $result;
	protected $error;

	protected $systemName;
	protected $serverDir;
	protected $systemDir;
	protected $request;

	public function __construct($args = [], $request = []) {

		$this->args = $args;
		$this->request = $request;
		$this->helper = new Helper();
		$this->setRequestData();

		$this->setSystemDirs();
		$this->setSystemName();

		$this->path = $this->getData('path');
		$this->text = $this->getData('text');
		$this->type = $this->getData('type');
	}

	protected function setRequestData() {
		$input = (array) json_decode(file_get_contents('php://input'));
		$this->data = (!empty($input)) ? $input : $_REQUEST;
		$this->inputData = $this->data;
	}

	protected function getData($fieldName) {
		return $this->has($fieldName, $this->data);
	}

	protected function getUrlParam($fieldName) {
		return $this->has($fieldName, $this->args);
	}

	protected function has($fieldName, $data) {
		return (!empty($data[$fieldName])) ? $data[$fieldName] : null;
	}

	protected function getResponse($data = array(), $fieldName = 'extend') {
		$response = array(
			'result' => $this->result,
			'error' => $this->error,
		);

		if (!empty($data)) {
			$response[$fieldName] = $data;
		}

		return $response;
	}

	protected function setSystemDirs() {
		$serverDir = $_SERVER['DOCUMENT_ROOT'];
		$this->serverDir = $serverDir;
		$systemArr = explode('/', $serverDir);
		$this->systemDir = (!empty($systemArr[0])) ? $systemArr[0] : '/';
	}

	protected function setSystemName() {
		$osName = php_uname();
		$subject = 'Windows';
		if (stristr($osName, $subject) === false) {
			$osName = 'linux';
		} else {
			$osName = 'windows';
		}

		$this->systemName = $osName;
	}

	public function testAction() {
		lg($this);
	}
}

class FindController extends AbstractBaseController {
	protected $findValue;

	public function findInit($dirPath = '', $findValue = '') {

		if (!$dirPath) {
			$dirPath = $this->path;
		}

		if (!$findValue) {
			$findValue = $this->text;
		}

		if (!$this->validate($dirPath, $findValue)) {
			return $this->getResponse();
		}

		$this->findValue = $findValue;
		$this->scanDirRecursive($dirPath);
		return $this->getResponse();
	}

	public function scanDirRecursive($dirPath) {
		$files = glob($dirPath . "/*");
		foreach ($files as $key => $filePath) {
			$filePath = trim($filePath);
			if (is_file($filePath)) {
				$this->find($filePath);
			} else {
				$funcName = __FUNCTION__;
				$this->$funcName($filePath);
			}
		}
		return true;
	}

	public function find($filePath) {
		$result = array();
		$fileContent = file($filePath);
		$findValue = $this->findValue;

		foreach ($fileContent as $num => $row) {
			if (!$row) {
				continue;
			}

			if (strpos($row, $findValue, 0) !== false) {
				$result[$num] = array(
					'find_value' => $findValue,
					'path' => $filePath,
					'num' => $num,
					'row' => $row,
				);
			}
		}

		if (count($result)) {
			$this->result[$filePath] = $result;
		}

		return true;
	}

	protected function validate($dirPath, $findValue) {
		if (!is_dir($dirPath)) {
			$this->error[] = 'ERROR: Tal directorio no existe. -- ' . $dirPath;
		}

		if (!$findValue) {
			$this->error[] = 'ERROR: Valor vacío para buscar ';
		}

		if (!empty($this->error)) {
			return false;
		}

		return true;
	}

	public function getError() {
		return $this->error;
	}

}

class FileManager extends AbstractBaseController {

	public function getServerDirPath() {
		$this->result = $this->serverDir;
		return $this->getResponse();
	}

	public function getSystemDirPath() {
		$this->result = $this->systemDir;
		return $this->getResponse();
	}

	public function scanServerDir() {
		$this->scanDir($this->serverDir);
		return $this->getResponse();
	}

	public function scanSystemDir() {
		$this->scanDir($this->systemDir);
		return $this->getResponse();
	}

	public function scanDirInit($dirPath = false) {
		if (!$dirPath) {
			$dirPath = $this->path;
		}

		$this->scanDir($dirPath);
		return $this->getResponse();
	}

	public function loadFileContent($filePath = '', $type = 'arr') {
		if (!$filePath) {
			$filePath = $this->path;
		}

		if ($this->type) {
			$type = $this->type;
		}

		$this->getFileContent($filePath, $type);
		return $this->getResponse();
	}

	protected function getFileContent($filePath, $type = 'arr') {
		if (!$this->validate($filePath, 'file')) {
			return false;
		}

		if ($type == 'arr') {
			$fileContent = file($filePath);
		} else {
			$fileContent = htmlspecialchars(encoding(file_get_contents($filePath)));
		}

		$this->result = $fileContent;
	}

	protected function getFileInfo($filePath) {
		if (!$this->validate($filePath)) {
			return false;
		}

		return array(
			'path' => $filePath,
			'name' => basename($filePath),
			'state' => stat($filePath),
			'realpath' => realpath($filePath),
			'pathinfo' => pathinfo($filePath),
		);
	}

	protected function scanDir($dirPath) {
		if (!$this->validate($dirPath, 'dir')) {
			return false;
		}

		$result = array();
		$files = glob($dirPath . "/*");
		foreach ($files as $key => $filePath) {
			$fileInfo = $this->getFileInfo($filePath);
			$fileName = basename($filePath);
			if (is_file($filePath)) {
				$fileInfo['type'] = 'file';
			} else {
				$fileInfo['type'] = 'dir';
			}
			$result[$fileName] = $fileInfo;
		}

		$this->result = $result;

		return $this->result;
	}

	protected function validate($path, $type = '') {

		if ((!file_exists($path))) {
			$this->error[] = 'ERROR: Archivo no subido -- ' . $path;
		}

		switch ($type) {

		case 'dir':
			if ((!is_dir($path))) {
				$this->error[] = 'ERROR: Esto no es un directorio -- ' . $path;
			}

			break;

		case 'file':
			if ((!is_file($path))) {
				$this->error[] = 'ERROR: Esto no es un archivo -- ' . $path;
			}

			break;
		}

		if (!empty($this->error)) {
			return false;
		}

		return true;
	}

	public function getPathToSystem($pathType) {
		$result = null;
		switch ($pathType) {
		case 'system':$result = $this->getSystemDirPath();
			break;
		case 'server':$result = $this->getServerDirPath();
			break;
		}
		return $result;
	}

	public function selectScanDir($scanType) {
		$result = [];
		switch ($scanType) {
		case 'system':$result = $this->scanSystemDir();
			break;
		case 'server':$result = $this->scanServerDir();
			break;
		case 'child':$result = $this->scanDirInit();
			break;
		}
		return $result;
	}

	public function fileReader($filePath = '') {
		if ($filePath) {
			$filePath = $this->path;
		}

		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . filesize($filePath));
		readfile($filePath);
	}

}

class File {
	protected string $fileName;
	protected string $fileCreated;
	protected string $fileModified;
	protected string $fileSize;
	public string $fileType;

	/**
	 * File constructor
	 *
	 * @param $fileName
	 */
	public function __construct($fileName = null) {
		if ($fileName) {
			$this->setFile($fileName);
		}

	}

	public function setFile($fileName) {
		$this->fileName = $fileName;
		$this->fileCreated = self::getNormalizedDate(filectime($fileName));
		$this->fileModified = self::getNormalizedDate(filemtime($fileName));
		$this->fileSize = self::getConvertedFileSize(filesize($fileName));
		$this->fileType = filetype($fileName);

		return $this;
	}

	/**
	 * Returns file name
	 *
	 * @return string
	 */
	public function getFileName(): string {
		return $this->fileName;
	}

	/**
	 * Returns date when file was created
	 *
	 * @return string
	 */
	public function getFileCreated(): string {
		return $this->fileCreated;
	}

	/**
	 * Returns date when file was modified
	 *
	 * @return string
	 */
	public function getFileModified(): string {
		return $this->fileCreated;
	}

	/**
	 * Returns file size
	 *
	 * @return string
	 */
	public function getFileSize(): string {
		return $this->fileSize;
	}

	/**
	 * Converts Unix timestamp into human readable date
	 *
	 * @param int $unixTimestamp
	 * @return string
	 */
	private static function getNormalizedDate(int $unixTimestamp): string {
		return date("d.m.Y", $unixTimestamp);
	}

	/**
	 * Converts bytes into human readable file size
	 *
	 * @param int $bytes
	 * @return string
	 */
	private static function getConvertedFileSize(int $bytes): string{
		$result = "";
		$bytes = floatval($bytes);
		$arBytes = array(
			0 => array(
				"UNIT" => "TB",
				"VALUE" => pow(1024, 4),
			),
			1 => array(
				"UNIT" => "GB",
				"VALUE" => pow(1024, 3),
			),
			2 => array(
				"UNIT" => "MB",
				"VALUE" => pow(1024, 2),
			),
			3 => array(
				"UNIT" => "KB",
				"VALUE" => 1024,
			),
			4 => array(
				"UNIT" => "B",
				"VALUE" => 1,
			),
		);

		foreach ($arBytes as $arItem) {
			if ($bytes >= $arItem["VALUE"]) {
				$result = $bytes / $arItem["VALUE"];
				$result = str_replace(".", ",", strval(round($result, 2))) . " " . $arItem["UNIT"];
				break;
			}
		}
		return $result;
	}

}

class Helper {

	protected $performParams = array();

	public function measurePerform($name = 'start') {
		$this->performParams[$name] = array(
			'date' => date('Y-m-d__H-i-s'),
			'time' => date('H-i-s'),
			'microtime' => microtime(true),
			'memory' => memory_get_usage(),
		);
	}

	public function performCast() {
		$execTime = $dateTime = '';

		if ((!empty($this->performParams['start'])) && (!empty($this->performParams['end']))) {
			$start = $this->performParams['start'];
			$end = $this->performParams['end'];
			$execTime = round($end['microtime'] - $start['microtime'], 3);
			$dateTime = "start: " . $start['time'] . "___finish: " . $end['time'];
		}

		return array(
			'perform_params' => $this->performParams,
			'exec_time' => $execTime,
			'date_time' => $dateTime,
		);
	}

	public function commandRun($cmd) {
		$osName = php_uname();
		if (substr($osName, 0, 7) == "Windows") {
			pclose(popen("start /B " . $cmd, "r"));
		} else {
			exec($cmd . " > /dev/null &");
		}
	}
}

////////////////////////////////////////////////////
/// ////////////////////////////////////////////////
///
///
///

class Dispatcher {
	protected $request;
	protected $server;
	protected $data;
	protected $error = array();
	protected $file;

	protected $fileControl;
	protected $fileManager;
	protected $helper;

	protected $action;

	protected $dirPath;
	protected $filePath;
	protected $findValue;

	public function __construct($fileManager, $findControl, $helper) {
		$this->request = $_REQUEST;
		$this->server = $_SERVER;
		$this->findControl = $findControl;
		$this->fileManager = $fileManager;
		$this->helper = $helper;

		$this->setData();
		$this->setAction();

		// $this->setTestData();
		// $this->init();
	}

	protected function setParameters() {

	}

	protected function setAction($name = 'action') {
		$this->action = $this->requestParam($name);
		if (!$this->action) {
			$this->error[] = 'ERROR : sin definir "' . $name . '"!';
		}

	}

	protected function requestParam($fieldName) {
		return $this->has($fieldName, $this->request);
	}

	protected function dataParam($fieldName) {
		return $this->has($fieldName, $this->data);
	}

	protected function serverParam($fieldName) {
		return $this->has($fieldName, $this->server);
	}

	protected function has($fieldName, $data) {
		return (!empty($data[$fieldName])) ? $data[$fieldName] : null;
	}

	protected function setData() {
		$input = (array) json_decode(file_get_contents('php://input'));
		$this->data = (!empty($input)) ? $input : $_REQUEST;
	}

	protected function setTestData() {
		$this->data['dir_path'] = 'C:\OpenServer\domains\localhost\FindText\test\src';
		$this->data['find_value'] = 'test_id_2345666TYkyi';
	}

	public function run() {
		$result = $error = $response = $info = array();

		switch ($this->action) {
		case 'get_server_dir':
			$dirPath = $this->serverParam('DOCUMENT_ROOT');
			$response = $this->fileManager->scanDirInit($dirPath);
			$result = $response['result'];
			$error = $response['error'];
			break;

		case 'get_file_content':
			$filePath = $this->dataParam('file_path');
			$response = $this->fileManager->getFileContent($filePath);
			$result = $response['result'];
			$error = $response['error'];
			break;

		case 'edit_file':

			break;

		case 'scan_dir':

			$dirPath = $this->dataParam('dir_path');
			$response = $this->fileManager->scanDirInit($dirPath);
			$result = $response['result'];
			$error = $response['error'];

			if (!empty($error)) {
				$this->error = array_merge($this->error, $error);
			}

			break;

		case 'find_text':

			$this->helper->measurePerform('start');

			$dirPath = $this->dataParam('dir_path');
			$findValue = $this->dataParam('find_value');
			$this->findControl->findInit($dirPath, $findValue);
			$response = $this->findControl->getResponse();
			$result = $response['result'];
			$error = $response['error'];
			if (!empty($error)) {
				$this->error = array_merge($this->error, $error);
			}

			$this->helper->measurePerform('end');
			$info['perform'] = $this->helper->performCast();

			break;

		default:

			break;
		}

		return array(
			'info' => $info,
			'result' => $result,
			'response' => $response,
			'error' => $this->error,
		);
	}

}

class Template {

	public function render($response) {

		$html = $edit = $error = '';
		$action = (!empty($response['action'])) ? $response['action'] : '';

		if (!empty($response['error'])) {
			foreach ($response['error'] as $key => $value) {
				$error .= '<div style="color:red" >' . $value . '</div>';
			}
		}

		switch ($action) {
		case 'edit':
			$edit = $this->editView($response);
			break;

		case 'submit':
			$html = $this->findResultView($response);
			break;
		}

		return array(
			'action' => $action,
			'html' => $html,
			'edit' => $edit,
			'error' => $error,
		);
	}

	protected function editView($response) {
		$html = '';
		if (empty($response['file'])) {
			$html = '<div class="alert-error"><h4>¡Error!</h4> Archivo no encontrado </div>';
		} else {

			$fileContent = $response['file_content'];
			$filePath = $response['file_path'];

			$html .= '<textarea id="source"> ' . $fileContent . ' </textarea>';
			$html .= '<div class="clear"></div><a class="btn btn-danger right" href="javascript:window.close()"> Cerrar </a>&nbsp;';
			$html .= '<a onClick="save(\'' . urlencode($filePath) . '\')" class="btn btn-info right" href="#" > Guardar </a>';
		}

		return $html;
	}

	protected function findResultView($response) {

		$cnt = 0;
		$html = '<div class="result">';
		$header = '<h2>Texto especificado encontrado en archivos:</h2> ';
//        if ($replace)
		//            $header = '<h2>Заданный текст заменен в файлах:</h2>';

		$html .= $header;

		// lg($response);

		if (!empty($response['error'])) {

			foreach ($response['error'] as $key => $value) {
				$row = '<div>' . $value . '<div>';
				$html .= $row;
			}

		} elseif (!empty($response['result'])) {

			foreach ($response['result'] as $key => $file) {
				foreach ($file as $num => $item) {

					$cnt++;
					$filePath = $item['path'];
					// $fileUrl = urlencode(dirname(__FILE__) . DIRECTORY_SEPARATOR . $filePath);
					$fileUrl = urlencode($filePath);
					$rowLine = htmlspecialchars($item['row']);

					// lg([$fileUrl, $item, $filePath]);

					$html .= '<div class="result-item" >
                                 <div class="left"><b>' . $cnt . ':</b> <span class="file">' . encoding($filePath) . '</span></div>
                                 <div onClick="del(this, \'' . $fileUrl . '\')" title="Eliminar un archivo" class="btn btn-danger btn-mini right">x</div>
                                 <div onClick="edit(\'' . $fileUrl . '\')" class="btn btn-info btn-mini right"> Editar </div>
                                 <div class="clear"></div>
                                 <code title="Haga doble clic para ver el texto completo del archivo"
                                       ondblclick="seeAll(this, \'' . $fileUrl . '\')">...' . $rowLine . '...</code>
                              </div>';
				}
			}

		} else {
			$html .= 'No hay coincidencias';
		}

		if (!empty($response['exec_time'])) {
			$html .= '<br><b>Tiempo de espera: ' . $response['exec_time'] . ' segundos.</b></div>';
		}

		return $html;
	}

}

//функция поиска
function scan_dir($dirname) {

	global $text, $replace, $ext, $cnt, $html, $regex, $regis;

	$dir = opendir($dirname);

	while (($file = readdir($dir)) !== false) {

		if ($file != '.' && $file != '..') {
			$file_name = $dirname . DIRECTORY_SEPARATOR . $file;

			if (is_file($file_name)) {
				$ext_name = substr(strrchr($file_name, '.'), 1);

				if (in_array($ext_name, $ext) || $file_name == '.' . DIRECTORY_SEPARATOR . BASE_NAME) {
					continue;
				}

				$content = encoding(file_get_contents($file_name));
				$str = '';

				if ($regex) {
					if (preg_match('/' . $text . '/s' . $regis, $content, $res, PREG_OFFSET_CAPTURE)) {
						$str = preg_replace('/(' . $text . ')/s' . $regis, "%find%\$1%/find%",
							mysubstr($content, $res[0][1], $res[0][0]));
					}
				} else {
					if (($pos = strpos($content, $text)) !== false) {
						$str = str_replace($text, '%find%' . $text . '%/find%', mysubstr($content, $pos, $text));
					}
				}

				if ($str != '') {
					$cnt++;

					if ($replace) {
						replace($content, $file_name, $regex);
					}

					$arg = urlencode(DIR_NAME . DIRECTORY_SEPARATOR . $file_name);

					$html .= '<div class="result-item">
                                 <div class="left"><b>' . $cnt . ':</b> <span class="file">' . encoding($file_name) . '</span></div>
                                 <div onClick="del(this, \'' . $arg . '\')" title="Eliminar un archivo
" class="btn btn-danger btn-mini right">x</div>
                                 <div onClick="edit(\'' . $arg . '\')" class="btn btn-info btn-mini right">Editar</div>
                                 <div class="clear"></div>
                                 <code title="Haga doble clic para ver el texto completo del archivo"
                                       ondblclick="seeAll(this, \'' . $arg . '\')">...' . htmlspecialchars($str) . '...</code>
                             </div>';
				}
			}

			if (is_dir($file_name)) {
				scan_dir($file_name);
			}
		}
	}

	closedir($dir);
}

//удаляем экранирование если нужно
function mystripslashes($string) {
	if (@get_magic_quotes_gpc()) {
		return stripslashes($string);
	} else {
		return $string;
	}
}

//функция замены
function replace($content, $file_name, $regex) {
	global $retext, $text, $regis;

	if ($regex) {
		$content = preg_replace('/' . $text . '/s' . $regis, $retext, $content);
	} else {
		$content = str_replace($text, $retext, $content);
	}

	if (!is_writable($file_name)) {
		chmod($file_name, 0644);
	}

	return file_put_contents($file_name, $content);
}

function mysubstr($content, $pos, $find_str, $cnt_str = CNT_STR) {

	$pos_start = $pos - $cnt_str;

	if ($pos_start <= 0) {
		$pos_start = 0;
	}

	$pos_end = ($pos - $pos_start) + strlen($find_str) + $cnt_str;

	return substr($content, $pos_start, $pos_end);
}

function encoding($content) {

	if (mb_check_encoding($content, 'windows-1251') && !mb_check_encoding($content, 'utf-8')) {
		return mb_convert_encoding($content, 'utf-8', 'windows-1251');
	} else {
		return $content;
	}

}

function tree($dirname) {
	$html = '';
	if (is_readable($dirname)) {
		$dir = opendir($dirname);
		while (($item = readdir($dir)) !== false) {
			if ($item != '.' && $item != '..') {
				$path = $dirname . DIRECTORY_SEPARATOR . $item;
				if (is_dir($path)) {
					$html .= '<div class="dir-list-item" ><span onclick="getDirs(this)" class="dir-open-btn" >+</span><a rel="' . $path . '" onclick="document.getElementById(\'dir\').value = this.getAttribute(\'rel\');" >  ' . $item . '</a><div class="tree-items"></div></div>';
				} else {
					$html .= '<div class="dir-list-item" >
                                   <div style="margin-left: 20px; font-style: italic" onclick="document.getElementById(\'dir\').value = this.getAttribute(\'rel\');" >  ' . $item . '</div>
                              </div>';
				}
			}
		}
	} else {
		$html .= '<div> Directorio no legible </div>';
	}

	return $html;
}

function memoryCheck() {
	return array(
		'date' => date('Y-m-d__H-i-s'),
		'time' => date('H-i-s'),
		'microtime' => microtime(true),
		'memory' => memory_get_usage(),
	);
}

function lg() {

	$out = '';
	$get = false;
	$style = 'margin:10px; padding:10px; border:3px red solid;';
	$args = func_get_args();
	foreach ($args as $key => $value) {
		$itemArr = array();
		$itemStr = '';
		is_array($value) ? $itemArr = $value : $itemStr = $value;
		if ($itemStr == 'get') {
			$get = true;
		}

		$line = print_r($value, true);
		$out .= '<div style="' . $style . '" ><pre>' . $line . '</pre></div>';
	}

	$debugTrace = debug_backtrace();
	$line = print_r($debugTrace, true);
	$out .= '<div style="' . $style . '" ><pre>' . $line . '</pre></div>';

	if ($get) {
		return $out;
	}

	print $out;
	exit;
}

function _filePutContents($filename, $data) {
	$file = fopen($filename, 'w');
	if (!$file) {
		return false;
	}
	$bytes = fwrite($file, $data);
	fclose($file);
	return $bytes;
}

function _fileGetContents($filename) {
	$file = fopen($filename, 'r');
	$fcontents = fread($file, filesize($filename));
	fclose($file);
	return $fcontents;
}

function scanDirectories($dirPath, $findValue) {
	$files = glob($dirPath . "/*");
	$findWorker = 'find_worker.php';
	foreach ($files as $key => $filePath) {
		$filePath = trim($filePath);
		if (is_file($filePath)) {

			$cmd = 'php ' . $findWorker . ' "' . $filePath . '" "' . $findValue . '"';

			// echo "<div style='margin:3px; padding:3px; border: 1px red solid;' >$filePath</div>";
			// echo "<div style='margin:3px; padding:3px; border: 1px red solid;' >$cmd</div>";

			commandInit($cmd);
		} else {
			$funcName = __FUNCTION__;
			$funcName($filePath, $findValue);
		}
	}
	return true;
}

function commandInit($cmd) {
	$osName = php_uname();
	if (substr($osName, 0, 7) == "Windows") {
		pclose(popen("start /B " . $cmd, "r"));
	} else {
		exec($cmd . " > /dev/null &");
	}
}
