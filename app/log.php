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
    //setup standard logs
    $writer = new Zend_Log_Writer_Stream(config()->logfile);
    $logger = new Zend_Log();
    $logger->addWriter($writer);
    Zend_Registry::set("logger", $logger);

    //setup firebug log
    $writer = new Zend_Log_Writer_Firebug();
    $logger = new Zend_Log();
    $logger->addWriter($writer);
    Zend_Registry::set("fb_logger", $logger);
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
function dlog($obj)
{
    if(config()->debug) {
        if(is_string($obj)) {
            $obj = log_format($obj);
        } 
        Zend_Registry::get("fb_logger")->log($obj, Zend_Log::DEBUG);
    }
}

//error log
function elog($obj)
{
    if(is_string($obj)) {
        $obj = log_format($obj);
    } 
    Zend_Registry::get("logger")->log($obj, Zend_Log::ERR);

    //send to error_log as well
    // 0) message is sent to PHP's system logger, using the Operating System's 
    // system logging mechanism or a file, depending on what the error_log  
    // configuration directive is set to. This is the default option. 
    error_log("[ERR] ".$obj, 0); 
}

//warning log
function wlog($obj)
{
    if(is_string($obj)) {
        $obj = log_format($obj);
    } 
    Zend_Registry::get("logger")->log($obj, Zend_Log::WARN);

    //send to error_log as well
    // 0) message is sent to PHP's system logger, using the Operating System's 
    // system logging mechanism or a file, depending on what the error_log  
    // configuration directive is set to. This is the default option. 
    error_log("[WARN] ".$obj, 0); 
}

//standard log
function slog($obj)
{
    if(is_string($obj)) {
        $obj = log_format($obj);
    } 
    Zend_Registry::get("logger")->log($obj, Zend_Log::INFO);
}

//session message (in HTML format)
function addMessage($content)
{
    $message = new Zend_Session_Namespace("message");
    if(isset($message->content)) {
        $message->content .= $content;
    } else {
        $message->content = $content;
    }
}
function flushMessage() {
    $message = new Zend_Session_Namespace("message");
    if(isset($message->content)) {
        $content = $message->content;
        unset($message->content);
        return $content;
    }
    return "";
}
