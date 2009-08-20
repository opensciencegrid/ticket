<?

class SearchController extends Zend_Controller_Action 
{ 
    public function init()
    {
        $this->view->submenu_selected = "search";
    }    

    public function indexAction() 
    { 
    }
} 
