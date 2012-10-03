<?

class OpenController extends Zend_Controller_Action 
{ 
    public function init()
    {
        header("Location: submit", true, 301);
        exit;
/*
        $this->view->submenu_selected = "open";
*/
    }

    public function indexAction() 
    { 
        $this->render();
    }
} 
