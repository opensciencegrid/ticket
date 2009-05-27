<?

class ViewerController extends Zend_Controller_Action 
{ 
    public function init()
    {
        $this->view->submenu_selected = "view";
    }    


    public function loaddetail()
    {
        $dirty_id = $_REQUEST["id"];
        $id = (int)$dirty_id;
        
        $model = new Tickets();
        $detail = $model->getDetail($id);
        if($detail === "") {
            $this->render("nosuchticket");
            return;
        } 

        /*
        //pull list of attachments
        $attachments = $model->getAttachments($id);
        var_dump($attachments);
        */

        //limit access to security ticket
        if($detail->Ticket__uType == "Security") {
            //only certain users can see security ticket
            if(!user()->allows("view_security_ticket")) {
                $this->render("security");
                return null;
            }
        }
        
        $this->view->ticket_id = $id;
        $this->view->title = $detail->title;
        $this->view->page_title = "[$id] ".$detail->title;

        //submitter 
        $this->view->submitter_name = $detail->First__bName." ".$detail->Last__bName;
        $this->view->submitter_fname = $detail->First__bName;
        $this->view->submitter_lname = $detail->Last__bName;

        $this->view->submitter_email = $detail->Email__baddress;
        $this->view->cc = $detail->Email__baddress;

        $this->view->originating_ticket_id = $detail->Originating__bTicket__bNumber;
        $this->view->destination_ticket_id = $detail->Destination__bTicket__bNumber;
        $this->view->submitter_phone = $detail->Office__bPhone;
        $this->view->submitter_vo = Footprint::parse($detail->Originating__bVO__bSupport__bCenter);

        //ticket info
        $this->view->status = Footprint::parse($detail->status);
        $plist = Footprint::GetPriorityList();
        $this->view->priority = $plist[$detail->priority];
        $this->view->assignees = "";
        $this->view->cc = "";

        //schema, team
        $aka_model = new AKA();
        $model = new Schema();
        $teams = array();
        foreach($model->getteams() as $teamrec) {
            $team = Footprint::parse($teamrec->team);
            $teams[$team] = split(",", $teamrec->members);
            $this->view->assignees[$team] = array();
        }
        $this->view->teams = $teams;
        $this->view->cc = array();

        //assignee, cc
        foreach(split(" ", $detail->assignees) as $a) {
            //FP somehow put CCs on assginee field... why!?!?
            if(strlen($a) >= 3 and strpos($a, "CC:") === 0) {
                $this->view->cc[] = substr($a, 3);
                continue;
            }
            $ass = Footprint::parse($a);
            //FP somehow contains team names on assignee...  why!?!?
            if(isset($teams[$ass])) {
                continue;
            }
            //now lookup real team for this person
            $team = $this->lookupTeam($teams, $ass);
            //lookup AKA
            $aka = $aka_model->lookupName($ass);
            if($aka !== null) $ass = $aka;
            $this->view->assignees[$team][$a] = $ass;
        }

        $this->view->destination_vo = Footprint::parse($detail->Destination__bVO__bSupport__bCenter);
        $this->view->nad = date("Y-m-d", strtotime($detail->ENG__bNext__bAction__bDate__fTime__b__PUTC__p));
        $this->view->next_action = $detail->ENG__bNext__bAction__bItem;
        $this->view->ready_to_close = $detail->Ready__bto__bClose__Q;
        $this->view->ticket_type = Footprint::parse($detail->Ticket__uType);

        return $detail;
    }

    public function lookupTeam($teams, $person)
    {
        foreach($teams as $team=>$members) {
            foreach($members as $member) {
                if($person == $member) return $team;
            }
        }
        return "Unknown Team";
    }

    public function editAction()
    {
        if(!user()->allows("update")) {
            $this->render("error/access", null, true); 
        } else {
            $detail = $this->loaddetail();

            //load additional stuff that we need for ticket edit
            $schema_model = new Schema();
            $this->view->originating_vos = $schema_model->getoriginatingvos();
            $this->view->destination_vos = $schema_model->getdestinationvos();
        }
    }
    
