<?php

set_time_limit(0);

header('Content-Type: text/html; charset=utf-8');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

date_default_timezone_set('Europe/Moscow');

include_once 'FindServiceController.php';

$helper = new Helper();

$routes = [
	'/get/dir/path/:path_type' => 'FileManager@getPathToSystem',
	'/scan/dir/:scan_type' => 'FileManager@selectScanDir',
	'/file/content/get' => 'FileManager@loadFileContent',
	'/find/text' => 'FindController@findInit',
];

$router = new Router($routes, $helper);

try {
	$response = $router->run();
} catch (\Exception $e) {
	$errorMessage = $e->getMessage();
	$response['error'] = $errorMessage;
}

die(json_encode($response, true));
