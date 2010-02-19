<?

require_once("log.php");
require_once("authentication.php");
require_once("db.php");

function greet()
{
    slog('----------------------------------------------------------------------');
    slog(config()->app_name. ' session starting.. '.$_SERVER["REQUEST_URI"]);
    if(config()->debug) { 
        slog("Dumping request object");
        slog(print_r($_REQUEST, true)); 
        slog("HTTP_USER_AGENT");
        slog(print_r($_SERVER['HTTP_USER_AGENT'], true)); 
    } else {
        //if not debug, only dump POST object
        slog(print_r($_POST, true)); 
    }
}

function remove_quotes()
{
    if(  ( function_exists("get_magic_quotes_gpc") && get_magic_quotes_gpc() ) || ini_get('magic_quotes_sybase')  ){
        foreach($_GET as $k => $v) $_GET[$k] = stripslashes_deep($v);
        foreach($_POST as $k => $v) $_POST[$k] = stripslashes_deep($v);
        foreach($_COOKIE as $k => $v) $_COOKIE[$k] = stripslashes_deep($v);
        foreach($_REQUEST as $k => $v) $_REQUEST[$k] = stripslashes_deep($v);
    }
}

function stripslashes_deep($v)
{
    if(is_array($v)) {
        $ret = array();
        foreach($v as $key=>$item) {
            $ret[$key] = stripslashes_deep($item);
        }
        return $ret;
    } else {
        return stripslashes($v);
    }
}

function sendSMS($users, $subject, $body)
{
    $recipient = "";
    foreach($users as $user) {
        if(isset(config()->sms_address[$user])) {
            if($recipient != "") {
                $recipient .= ", ";
            }
            $recipient .= config()->sms_address[$user];
        } else {
            elog("couldn't find user $user in sms_address configuration");
        }
    }
    $header = "From: ". config()->error_from."\r\n";
    mail($recipient, $subject, $body, $header);

    slog("Sent SMS notification to $recipient user:".print_r($users, true));
}

function signedmail($to, $from, $subject, $body, $header = "")
{
    $key = config()->signed_email_key;
    $cert = config()->signed_email_cert;

    if(!file_exists($key)) {
        throw new exception("Couldn't find certificate key $key");
    }
    if(!file_exists($cert)) {
        throw new exception("Couldn't find certificate $key");
    }

    //store the original body (so that openssl can process it)
    $original_body = tempnam("/tmp", "gocticket");
    $handle = fopen($original_body, "w");
    fwrite($handle, $body);
    fclose($handle);

    //sign the body
    $signed_body = tempnam("/tmp", "gocticket");
    $command = "openssl smime -sign -text -inkey $key -signer $cert -in $original_body | dos2unix > $signed_body";
    slog($command);
    system($command);

    //insert the signed content and my header to $header
    $header .= "From: $from\r\n";
    $header .= file_get_contents($signed_body);

    //send everything from $header
    if(!mail($to, $subject, "", $header)) {
        elog("Failed to send email");
        throw new exception("Failed to send email");
    }
}

function fpCall($function, $param)
{
    $client = new SoapClient(null, array('location' => config()->fp_soap_location, 'uri' => config()->fp_soap_uri, 'connection_timeout'=>5));
    $msg = "";
    for($i = 0; $i < 3; $i++) {
        try {
            $ret = $client->__soapCall($function, $param);
            return $ret;
        } catch (SoapFault $e) {
            $msg = $e->getMessage();

            /*
            if($msg == "Could not connect to host") {
                //this happens when server is gone - due to like n/w issue -- bail!
                elog("fpcall: SoapFault (bailing) -- ".$msg);
                throw new exception("Underlying system that this application depends on is having an issue : ".$msg);
            } 
            */
            elog("fpcall: SoapFault (trying again) -- ".$msg);

            if(strpos($msg, "Wide character in subroutine entry at /usr/local/footprints//cgi/SUBS/send_mail.pl") !== false) {
                elog("ignoring this error - this is a known FP issue - fpcall itself was ok");
                return;
            }

            sleep(1);
        }
    }
    elog("Soap called failed too many times.. quitting");
    throw new exception("Couldn't contact IU Footprint server : ".$msg);
}


