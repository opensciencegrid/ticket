<?

class GratiaController extends GOCServiceController
{ 
    function init()
    {
        $this->view->submenu_selected = "open";
        $this->form_title = "Gratia Issues / Requests";
    }

    function addElements($form) {
        //nothing to add
    }
    function processFields($form, $footprint) {
        $model = new NextAssignee();
        $gocid = $model->getNextAssignee();
        $footprint->addAssignee($gocid, true); //reset assignee first
        $footprint->addAssignee("fnal"); //Fermigrid Ops?
    }
} 
