<?

class OverrideController extends BaseController
{ 
    public function indexAction()
    {
        $this->view->submenu_selected = "admin";
        if(!user()->allows("admin")) {
            $this->render("error/access", null, true);
            return;
        }

        //pull current override table
        $model = new Override();
        $this->view->overrides = $model->get();

        $model = new Schema();
        $this->view->users = $model->getusers();
    }

    public function submitAction()
    {
        $this->view->submenu_selected = "admin";
        if(!user()->allows("admin")) {
            $this->render("error/access", null, true);
            return;
        }

        //construct records
        $overrides = array();
        if(isset($_REQUEST["rec_from"])) {
            $froms = $_REQUEST["rec_from"];
            $tos = $_REQUEST["rec_to"];
            foreach($froms as $id => $from) {
                if(trim($from) == "" || trim($tos[$id]) == "") {
                    continue;
                }
                $overrides[$from] = $tos[$id];
            }
        }

        //update override table
        $model = new Override();
        $model->set($overrides);

        addMessage("Updated the assignment rule");
        $this->_redirect("admin");
        $this->render("none");
    }

}
