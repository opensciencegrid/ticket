<?php

//init zend framework
//set_include_path('lib/zf/library' . PATH_SEPARATOR . get_include_path());  
//set_include_path('/usr/local/ZendFramework-1.12.3/library' . PATH_SEPARATOR . get_include_path());
set_include_path('app/models' . PATH_SEPARATOR . get_include_path());  
set_include_path('app/controls' . PATH_SEPARATOR . get_include_path());  
set_include_path('/usr/share/php/Google/src' . PATH_SEPARATOR . get_include_path());
require_once "Zend/Loader/Autoloader.php";
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->setFallbackAutoloader(true);

//check to make sure our site installation is done
if(!file_exists("config.php")) {
    echo "please create site specific config.php";
    exit;
}
if(!file_exists(".htaccess")) {
    echo ".htaccess file doesn't exist. please create";
    exit;
}

//load our stuff
require_once("config.php");
require_once("app/views/helper.php");
require_once("app/base.php");

//bootstrap
try {
    Zend_Session::start();

    //set php config
    ini_set('error_log', config()->error_logfile);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('display_startup_errors', 1);
    ini_set('default_charset', 'UTF-8');
    ini_set('default_socket_timeout', 120);
    date_default_timezone_set("UTC");//this will be later modified again (if user has valid timezone)

    remove_quotes();
    setup_logs();
    greet();

    
    if($_REQUEST["action"]=="logout"){
      //Zend_Session::destroy();
      //  header("locationa: https://ticket-dev.grid.iu.edu");
      //  $oidc->signOut();
      unset($_SESSION['access_token']); 

      $_SESSION["signout"]="1";   
      //  $oidc->signOut($_SESSION["access_token"],"https://ticket-dev.grid.iu.edu"); \
          
      header("location: https://ticket-dev.grid.iu.edu"); 
      exit();
    }

    cert_authenticate();
    /*
    if(!date_default_timezone_set(user()->getTimeZone())) {
        message("WARNING", "Your timezone '".user()->getTimeZone()."' is not valid. Please try updating to location based timezone such as 'America/Chicago' via OIM profile. Reverting to UTC.");
        elog(user()->dn. " who is using ".user()->getTimeZone()." was asked to update to location based timezone");
    }
    */
    error_reporting(E_ALL | E_STRICT);

} catch(exception $e) {
    header("HTTP/1.0 500 Internal Server Error");
    echo "Boot Error";
    echo "<pre>".$e->getMessage()."</pre>";
    elog($e->getMessage());
    exit;
}

//log access
try {
    $data = new Data();
    $data->logAccess();
} catch(exception $e) {
    //continue processing
    wlog("failed to log access - skipping... : ".$e->getMessage());
}

//dispatch
slog("---------dispatching--------");
$frontController = Zend_Controller_Front::getInstance();

//add ticket viewer shortcut
$route = new Zend_Controller_Router_Route_Regex('(\d+)',
    array('controller'=>'viewer', 'action'=>'index'),
    array(1=>'id')
);
$frontController->getRouter()->addRoute('call', $route);

$frontController->setControllerDirectory('app/controls');
$frontController->dispatch();
slog("---------all done-----------");
