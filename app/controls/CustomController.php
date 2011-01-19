<?

class CustomController extends Zend_Controller_Action 
{
    public function init()
    {
        if(!user()->allows("admin")) {
            $this->render("error/access", null, true); 
            exit;
        }
        $this->view->submenu_selected = "open";

        //store form errors
        $this->view->error_messages = array();

        //schema, team
        $aka_model = new AKA();
        $model = new Schema();
        $this->view->teams = $model->getteams();
        $this->view->originating_vos = $model->getoriginatingvos();
        $this->view->destination_vos = $model->getdestinationvos();

        //replace members (echism,kagross,cpipes) to more usable array
        foreach($this->view->teams as $team) {
            $members = explode(",", $team->members);
            $new_members = array();
            foreach($members as $member) {
                $new_members[$member] = $aka_model->lookupName($member);
            }
            $team->members = $new_members;
        }

        //assignee, cc
        $this->view->assignees = array();
        $this->view->cc = array();
	/*
        foreach(explode(" ", $detail->assignees) as $a) {
            //FP somehow put CCs on assginee field... 
            if(strlen($a) >= 3 and strpos($a, "CC:") === 0) {
                $this->view->cc[] = substr($a, 3);
                continue;
            }
            //FP somehow contains team names on assignee... ignore it
            $team_name = false;
            foreach($this->view->teams as $team) {
                if($team->team == $a) {
                    $team_name = true;
                    break;
                }
            }
            if($team_name) continue;

            //store to assignee list
            $this->view->assignees[$a] = $aka_model->lookupName($a);
        }
	*/
    }

    public function submitAction()
    {
        //pull & validate the request 
        $good = true;

        $title = trim($_REQUEST["title"]);
        if($title == "") {
            $this->view->error_messages[] = "Title cannot be empty.";
            $good = false;
        }

        //contact
        $submit_name = $_REQUEST["submitter_name"];
        $submit_email = $_REQUEST["submitter_email"];
        $submit_phone = $_REQUEST["submitter_phone"];

        //consolidate assignee list
        $assignees = array();
        foreach($_REQUEST as $key=>$param) {
            if(substr($key, 0, 5) == "team_") {
                foreach($param as $assignee=>$flag) {
                    $assignees[] = $assignee;
                }
            }
        }

        //detail
        $ccs = @$_REQUEST["cc"]; //TODO - validate
        $description = trim($_REQUEST["description"]); //TODO - validate?
        $dest_vo = $_REQUEST["destination_vo"]; //TODO - validate
        $nad = strtotime($_REQUEST["nad"]);
        $next_action = trim($_REQUEST["next_action"]);//TODO - validate?
        $orig_ticket_id = "";
        if(trim($_REQUEST["originating_ticket_id"]) != "") {
            $orig_ticket_id = $_REQUEST["originating_ticket_id"]; //TODO - validate..
        }
        $dest_ticket_id = "";
        if(trim($_REQUEST["destination_ticket_id"]) != "") {
            $dest_ticket_id = $_REQUEST["destination_ticket_id"]; //TODO - validate..
        }
        $priority = (int)$_REQUEST["priority"];
        $status = $_REQUEST["status"]; //TODO - validate?
        $type = $_REQUEST["ticket_type"]; //TODO - validate

        if($good) {
            //prepare and submit ticket update
            $footprint = new Footprint;
            $footprint->setTitle($title); 

            if($description != "") {
                $footprint->addDescription($description);
            }

            $this->setSubmitter($footprint);

            //contact
            $footprint->setName($submit_name);
            $footprint->setOfficePhone($submit_phone);
            $footprint->setEmail($submit_email);
            //$footprint->setOriginatingVO($submit_vo);

            //detail
            $footprint->resetAssignee();
            foreach($assignees as $assignee) {
                $footprint->addAssignee($assignee);
            }
            $footprint->resetCC();
            if(isset($ccs)) {
                foreach($ccs as $cc) {
                    $cc = trim($cc);
                    if($cc != "") {
                        $footprint->addCC($cc);
                    }
                }
            }
            $footprint->setDestinationVO($dest_vo);
            $footprint->setNextAction($next_action);
            $footprint->setNextActionTime($nad);
            $footprint->setPriority($priority);
            $footprint->setStatus($status);
            $footprint->setTicketType($type);
            $footprint->setOriginatingTicketNumber($orig_ticket_id);
            $footprint->setDestinationTicketNumber($dest_ticket_id);

            $mrid = $footprint->submit();
            addMessage("Successfully opened a ticket");
            header("Location: ".fullbase()."/viewer?id=".$mrid);
            exit;
        } else {
            //send data back to form
            $this->view->title = $_REQUEST["title"];
            $this->view->submitter_name = $_REQUEST["submitter_name"];
            $this->view->submitter_email = $_REQUEST["submitter_email"];
            $this->view->submitter_phone = $_REQUEST["submitter_phone"];

            $this->view->ticket_type = $_REQUEST["ticket_type"];
            $plist = Footprint::GetPriorityList();
            $this->view->priority = $plist[(int)$_REQUEST["priority"]];
            $this->view->status = $_REQUEST["status"];

            $this->view->nad = $_REQUEST["nad"];
            $this->view->next_action = $_REQUEST["next_action"];
            $this->view->cc = @$_REQUEST["cc"];

            $this->view->submitter_vo = $_REQUEST["submitter_vo"];
            $this->view->originating_ticket_id = $_REQUEST["originating_ticket_id"];
            $this->view->destination_vo = $_REQUEST["destination_vo"];
            $this->view->destination_ticket_id = $_REQUEST["destination_ticket_id"];

            //agg..I have to reconstruct the assignee list..
            $this->view->assignees = array();
            foreach($_REQUEST as $key=>$value) {
                if(substr($key, 0, 5) == "team_") {    
                    foreach($value as $id=>$ignore) {
                        //$this->view->assignees[$id] = $aka_model->lookupName($id);
                        $this->view->assignees[$id] = "whatever";
                    }
                }
            }

            $this->view->description = $_REQUEST["description"];

            $this->render("index");
        }
    }

    public function setSubmitter($footprint) 
    {
        $agent = $this->getFPAgent(user()->getPersonName());
        if($agent !== null) {
            $footprint->setSubmitter($agent);
        } else {
            $footprint->addDescription("\n\n-- by ".user()->getPersonName());
            $footprint->addMeta(user()->getDN());
        }
    }

    private function getFPAgent($name)
    {
        $model = new Schema();
        $users = $model->getusers();
        foreach($users as $id=>$fpname) {
            if($fpname == $name) {
                return $id;
            }
        }
        return null;
    }

    public function indexAction() 
    { 
        //TODO - add more efaults here
        $plist = Footprint::GetPriorityList();
        $this->view->priority = $plist[4];
        $this->view->nad = date("Y-m-d", time());
        $this->view->submitter_vo = "MIS";
        $this->view->destination_vo = "MIS";
    }
}
