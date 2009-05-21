<?php

//init zend framework
set_include_path('lib/zf/library' . PATH_SEPARATOR . get_include_path());  
set_include_path('app/models' . PATH_SEPARATOR . get_include_path());  
set_include_path('app/controls' . PATH_SEPARATOR . get_include_path());  
require_once "Zend/Loader.php"; 
Zend_Loader::registerAutoload(); 

//load our stuff
require_once("config.php");
require_once("app/views/helper.php");
require_once("app/base.php");

//bootstrap
try {
    Zend_Session::start();

    remove_quotes();
    setup_logs();
    greet();
    cert_authenticate();

    //set php config
    ini_set('error_log', config()->error_logfile);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL | E_STRICT);
    date_default_timezone_set("UTC");

} catch(exception $e) {
    //when a catastrohpic failure occure (like disk goes read-only..) emailing is the only way we got..
    mail(config()->elog_email_address, "[gocticket] Caught exception during bootstrap", $e, "From: ".config()->email_from);
    header("HTTP/1.0 500 Internal Server Error");
    echo $e;
    exit;
}

//dispatch
$frontController = Zend_Controller_Front::getInstance();
$frontController->setControllerDirectory('app/controls');
$frontController->dispatch(); 

