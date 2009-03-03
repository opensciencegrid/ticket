<?

class GocadminController extends BaseController
{ 
    public function init()
    {
        $this->view->submenu_selected = "gocadmin";
    }
    public function indexAction() 
    { 
        //only goc users are allowed
        if(!in_array(role::$goc_admin, user()->roles)) {
            $this->render("error/access", null, true);
            return;
        }

        $this->render();
    }
} 
