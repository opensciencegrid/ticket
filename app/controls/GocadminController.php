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
        if(!user()->allows("admin")) {
            $this->render("error/access", null, true);
            return;
        }

        $this->render();
    }
} 
