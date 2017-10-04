<?php

function isbot()
{
    //if user doesn't provide HTTP_USER_AGENT, consider bot
    if(!isset($_SERVER["HTTP_USER_AGENT"])) {
        return true;
    }

    foreach(config()->botlist as $bot) {
        if(ereg($bot, $_SERVER['HTTP_USER_AGENT'])) {
            slog("Detected Bot - ".$_SERVER['HTTP_USER_AGENT']);
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
    $guest = new User($_SESSION["user"]);
    Zend_Registry::set("user", $guest);
    slog("guest access from ".$_SERVER["REMOTE_ADDR"]);
    }
  /* 


  if($_SESSION['signout']!=""){
    //  $oidc->signOut($_SESSION["access_token"],"https://ticket-dev.grid.iu.edu");
  }
  print $_SESSION['email']."<-- email<br>";

  print $_SESSION['access_token']."<-- session token<br>";
  print $_SESSION['family_name']."<- family name<br>";
  print $_SESSION['idp']."<- idp <br>";
  print $_SESSION['idp_name']."<- idp name<br>";
  
     if($_SESSION['access_token']==""){


  
  
  //   session_start();

  
  $oidc->addScope("openid");
  $oidc->addScope("email");   
  $oidc->addScope("profile");
  $oidc->addScope("org.cilogon.userinfo");

  $oidc->authenticate();                                                                                                                       
                       
  $name = $oidc->requestUserInfo('email');                                                                                                     
                                 
  print "<pre>";                                                                                                                               
  print_r($oidc);                                                                                                                              
  print "</pre>";     
   
  if (isset($_REQUEST['code'])) {                                                                                                              
                             
    $_SESSION['access_token'] = $oidc->getAccessToken();                                                                                       
    $_SESSION['email'] = $oidc->requestUserInfo('email');                                                                                       
    $_SESSION['family_name'] = $oidc->requestUserInfo('family_name');
    $_SESSION['given_name'] = $oidc->requestUserInfo('given_name');
    $_SESSION['idp'] = $oidc->requestUserInfo('idp');
    $_SESSION['idp_name'] = $oidc->requestUserInfo('idp_name');
  }  
     }else{
     if($_SESSION['name']!=""){                                
     $_SESSION['email'] = $_SESSION['name'];
    }
    
  }
  */
      
  if(isset($_SESSION["email"])){
            $dn = $_SESSION["email"];

            //apply dn override (for debugging)
            if(isset(config()->dn_override[$dn])) {
                $override = config()->dn_override[$dn];
                $dn = $override;
                slog("Overriding DN to $dn");
            }

            $user = new User($dn);
            if(is_null($user->getPersonID())) {
                //not yet registered?
                Zend_Registry::set("unregistered_dn", $dn);
                _setguest();
            } else if($user->isDisabled()) {
                Zend_Registry::set("disabled_dn", $dn);
                _setguest();
            } else {
                Zend_Registry::set("user", $user);
                slog("authenticated:".$user->getPersonName()."($dn)". " from ".$_SERVER["REMOTE_ADDR"]);
            }
        } else {
            //no client cert provided
            _setguest();
        }
  
   _setguest(); 
}

// shorthand
function user() { return Zend_Registry::get("user"); }
