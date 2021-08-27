<?php
require_once 'src/AccessLogController.php';

$fileName = count($argv) > 1 ? $argv[1] : '';

$accessLogController = new AccessLogController();
$result = $accessLogController->processLogFile($fileName);

echo $result;