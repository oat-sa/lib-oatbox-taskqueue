<?php

use oat\Taskqueue\Action\InitRdsQueue;
use oat\oatbox\service\ServiceManager;
use oat\oatbox\action\ActionService;
$parms = $argv;
array_shift($parms);

if (count($parms) != 2) {
	echo 'Usage: '.__FILE__.' TAOROOT PERSISTENCE'.PHP_EOL;
	die(1);
}

$root = rtrim(array_shift($parms), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
$rawStart = $root.'tao'.DIRECTORY_SEPARATOR.'includes'.DIRECTORY_SEPARATOR.'raw_start.php';

if (!file_exists($rawStart)) {
    echo 'Tao not found at "'.$rawStart.'"'.PHP_EOL;
    die(1);
}

require_once $rawStart;

$persistenceId = array_shift($parms);

$peristence = common_persistence_SqlPersistence::getPersistence($persistenceId);

$actionService = ServiceManager::getServiceManager()->get(ActionService::SERVICE_ID);
$action = $actionService->resolve(InitRdsQueue::class);
$report = $action->__invoke(array($persistenceId));
echo tao_helpers_report_Rendering::renderToCommandline($report);