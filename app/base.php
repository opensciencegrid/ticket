<?

require_once("log.php");
require_once("authentication.php");
require_once("db.php");

function remove_quotes()
{
    if(  ( function_exists("get_magic_quotes_gpc") && get_magic_quotes_gpc() ) || ini_get('magic_quotes_sybase')  ){
        foreach($_GET as $k => $v) $_GET[$k] = stripslashes($v);
        foreach($_POST as $k => $v) $_POST[$k] = stripslashes($v);
        foreach($_COOKIE as $k => $v) $_COOKIE[$k] = stripslashes($v);
    }
}
