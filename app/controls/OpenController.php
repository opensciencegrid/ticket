<?

class OpenController extends Zend_Controller_Action 
{ 
    public function init()
    {
        $this->view->submenu_selected = "open";
    }

    public function indexAction() 
    { 
        $this->render();
    }
} 
