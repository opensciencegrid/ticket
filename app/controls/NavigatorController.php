<?

class NavigatorController extends Zend_Controller_Action 
{ 
    public function init()
    {
        $this->view->submenu_selected = "view";

        $this->view->sortby = "nad";
        dlog("settin sortby to ".$_REQUEST["sortby"]);
        if(isset($_REQUEST["sortby"])) {
            $this->view->sortby = $_REQUEST["sortby"];
        }

        $this->view->sortdir = "up";
        if(isset($_REQUEST["sortdir"])) {
            $this->view->sortdir = $_REQUEST["sortdir"];
        }

    }

    public function indexAction()
    {
        if(user()->allows("ticket_admin")) {
            $this->openassignAction();
        } else {
            $this->openAction();
        }
    }

    public function openAction()
    {
        try {
            $model = new Tickets();
            $tickets = $model->getopen();
            $this->view->activetab = "open";
            $this->view->tickets = $this->groupby("mrdest", $tickets);
            $this->render("index");
        } catch (SoapFault $e) {
            elog("SoapFault detected while ViewController:loaddetail()");
            elog($e->getMessage());
            $this->view->content = $e->getMessage();
            $this->render("error/error", null, true);
        }
    }
    public function openassignAction()
    {
        try {
            $model = new Tickets();
            $tickets = $model->getopen();
            $this->view->activetab = "openassign";
            $this->view->tickets  = $this->groupby("mrassignees", $tickets);
            $this->render("index");
        } catch (SoapFault $e) {
            elog("SoapFault detected while ViewController:loaddetail()");
            elog($e->getMessage());
            $this->view->content = $e->getMessage();
            $this->render("error/error", null, true);
        }
    }
    public function closeAction()
    {
        try {
            $model = new Tickets();
            $tickets = $model->getclosed(time()-3600*24*90);
            $this->view->activetab = "close";
            $this->view->tickets  = $this->groupby("mrdest", $tickets);
            $this->render("index");
        } catch (SoapFault $e) {
            elog("SoapFault detected while ViewController:loaddetail()");
            elog($e->getMessage());
            $this->view->content = $e->getMessage();
            $this->render("error/error", null, true);
        }
    }

    protected function groupby($type, $tickets) 
    {
        $aka_model = new AKA();
        
        $schema_model = new Schema();
        $teams = $schema_model->getteams();
        //find suppor centers
        $fp_scs = array();
        foreach($teams as $team) {
            if(
                $team->team == "OSG__bGOC__bSupport__bTeam" or
                $team->team == "OSG__bOperations__bInfrastructure" or 
                $team->team == "OSG__bGOC__bManagement" or
                $team->team == "OSG__bGOC__bService__bDesk" or
                $team->team == "OSG Security Coordinators" or
                $team->team == "OSG Storage Team") {
                $fp_scs = array_merge($fp_scs, split(",", $team->members));
            }
        }

        //group tickets by certain field id
        $grouped_tickets = array();
        foreach($tickets as $ticket) {
            $token = Footprint::parse($ticket->$type);
            if($type == "mrassignees") {
                //assignees are wierd
                foreach(split(" ", $token) as $a) {
                    if(strlen($a) >= 3 and strpos($a, "CC:") === 0) {
                        //ignore CCs
                        continue;
                    }

                    //ignore non-goc assignee er
                    if(!in_array($a, $fp_scs)) {
                        continue;
                    }

                    //lookup real name
                    $aka = $aka_model->lookupName($a);
                    if($aka !== null) $a = $aka;

                    //ignore non username
                    if(!isset($grouped_tickets[$a])) {
                        $grouped_tickets[$a] = array();
                    }
                    $grouped_tickets[$a][] = $ticket;
                }
            } else {
                if(!isset($grouped_tickets[$token])) {
                    $grouped_tickets[$token] = array();
                }
                $grouped_tickets[$token][] = $ticket;
            }
        }
        return $grouped_tickets;
    }
} 
