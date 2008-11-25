<?

class ViewController extends Zend_Controller_Action 
{ 
    public function init()
    {
        $this->view->submenu_selected = "view";
    }    

    public function indexAction() 
    { 
    }
} 
