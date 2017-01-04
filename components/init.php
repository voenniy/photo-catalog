<?php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\ErrorHandler;
use Monolog\Registry;

Logger::setTimezone(new DateTimeZone('Europe/Moscow'));
$log = new Logger('photos');

if(!dir('./tmp')){
    mkdir('./tmp', 0777);
}

$logFile = './tmp/photos.log';
if(file_exists($logFile)){
    unlink($logFile);
}

$log->pushHandler(new StreamHandler($logFile));

Registry::addLogger($log);
ErrorHandler::register($log);

logger('run!');

function logger($message, array $context = []){
    Monolog\Registry::photos()->info($message, $context);
}