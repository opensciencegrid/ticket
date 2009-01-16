<?

class GroupController extends Zend_Controller_Action 
{ 
    public function init()
    {
        $this->view->submenu_selected = "admin";
        if(!in_array(role::$goc_admin, user()->roles)) {
            $this->render("error/404", null, true);
            return;
        }
    }    

    public function indexAction() 
    { 
        $this->view->groups = new SimpleXmlElement(file_get_contents("/tmp/gocticket.groupticket.xml"), LIBXML_NOCDATA);
    }
} 
