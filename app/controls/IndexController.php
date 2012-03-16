<?

class IndexController extends Zend_Controller_Action 
{ 
    public function indexAction() 
    { 
        $response = $this->getResponse();
        $response->setRedirect(fullbase()."/submit");
    }
} 
