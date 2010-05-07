<?

class ViewerController extends Zend_Controller_Action 
{
    public function init()
    {
        $this->view->submenu_selected = "view";
    }
    
    public function loaddetail()
    {
        if(!isset($_REQUEST["id"])) {
            $this->render("noid");
            return;
        }

        $dirty_id = trim($_REQUEST["id"]);
        $id = (int)$dirty_id;

        if((string)$id !== $dirty_id) {
            //id that looks like non-id - forward to keyword search
            $this->_redirect("http://www.google.com/cse?cx=016752695275174109936:9u1k_fz_bag&q=".urlencode($_REQUEST["id"]), array("exit"=>true));
        }

        $model = new Tickets();
        $detail = $model->getDetail($id);
        if($detail === "") {
            $this->render("nosuchticket");
            return;
        }

        $this->view->ticket_id = $id;
        $this->view->title = $detail->title;
        $this->view->page_title = "[$id] ".$detail->title;

        //limit access to security announcement
        if($detail->Ticket__uType == "Security_Notification") {
            //only certain users can see security announcements - registered users on OIM 2009-09-22
            if(user()->isGuest()) {
                $this->render("securityannounceticket");
                return null;
            }
        }
        
        //limit access to security ticket
        if($detail->Ticket__uType == "Security") {
            //only certain users can see security incident ticket - GOC and Security staff 2009-09-22
            if(!user()->allows("view_security_incident_ticket")) {
                $this->render("security");
                return null;
            }
        }

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
        $this->view->teams = $model->getteams();

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


        $this->view->destination_vo = Footprint::parse($detail->Destination__bVO__bSupport__bCenter);
        $this->view->nad = date("Y-m-d", strtotime($detail->ENG__bNext__bAction__bDate__fTime__b__PUTC__p));
        $this->view->next_action = $detail->ENG__bNext__bAction__bItem;
        $this->view->ready_to_close = $detail->Ready__bto__bClose__Q;
        $this->view->ticket_type = Footprint::parse($detail->Ticket__uType);

        $model = new TX();
        $this->view->txlinks = array();
        try {
            foreach($model->getLinks($id) as $txid=>$tid) {
                list($fpid, $url) = config()->lookupFPID($txid, $tid);
                if($fpid !== null) {
                    $this->view->txlinks[$fpid] = array($tid, $url);
                }
            }
        } catch (Exception $e) {
            elog("Failed to connect to TX db - ignoring");
        }

        return $detail;
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

            if(!$good) {
                //TODO - implement mechanism to allow re-editing
                echo "Sorry, I haven't implemented the re-edit mechanism yet.. I have lost your update information";
            } else {
                //prepare and submit ticket update
                $footprint = new Footprint($ticket_id);
                $footprint->setTitle($title); 

                if($description != "") {
                    $footprint->addDescription($description);
                }

                $agent = $this->getFPAgent(user()->getPersonName());
                if($agent !== null) {
                    $footprint->setSubmitter($agent);
                } else {
                    $footprint->addDescription("\n-- by ".user()->getPersonName());
                    $footprint->addMeta(user()->getDN());
                }

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
                $footprint->setDestinationVO($dest_vo);
                $footprint->setNextAction($next_action);
                $footprint->setNextActionTime($nad);
                $footprint->setPriority($priority);
                $footprint->setStatus($status);
                $footprint->setTicketType($type);
                $footprint->setOriginatingTicketNumber($orig_ticket_id);
                $footprint->setDestinationTicketNumber($dest_ticket_id);

                //set suppression
                if(!isset($_REQUEST["notify_assignees"])) {
                    $footprint->suppress_assignees();
                }
                if(!isset($_REQUEST["notify_submitter"])) {
                    $footprint->suppress_submitter();
                }
                if(!isset($_REQUEST["notify_ccs"])) {
                    $footprint->suppress_ccs();
                }
            
                $footprint->submit();
                addMessage("Successfully updated this ticket!");
                header("Location: ".fullbase()."/viewer?id=".$ticket_id);
                exit;
            }
        }
        $this->render("none", null, true);
    }

    public function updatecclistAction()
    {
        if(user()->isGuest()) {
            $this->render("error/access", null, true); 
        } else {
            if(!isset($_REQUEST["cc"])) {
                $this->view->content = "No CC list send....";
                $this->render("error/error", null, true); 
            } else {
                $ccs = $_REQUEST["cc"]; //TODO - validate
                $ticket_id = (int)$_REQUEST["id"];
                $footprint = new Footprint($ticket_id);

                $cclist = "";
                foreach($ccs as $cc) {
                    $cc = trim($cc);
                    if($cc != "") {
                        $footprint->addCC($cc);
                        $cclist .= $cc."\n";
                   }
                }
                if($cclist == "") $cclist = "(empty)";
                $footprint->addDescription("[Updated CC List]\n$cclist");

                $footprint->submit();
                addMessage("Successfully Updated CC list!");
                header("Location: ".fullbase()."/viewer?id=".$ticket_id);
                exit;
             }
            $this->render("none", null, true);
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
        try {
            $detail = $this->loaddetail();
            if($detail === null) return;

        } catch (SoapFault $e) {
            elog("SoapFault detected while ViewController:loaddetail()");
            elog($e->getMessage());
            return;
        }

        if(user()->allows("update")) {
            //load additional stuff that we need for ticket edit
            $schema_model = new Schema();
            $this->view->originating_vos = $schema_model->getoriginatingvos();
            $this->view->destination_vos = $schema_model->getdestinationvos();

            $this->view->editable = true;
        }

        //notes
        $alldesc = $detail->alldescs;
        $alldescs = explode("Entered on", $alldesc);
        $descs = array();

        foreach($alldescs as $desc) {
            if($desc == "") continue;
            $desc_lines = explode("\n", $desc);
            $info = trim($desc_lines[0]);
            $desc = strstr($desc, "\n");

            //parse out time and by..
            $info_a = explode(" by ", $info);
            $date_str = str_replace(" at ", " ", $info_a[0]);

            //FP9 add some time zone description - remove it since php can't parse it out
            $date_str = explode("(GMT", $date_str);
            $date_str = $date_str[0];

            $time = strtotime($date_str);// + 3600;
            $by = str_replace(":", "", $info_a[1]);

            if(isset($descs[$time])) {
                $descs[$time]["content"].= "\n".$desc;
            } else {
                $descs[$time] = array("type"=>"description", "by"=>$by, "content"=>$desc); 
            }
        }

        //only show internal activity for non-guest users (since it contains email address)
        if(user()->getPersonID() !== null) {
            //history (internal activity)
            $history = explode("\n", $detail->history);
            foreach($history as $hist) {
                $fields = explode("____________history", $hist);

                //parse out fields
                $time = strtotime($fields[0].$fields[1]." GMT"); //set GMT so that strtotime will parse it as FP timezone (should be GMT)
                $by = $fields[2];
                $action = $fields[3];
                //$action = str_replace(";", "\n", $action);

                if(isset($descs[$time])) {
                    $descs[$time]["content"].= "\n".Footprint::parse($action);
                } else {
                    $descs[$time] = array("type"=>"history", "by"=>$by, "content"=>$action); 
                }
            }
        }

        krsort($descs);
        $this->view->descs = $descs;
    }
}


