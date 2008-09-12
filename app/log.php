<?

$g_starttime = microtime(true);

function clearlog()
{
    unlink(config()->logfile);
    unlink(config()->error_logfile);
    unlink(config()->audit_logfile);
}

function setup_logs()
{
    //setup logs
    $logger = new Zend_Log();
    $writer = new Zend_Log_Writer_Stream(config()->logfile);
    $logger->addWriter($writer);
    Zend_Registry::set("logger", $logger);

    slog('----------------------------------------------------------------------');
    slog('RSV Viewer session starting.. '.$_SERVER["REQUEST_URI"]);

    dlog(print_r($_REQUEST, true));
}

function log_format($str)
{
    global $g_starttime;
    if($str === null) $str = "[null]";
    $time = microtime(true) - $g_starttime;
    $str = getmypid()."@".round($time, 3)." ".$str;

    return $str;
}

//debug log
function dlog($str)
{
    if(config()->debug) {
        Zend_Registry::get("logger")->log(log_format($str), Zend_Log::DEBUG);
    }
}

//error log
function elog($str)
{
    Zend_Registry::get("logger")->log(log_format($str), Zend_Log::ERR);

    if(config()->elog_email) {
        error_log($str, 1,config()->elog_email_address);
    }

    //send to error_log as well
    // 0) message is sent to PHP's system logger, using the Operating System's 
    // system logging mechanism or a file, depending on what the error_log  
    // configuration directive is set to. This is the default option. 
    error_log($str, 0); 
}

//standard log
function slog($str)
{
    Zend_Registry::get("logger")->log(log_format($str), Zend_Log::INFO);
}




