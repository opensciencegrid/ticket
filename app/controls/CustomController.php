<?

//can't use BaseController since this form doesn't use zend form
class CustomController extends Zend_Controller_Action  
{
    public function init()
    {
        user()->check("admin");

        $this->view->page_title = "Custom Ticket Submitter";
        $this->view->menu_selected = "user";
        $this->view->submenu_selected = "custom";

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
    }

    public function submitAction()
    {
        //pull & validate the request 
        $good = true;

        $title = trim($_REQUEST["title"]);
        if($title == "") {
            message("error", "Title cannot be empty.");
            $good = false;
        }

        //contact
        $submit_name = trim($_REQUEST["submitter_name"]);
        $submit_email = trim($_REQUEST["submitter_email"]);
        $submit_phone = trim($_REQUEST["submitter_phone"]);

        if($submit_name == "") {
            message("error", "Please specify contact fullname.");
            $good = false;
        }

        //consolidate assignee list
        $assignees = array();
        foreach($_REQUEST as $key=>$param) {
            if(substr($key, 0, 5) == "team_") {
                foreach($param as $assignee=>$flag) {
                    $assignees[] = $assignee;
                }
            }
        }
        if(count($assignees) == 0) {
            message("error", "Please assign at least one assignee.");
            $good = false;
        }

        //detail
        $ccs = @$_REQUEST["cc"]; //TODO - validate
        $description = trim($_REQUEST["description"]); //TODO - validate?
        $nad = strtotime($_REQUEST["nad"]);
        $next_action = trim($_REQUEST["next_action"]);//TODO - validate?
        $priority = (int)$_REQUEST["priority"];
        $status = $_REQUEST["status"]; //TODO - validate?
        $type = $_REQUEST["ticket_type"]; //TODO - validate

        $footprint = new Footprint(null, false);
        $footprint->setTitle($title); 

        if($_REQUEST["metadata_r"] != "") {
            $resource_id = (int)$_REQUEST["metadata_r"];
            $footprint->setMetadataResourceID($resource_id);
        }
        if($_REQUEST["metadata_vo"] != "") {
            $vo_id = (int)$_REQUEST["metadata_vo"];
            $footprint->setMetadataVOID($vo_id);
        }
        if($_REQUEST["metadata_sc"] != "") {
            $sc_id = (int)$_REQUEST["metadata_sc"];
            $footprint->setMetadataSCID($sc_id);
        }

        if($good) {

            if($description != "") {
                $footprint->addDescription($description);
            }

            $this->setSubmitter($footprint);

            //contact
            $footprint->setName($submit_name);
            $footprint->setMetadata("SUBMITTER_NAME", $submit_name);
            $footprint->setOfficePhone($submit_phone);
            $footprint->setEmail($submit_email);
            if(user()->getDN() !== null) {
                $footprint->setMetadata("SUBMITTER_DN", user()->getDN());
            }
            $footprint->setMetadata("SUBMITTED_VIA", "GOC Ticket/".$this->getRequest()->getControllerName());

            //detail
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
            $footprint->setNextAction($next_action);
            $footprint->setNextActionTime($nad);
            $footprint->setPriority($priority);
            $footprint->setStatus($status);
            $footprint->setTicketType($type);
            try {
                $mrid = $footprint->submit();
                if(!config()->simulate) {
                    message("success", "Successfully updated ticket <a href=\"".fullbase()."/$mrid\">$mrid</a>", true);
                }
                $this->view->mrid = $mrid;
                $this->render("success", null, true);
            } catch(exception $e) {
                $this->sendErrorEmail($e);
                $this->render("failed", null, true);
            }
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

            $this->view->submitter_vo = @$_REQUEST["submitter_vo"];
            $this->view->originating_ticket_id = @$_REQUEST["originating_ticket_id"];
            $this->view->destination_vo = @$_REQUEST["destination_vo"];
            $this->view->destination_ticket_id = @$_REQUEST["destination_ticket_id"];

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
            $footprint->addDescription("\n\nby ".user()->getDN());
        }
        $footprint->setSubmitterName(user()->getPersonName()); //used for notification
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
