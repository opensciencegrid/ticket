<?

require_once("log.php");
require_once("authentication.php");
require_once("db.php");

function greet()
{
    slog('----------------------------------------------------------------------');
    slog(config()->app_name. ' session starting.. '.$_SERVER["REQUEST_URI"]);
    slog("POST: ".print_r($_POST, true));
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
    $Name = config()->app_name;
    $email = "hayashis@indiana.edu"; //senders e-mail adress
    $header = "From: ". $Name . " <" . $email . ">\r\n";
    mail($recipient, $subject, $body, $header);

    dlog("Sent SMS notification to $recipient user:".print_r($users, true));
}

function signedmail($to, $subject, $body, $header = "")
{
    $key = config()->signed_email_key;
    $cert = config()->signed_email_cert;

    //store the original body (so that openssl can process it)
    $original_body = tempnam("/tmp", "gocticket");
    $handle = fopen($original_body, "w");
    fwrite($handle, $body);
    fclose($handle);

    //sign the body
    $signed_body = tempnam("/tmp", "gocticket");
    system("openssl smime -sign -text -inkey $key -signer $cert -in $original_body | dos2unix > $signed_body");

    //insert the signed content and my header to $header
    $header .= "\r\nFrom: " . config()->signed_email_from."\r\n";
    $header .= file_get_contents($signed_body);

    //send everything from $header
    slog($header);
    return mail($to, $subject, "", $header);
}

