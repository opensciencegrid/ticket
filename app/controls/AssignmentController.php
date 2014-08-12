<?php

class AssignmentController extends BaseController
{ 
    public function indexAction()
    {
        user()->check("admin");

        $this->view->menu_selected = "user";
        $this->view->submenu_selected = "assignment";
        $this->view->page_title = "Assignment";

        //pull current override table
        $model = new NextAssignee();
        $this->view->config = $model->getConfig(false); //pull everything
        //get next assignee base on current info
        $this->view->next_a = $model->getNextAssignee();
        $this->view->next_reason = $model->getReason();

        $model = new Schema();
        $this->view->users = $model->getusers();

    }

    public function postAction()
    {
        /*
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

        */
        $postdata = file_get_contents("php://input");
        $assignees = json_decode($postdata);
        $model = new NextAssignee();
        $model->setConfig($assignees);

        message("success", "Successfully updated the assignment rule");
        $this->_helper->viewRenderer->setNoRender(true);
    }
}