    public function quickdescAction()
    {
        $dirty_id = $_REQUEST["id"];

        $model = new Schema();
        $descs = $model->getquickdesc();
        echo $descs[$dirty_id];
        $this->render("none", null, true);
    }

    public function updateAction()
    {
        if(!user()->allows("update")) {
            $this->render("error/access", null, true); 
        } else {
            //pull & validate the request 
            $good = true;

            $ticket_id = (int)$_REQUEST["id"];
            $title = $_REQUEST["title"]; //TODO - validate?

            //contact
            $submit_fname = $_REQUEST["submitter_fname"];
            $submit_lname = $_REQUEST["submitter_lname"];
            $submit_email = $_REQUEST["submitter_email"];
            $submit_phone = $_REQUEST["submitter_phone"];
            $submit_vo = $_REQUEST["submitter_vo"];

            //detail
            $assignees = @$_REQUEST["assignees"]; //TODO - validate
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

            if(!$good) {
                //TODO - implement mechanism to allow re-editing
                echo "Sorry, I haven't implemented the re-edit mechanism yet.. I have lost your update information";
            } else {

                //prepare and submit ticket update
                try {
                    $footprint = new Footprint($ticket_id);
                    $footprint->setTitle($title); 
                    $footprint->setSubmitter(user()->getPersonName()); 

                    //contact
                    $footprint->setName($submit_fname." ".$submit_lname);
                    $footprint->setOfficePhone($submit_phone);
                    $footprint->setEmail($submit_email);
                    $footprint->setOriginatingVO($submit_vo);

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
                    if($description != "") {
                        $footprint->addDescription($description);
                    }
                    $footprint->setDestinationVO($dest_vo);
                    $footprint->setNextAction($next_action);
                    $footprint->setNextActionTime($nad);
                    $footprint->setPriority($priority);
                    $footprint->setStatus($status);
                    $footprint->setTicketType($type);
                    $footprint->setOriginatingTicketNumber($orig_ticket_id);
                    $footprint->setDestinationTicketNumber($dest_ticket_id);
                
                    $footprint->submit();
                    header("Location: ".fullbase()."/viewer?id=".$ticket_id);
                    exit;
                } catch(exception $e) {
                    echo "Sorry, ticket update submission failed for some reason";
                    echo $e;
                }
            }
        }
        $this->render("none", null, true);
    }

    public function indexAction() 
    { 
        try {
            $detail = $this->loaddetail();
            if($detail === null) return;
        } catch (SoapFault $e) {
            elog("SoapFault detected while ViewController:loaddetail()");
            elog($e->getMessage());
            return;
        }

        //notes
        $alldesc = $detail->alldescs;
        $alldescs = split("Entered on", $alldesc);
        $descs = array();

        foreach($alldescs as $desc) {
            if($desc == "") continue;
            $desc_lines = split("\n", $desc);
            $info = trim($desc_lines[0]);
            $desc = strstr($desc, "\n");

            //parse out time and by..
            $info_a = split(" by ", $info);
            $date_str = str_replace(" at ", " ", $info_a[0]);
            $time = strtotime($date_str) + 3600;
            $by = str_replace(":", "", $info_a[1]);

            if(isset($descs[$time])) {
                $descs[$time]["content"].= "\n".$desc;
            } else {
                $descs[$time] = array("type"=>"description", "by"=>$by, "content"=>$desc); 
            }
        }

        if(user()->getPersonID() !== null) {
            //history
            $history = split("\n", $detail->history);
            foreach($history as $hist) {
                $fields = split("____________history", $hist);

                //parse out fields
                $time = strtotime($fields[0].$fields[1]) + 3600;
                $by = $fields[2];
                $action = $fields[3];
                $action = str_replace(";", "\n", $action);

                if(isset($descs[$time])) {
                    $descs[$time]["content"].= "\n".$action;
                } else {
                    $descs[$time] = array("type"=>"history", "by"=>$by, "content"=>$action); 
                }
            }
        }

        krsort($descs);
        $this->view->descs = $descs;
    }

}


