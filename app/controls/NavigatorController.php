<?

class NavigatorController extends Zend_Controller_Action 
{ 
    public function init()
    {
        $this->view->submenu_selected = "view";
    }

    public function indexAction()
    {
        $model = new Tickets();

        //determine listtype
        $dirty_listtype = "open";
        if(isset($_REQUEST["listtype"])) {
            $dirty_listtype = $_REQUEST["listtype"];
        }
        switch($dirty_listtype) {
        case "open":
        case "openassign":
        case "close":
            $this->view->listtype = $dirty_listtype;
            break;
        default:
            throw new exception("Unknown listtype");
        }
    }

    public function openAction()
    {
        $model = new Tickets();
        $tickets = $model->getopen();
        $this->view->tickets = $this->groupby("mrdest", $tickets);
        $this->render("list");
    }
    public function openassignAction()
    {
        $model = new Tickets();
        $tickets = $model->getopen();

/*
        //load teams (used to group by mrassignees)
        $aka_model = new AKA();
        $model = new Schema();
        $this->teams = array();
        foreach($model->getteams() as $team) {
            $team = Footprint::parse($team->team);
            $this->teams[] = $team;
        } 
*/

        $this->view->tickets = $this->groupby("mrassignees", $tickets);
        $this->render("list");
    }
    public function closeAction()
    {
        $model = new Tickets();
        $tickets = $model->getclosed();
        $this->view->tickets = $this->groupby("mrdest", $tickets);
        $this->render("list");
    }

    protected function groupby($type, $tickets) 
    {
        $aka_model = new AKA();
        
        $schema_model = new Schema();
        $teams = $schema_model->getteams();
        //find suppor centers
        $fp_scs = array();
        foreach($teams as $team) {
            //var_dump($team->team);
            //if($team->team == "OSG__bSupport__bCenters") {
            if($team->team == "OSG__bGOC__bSupport__bTeam" or
                $team->team == "OSG__bOperations__bInfrastructure" or 
                $team->team == "OSG__bGOC__bManagement" or
                $team->team == "OSG__bGOC__bService__bDesk") {
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
