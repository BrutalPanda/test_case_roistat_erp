<?php
require_once 'src/AccessLogController.php';

$fileName = array_key_exists(1, $argv) ? $argv[1] : '';

$accessLogController = new AccessLogController();
$result = $accessLogController->processLogFile($fileName);

echo $result;