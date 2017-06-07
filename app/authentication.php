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

function curPageURL()
{
  $pageURL = 'http';
  if ($_SERVER["HTTPS"] == "on") {
    $pageURL .= "s://";
    if ($_SERVER["SERVER_PORT"] != "443") {
      $pageURL .= $_SERVER["HTTP_HOST"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI\
"];
    } else {
      $pageURL .= $_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"];
    }
  } else {
    $pageURL .= "://";
    if ($_SERVER["SERVER_PORT"] != "80") {
      $pageURL .= $_SERVER["HTTP_HOST"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI\
"];
    } else {
      $pageURL .= $_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"];
    }
  }
  return $pageURL;
}//END CURRENT URL FUNCTION                                                               
*/


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
 
  //  print $_SESSION['access_token'];

  //  ksort($_SERVER);

    $addr = $_SERVER["OIDC_CLAIM_email"]; 
       $fname= $_SERVER["OIDC_CLAIM_family_name"]; 
       $sub = $_SERVER["OIDC_CLAIM_sub"];
  print $addr."<-- OIDC_CLAIM_email<br>";
   print $fname."<-- OIDC_CLAIM_familyname<br>";
   print $sub."<-- OIDC_CLAIM_familyname<br>";
   $_SESSION['access_token'] = $_SERVER["OIDC_CLAIM_access_token"];
   $_SESSION['email'] = $_SERVER["OIDC_CLAIM_email"];
     $_SESSION['family_name'] = $_SERVER["OIDC_CLAIM_family_name"];
     $_SESSION['given_name'] = $_SERVER["OIDC_CLAIM_given_name"];                                                                                                                           
     $_SESSION['idp'] = $_SERVER["OIDC_CLAIM_idp"];
     $_SESSION['idp_name'] = $_SERVER["OIDC_CLAIM_idp_name"];

  // foreach ($_SERVER as $key => $value) {
      //  if ((preg_match(/^OIDC/,$key)) ||
      //  (preg_match(/^REMOTE_USER/,$key))) {
  //    echo "<tr><td>$key</td><td>$value</td></tr>\n";
      //}
  // }
  


  /*  
  require "/usr/local/ticket/vendor/autoload.php";
  $oidc = new OpenIDConnectClient('https://cilogon.org',
                                  'myproxy:oa4mp,2012:/client_id/409f337578d64f4d77c093d0680affa1',
                                  'kexZWPeIAA2kFgelPN-6DpPIXO-MKiXP9Q1EmBC_4A-LRroyUf2pqYB9oLNJjfJp4Yr5-iFFXkJwkJ_Qbth9nQ');

  if($_SESSION['signout']!=""){
    //  $oidc->signOut($_SESSION["access_token"],"https://ticket-dev.grid.iu.edu");
  }
  print $_SESSION['email']."<-- email<br>";

  print $_SESSION['access_token']."<-- session token<br>";
  print $_SESSION['family_name']."<- family name<br>";
  print $_SESSION['idp']."<- idp <br>";
  print $_SESSION['idp_name']."<- idp name<br>";
  
     if($_SESSION['access_token']==""){

  */

  

  //   session_start();
  # cilogon
  # myproxy:oa4mp,2012:/client_id/409f337578d64f4d77c093d0680affa1
  # kexZWPeIAA2kFgelPN-6DpPIXO-MKiXP9Q1EmBC_4A-LRroyUf2pqYB9oLNJjfJp4Yr5-iFFXkJwkJ_Qbth9nQ
    /*
  Here is your client identifier: 

  myproxy:oa4mp,2012:/client_id/409f337578d64f4d77c093d0680affa1

  Here is your client secret: 

  kexZWPeIAA2kFgelPN-6DpPIXO-MKiXP9Q1EmBC_4A-LRroyUf2pqYB9oLNJjfJp4Yr5-iFFXkJwkJ_Qbth9nQ

   
  $oidc = new OpenIDConnectClient('https://accounts.google.com',
                               '860986807471-mce3pdl0odonb3nlmvquo32sr290objk.apps.googleusercontent.com',
 				  '-S-qO025USlcfVVdAzaN9dxv');*/
  /*
  $oidc = new OpenIDConnectClient('https://cilogon.org',
				  'myproxy:oa4mp,2012:/client_id/409f337578d64f4d77c093d0680affa1',
				  'kexZWPeIAA2kFgelPN-6DpPIXO-MKiXP9Q1EmBC_4A-LRroyUf2pqYB9oLNJjfJp4Yr5-iFFXkJwkJ_Qbth9nQ');
  

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
  /*
    $authenticated = $_SESSION['CAS'];
    //      echo $authenticated."<-- authen" ;                                    
    $casurl = "https://ticket-dev.grid.iu.edu";//curPageURL();                    
    //$casurl = curPageURL();                                                     
    //send user to CAS login if not authenticated                                       
    if (!$authenticated) {
      $_SESSION['LAST_SESSION'] = time(); // update last activity time stamp            
      $_SESSION['CAS'] = true;
      header("Location: https://cas.iu.edu/cas/login?cassvc=IU&casurl=$casurl");
      exit;
    }
    if ($authenticated) {

      //echo $authenticated."--".$_SESSION['user']."-- resp". $_SESSION['access_resp'];
      if (isset($_GET["casticket"])) {

	//set up validation URL to ask CAS if ticket is good                            
	$_url = 'https://cas.iu.edu/cas/validate';
	$cassvc = 'IU'; //search kb.indiana.edu for "cas application code" to determinecode to use here in place of "appCode"                                                   

        $params = "cassvc=$cassvc&casticket=$_GET[casticket]&casurl=$casurl";
        $urlNew = "$_url?$params";

        //CAS sending response on 2 lines. First line contains "yes" or "no". If "yes", second line contains username (otherwise, it is empty).                                  
        $ch = curl_init();
        $timeout = 5; // set to zero for no timeout                                     
        curl_setopt ($ch, CURLOPT_URL, $urlNew);
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        ob_start();
        curl_exec($ch);
        curl_close($ch);
        $cas_answer = ob_get_contents();
        ob_end_clean();
        //split CAS answer into access and user                                         
        list($access,$user) = split("\n",$cas_answer,2);
        $access = trim($access);
        $user = trim($user);

        $_SESSION['access_resp'] = $access;     //set user and session variable if CAS says YES                                                                                  
        if ($access == "yes") {

        //////// LDAP implementation                                                  
        //Connect                                                                     

           $ds= ldap_connect(config()->sso_ldap_host);
 
           $baseDN= config()->sso_ldap_basedn;
           if (config()->sso_ldap_host){
              ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
              ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
              ldap_bind($ds,config()->sso_username ,config()->sso_pw) or die("Could not connect to LDAP server.");
           }
                                                                                          
           $filter = "(sAMAccountName=".$user.")";
                                                                                          
           $sr = ldap_search($ds,$baseDN,$filter);
           $addG = ldap_get_entries($ds,$sr);

           $username = $addG[0]['cn'][0];           $phone = $addG[0]['telephonenumber'][0];

           for($i=0;$i<sizeof($addG[0]['memberof']);$i++){
              $memberof = $addG[0]['memberof'][$i];
              $member_array = explode(",",$memberof);
              $member= $member_array[0];

             if($member=="CN=BL-CITO-MSCH-GraduateStudies-GradApps"){
	       $deparment_pass="yes";
             }
          }

         if($deparment_pass=="yes"  ){
           $_SESSION['user'] = $user;
           Zend_Registry::set("user", $user);

        }else{ //END GET CAS TICKET                                                
           session_destroy();
	   echo '<META HTTP-EQUIV="Refresh" Content="0; URL=https://cas.iu.edu/cas/login?cassvc=IU&casurl='.$casurl.'">';
  
	   //     header("Location: https://cas.iu.edu/cas/login?cassvc=IU&casurl=".$casurl."");
	   die();
        }

	}//END SESSION USER                                                           
    } else if (!isset($_SESSION['user'])) { //END GET CAS TICKET                      
       echo '<META HTTP-EQUIV="Refresh" Content="0; URL=https://cas.iu.edu/cas/login?cassvc=IU&casurl='.$casurl.'">';
        session_destroy();
	//	header("Location: https://cas.iu.edu/cas/login?cassvc=IU&casurl=".$casurl."");
	die();
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

//shorthand
function user() { return Zend_Registry::get("user"); }
