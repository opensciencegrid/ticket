<?

class MyticketsController extends Zend_Controller_Action 
{ 
    public function init()
    {
        $this->view->submenu_selected = "admin";
        $this->view->page_title = "My Tickets";
    }

    public function lookupFPID($id) {
        $aka_model = new AKA();
        $model = new Schema();
        $teams = $model->getteams();

        //replace members (echism,kagross,cpipes) to more usable array
        foreach($teams as $team) {
            $members = explode(",", $team->members);
            foreach($members as $member) {
                if($id == $member) {
                    return array($member, $aka_model->lookupName($member));
                }
            }
        }
        //use rob by default..
        return array("rquick", "Rob Quick");
    }

    public function indexAction()
    {
        if(!user()->allows("admin")) {
        //if(!in_array(role::$goc_admin, user()->roles)) {
            $this->render("error/access", null, true);
            return;
        }

        if(!isset($_REQUEST["assignee"])) {
            list($id, $name) = array(null, "All");
        } else {
            list($id, $name) = $this->lookupFPID($_REQUEST["assignee"]);
        }

        try {
            $model = new Tickets();
            $this->view->page_title = "Tickets for $name";
            $this->view->assignee = $id;

            //assigned tickets
            $closed_status = "('Closed', '_DELETED_', '_SOLVED_', 'Resolved')";
            if($id !== null) {
                $query = "WHERE mrstatus not in $closed_status and mrassignees like '%".$this->view->assignee."%'";
            } else {
                $query = "WHERE mrstatus not in $closed_status";
            }
            $this->view->assigned_tickets = $model->dosearch($query);

            //recently closed tickets
            $this->view->closed_days = config()->myticket_closed_days;
            $recent_date = date("Y-m-d G:i:s", time()-3600*24*$this->view->closed_days);
            if($id !== null) {
                $query = "WHERE mrstatus in $closed_status and mrassignees like '%".$this->view->assignee."%' and mrupdatedate > '$recent_date'";
            } else {
                $query = "WHERE mrstatus in $closed_status and mrupdatedate > '$recent_date'";
            }
            $this->view->closed_tickets = $model->dosearch($query);

        } catch (SoapFault $e) {
            elog("SoapFault detected while ViewController:loaddetail()");
            elog($e->getMessage());
            $this->view->content = $e->getMessage();
            $this->render("error/error", null, true);
        }
    }

} 
