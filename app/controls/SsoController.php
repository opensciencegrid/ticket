<?

class SsoController extends Zend_Controller_Action 
{ 
    public function indexAction() 
    { 
      // $response = $this->getResponse();
	//   $response->setRedirect(fullbase()."/sso");
      //  $this->render();
      if ($_REQUEST['code']=="" || $_REQUEST['code']==null) {                                                                                                  
 
	$_SESSION["ref_loc"] = $_SERVER['HTTP_REFERER'];

	elog("referrer: ". $_SESSION["ref_loc"]);
      }


    }
} 
