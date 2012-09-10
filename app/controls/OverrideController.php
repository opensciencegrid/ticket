<?

class OverrideController extends BaseController
{ 
    public function indexAction()
    {
        user()->check("admin");

        $this->view->page_title = "Assignment Override";
        $this->view->menu_selected = "user";
        $this->view->submenu_selected = "override";

        //pull current override table
        $model = new Override();
        $this->view->overrides = $model->get();

        $model = new Schema();
        $this->view->users = $model->getusers();
    }

    public function submitAction()
    {
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

        message("success", "Successfully updated the assignment rule");
        $this->_redirect("override");
        $this->render("none");
    }

}
