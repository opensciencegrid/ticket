<?php


/* phpinfo sample
HTTPS   on
SSL_VERSION_INTERFACE   mod_ssl/2.2.8
SSL_VERSION_LIBRARY     OpenSSL/0.9.8b
SSL_PROTOCOL    TLSv1
SSL_COMPRESS_METHOD     NULL
SSL_CIPHER      DHE-RSA-AES256-SHA
SSL_CIPHER_EXPORT       false
SSL_CIPHER_USEKEYSIZE   256
SSL_CIPHER_ALGKEYSIZE   256
SSL_CLIENT_VERIFY       SUCCESS
SSL_CLIENT_M_VERSION    3
SSL_CLIENT_M_SERIAL     6093
SSL_CLIENT_V_START      Jun 23 19:25:12 2008 GMT
SSL_CLIENT_V_END        Jun 23 19:25:12 2009 GMT
SSL_CLIENT_V_REMAIN     337
SSL_CLIENT_S_DN         /DC=org/DC=doegrids/OU=People/CN=Soichi Hayashi 461343
SSL_CLIENT_S_DN_OU      People
SSL_CLIENT_S_DN_CN      Soichi Hayashi 461343
SSL_CLIENT_I_DN         /DC=org/DC=DOEGrids/OU=Certificate Authorities/CN=DOEGrids CA 1
SSL_CLIENT_I_DN_OU      Certificate Authorities
SSL_CLIENT_I_DN_CN      DOEGrids CA 1
SSL_CLIENT_A_KEY        rsaEncryption
SSL_CLIENT_A_SIG        sha1WithRSAEncryption
SSL_SERVER_M_VERSION    3
SSL_SERVER_M_SERIAL     00
SSL_SERVER_V_START      Dec 18 16:57:01 2007 GMT
SSL_SERVER_V_END        Dec 17 16:57:01 2008 GMT
SSL_SERVER_S_DN         /C=US/ST=Indiana/L=Bloomington/O=Indiana University/OU=Grid Operations Center/CN=oim-dev.grid.iu.edu/emailAddress=thomlee@indiana.edu
SSL_SERVER_S_DN_C       US
SSL_SERVER_S_DN_ST      Indiana
SSL_SERVER_S_DN_L       Bloomington
SSL_SERVER_S_DN_O       Indiana University
SSL_SERVER_S_DN_OU      Grid Operations Center
SSL_SERVER_S_DN_CN      oim-dev.grid.iu.edu
SSL_SERVER_S_DN_Email   thomlee@indiana.edu
SSL_SERVER_I_DN         /C=US/ST=Indiana/L=Bloomington/O=Indiana University/OU=Grid Operations Center/CN=oim-dev.grid.iu.edu/emailAddress=thomlee@indiana.edu
SSL_SERVER_I_DN_C       US
SSL_SERVER_I_DN_ST      Indiana
SSL_SERVER_I_DN_L       Bloomington
SSL_SERVER_I_DN_O       Indiana University
SSL_SERVER_I_DN_OU      Grid Operations Center
SSL_SERVER_I_DN_CN      oim-dev.grid.iu.edu
SSL_SERVER_I_DN_Email   thomlee@indiana.edu
SSL_SERVER_A_KEY        rsaEncryption
SSL_SERVER_A_SIG        md5WithRSAEncryption
SSL_SESSION_ID  516AC461A299606A1CD870F70FE02EA04B83841919B023C4B50F6E3D469B94C1
SSL_SERVER_CERT         XXXXXXXXXXXXXXXX
SSL_CLIENT_CERT         XXXXXXXXXXXXXXXX
*/

function isbot()
{
    foreach(config()->botlist as $bot) {
        if(ereg($bot, $HTTP_USER_AGENT)) {
            return true;
        }
    }
    return false; 
}

function islocal()
{
    if($_SERVER["REMOTE_ADDR"] == $_SERVER["SERVER_ADDR"]) {
        return true;
    }
    return false;
}

//do the db lookup against SSL cert. Store User object to the registry.
//I am not sure if we are going to store this on session instead, 
//and do authentication if it's not done already..
function cert_authenticate()
{
    function _setguest() {
        $guest = new User(null);
        Zend_Registry::set("user", $guest);
        slog("guest access from ".$_SERVER["REMOTE_ADDR"]);
    }

    if(!isset($_SERVER["HTTPS"])) {
        if(config()->force_https and !isbot()) {
            //reload as https (if not bot)
            $SERVER_NAME=$_SERVER["SERVER_NAME"];
            $REQUEST_URI=$_SERVER["REQUEST_URI"];
            slog("Forwarding to HTTPS");
            header ("Location: https://$SERVER_NAME$REQUEST_URI");
            exit;
        } else {
            //can't authenticate through http - so let's just assume a guest user
            _setguest();
        }
    } else {
        if(isset($_SERVER["SSL_CLIENT_S_DN"])) {
            $dn = $_SERVER["SSL_CLIENT_S_DN"];
            $user = new User($dn);
            if($user->getPersonID()) {
                Zend_Registry::set("user", $user);
                slog("Authenticated User: ".$user->getPersonName());
            } else {
                //unknown, non-active, or expired cert?
                _setguest();
                slog("Authenticated User as a Guest");
            }
        } else {
            //no client cert provided
            _setguest();
        }
    }
}

//shorthand
function user() { return Zend_Registry::get("user"); }
