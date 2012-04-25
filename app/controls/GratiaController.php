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

        //Assign the Gratia Dev SC and set Destination VO to gratia_support_and_dev for tickets that fall under the following conditions: 
        //Tickets related specifically to development and maintenance of the Gratia system *as distinct from* operation of the OSG collectors and reporting of sites thereto (which is a Gratia Ops matter). Bug reports against probes or collectors, or requests for help with regional / local collectors are suitable subjects for tickets to Gratia Dev. 
        $footprint->addAssignee("gratia");
        //$footprint->setDestinationVO("GRATIA"); //Kyle requested that for now, we set this to GRATIA instead of gratia_support_and_dev

/*
        //for central gratia operational issues - assign the GRATIA SC and set destination VO to GRATIA (not fermigrid)
        $footprint->addAssignee("????");
*/
    }
} 
