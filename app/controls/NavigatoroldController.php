<?

class NavigatoroldController extends Zend_Controller_Action 
{ 
    public function init()
    {
        header("Location: list/open", true, 301);
        exit;
/*
        $this->view->submenu_selected = "view";

        $this->view->sortby = "nad";
        if(isset($_REQUEST["sortby"])) {
            //dlog("settin sortby to ".$_REQUEST["sortby"]);
            $this->view->sortby = $_REQUEST["sortby"];
        }

        $this->view->sortdir = "up";
        if(isset($_REQUEST["sortdir"])) {
            $this->view->sortdir = $_REQUEST["sortdir"];
        }
*/

    }

    public function indexAction()
    {
        if(user()->allows("admin")) {
            $this->openassignAction();
        } else {
            $this->openoriginAction();
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
    public function openoriginAction()
    {
        try {
            $model = new Tickets();
            $tickets = $model->getopen();
            $this->view->activetab = "openorigin";
            $this->view->tickets = $this->groupby("mrorigin", $tickets);
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
            $tickets = $model->getclosed(config()->closeticket_window);
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
    public function closeoriginAction()
    {
        try {
            $model = new Tickets();
            $tickets = $model->getclosed(config()->closeticket_window);
            $this->view->activetab = "closeorigin";
            $this->view->tickets  = $this->groupby("mrorigin", $tickets);
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

        //group tickets by certain field id
        $grouped_tickets = array();
        foreach($tickets as $ticket) {
            $token = Footprint::parse($ticket->$type);
            if($type == "mrassignees") {
                //assignees are wierd (need to filter by certain team)

                $fp_goc = array();
                foreach($teams as $id=>$team) {
                    if(in_array($id, config()->navigator_assignee_list)) {
                        $fp_goc = array_merge($fp_goc, explode(",", $team->members));
                    }
                }

                foreach(explode(" ", $token) as $a) {
                    if(strlen($a) >= 3 and strpos($a, "CC:") === 0) {
                        //ignore CCs
                        continue;
                    }

                    //ignore non-goc assignee
                    if(!in_array($a, $fp_goc)) {
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
