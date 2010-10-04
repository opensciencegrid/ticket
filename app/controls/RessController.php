<?

class RessController extends GOCServiceController
{ 
    function init()
    {
        $this->view->submenu_selected = "open";
        $this->form_title = "Ress Bugs / Requests";
    }

    function addElements($form) {
        //nothing to add
    }
    function processFields($form, $footprint) {
        $model = new NextAssignee();
        $gocid = $model->getNextAssignee();
        $footprint->addAssignee($gocid, true); //reset assignee first
        //$footprint->addAssignee("ress-ops"); //Fermigrid Ops

        $footprint->addAssignee("ress-ops"); 
        $footprint->setDestinationVO("ReSS_Development");
    }
} 
