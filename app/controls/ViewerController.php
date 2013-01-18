<?

//class ViewerController extends Zend_Controller_Action 
class ViewerController extends BaseController
{
    public function init()
    {
        $this->view->menu_selected = "navigator";
        //message("success", "here is my message");
    }
    
    public function loaddetail()
    {
        $dirty_id = trim($this->getRequest()->getParam("id"));
        if(empty($dirty_id)) {
            $this->render("noid");
            return;
        }

        $id = (int)$dirty_id;

        if((string)$id !== $dirty_id) {
            //id that looks like non-id - forward to keyword search
            //$this->_redirect("http://www.google.com/cse?cx=".config()->google_custom_search_cx."&q=".urlencode($dirty_id), array("exit"=>true));
            $this->_redirect("/search?q=".urlencode($dirty_id));
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
        if(!user()->isGuest()) {
            $this->view->load_comet = true;
            $this->view->contact_id = user()->contact_id;
            $this->view->contact_name = user()->contact_name;
        }

        //limit access to security announcement
        if($detail->Ticket__uType == "Security_Notification") {
            //only certain users can see security announcements - registered users on OIM 2009-09-22
            if(user()->isGuest()) {
                $this->render("securityannounceticket");
                return null;
            }
            message("normal", "This is a security notification ticket which is only accessible to registered OIM users. Please do not forward the content of this ticket to anyone.");
        }
        
        //limit access to security ticket
        if($detail->Ticket__uType == "Security") {
            //only certain users can see security incident ticket - GOC and Security staff 2009-09-22
            if(!user()->allows("view_security_incident_ticket")) {
                $this->render("security");
                return null;
            }
            message("error", "This is a security ticket that only authorized persons can view. Please do NOT forward the contents of this ticket to any one without the OSG security team's consent");
        }

        //submitter 
        $this->view->submitter_name = $detail->First__bName." ".$detail->Last__bName;

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
            elog("Failed to connect to TX db - ignoring\n".$e->getMessage());
        }

        $model = new Data();
        $this->view->metadata = $model->getAllMetadata($id);

        //load simmilar ticket info
        $xml_file = config()->group_xml_path;
        if(file_exists($xml_file)) {
            try {
                $groups = new SimpleXmlElement(file_get_contents($xml_file), LIBXML_NOCDATA);
                $match = false;
                foreach($groups as $group) {
                    $tickets = array();
                    foreach($group as $ticket) {
                        if($ticket->id == $id) {
                            $match = true;
                            continue;
                        }
                        $tickets[] = $ticket;
                    }
                    if($match) {
                        uasort($tickets, "ticketcmp");
                        $this->view->similar_tickets = $tickets;
                        break;
                    }
                }
            } catch(exception $e) {
                throw new exception($e->getMessage());
            }
        } else {
            elog($xml_file." doesn't exist");
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
        user()->check("update");

        //pull & validate the request 
        $good = true;

        $ticket_id = (int)$_REQUEST["id"];
        $title = $_REQUEST["title"]; //TODO - validate?

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
        $nad = strtotime($_REQUEST["nad"]);
        $next_action = trim($_REQUEST["next_action"]);//TODO - validate?
        $priority = (int)$_REQUEST["priority"];
        $status = $_REQUEST["status"]; //TODO - validate?
        $type = $_REQUEST["ticket_type"]; //TODO - validate

        //load current metadata
        $metadata = array();
        $data = new Data();
        foreach($data->getAllMetadata($ticket_id) as $item) {
            $metadata[$item->key] = $item->value;
        }

        //update metadata
        if($_REQUEST["metadata_r"] == null) {
            if(isset($metadata["ASSOCIATED_R_ID"])) {
                //reset existing values to null
                //TODO - not sure GOC-TX can handle metadata set to NULL..
                $metadata["ASSOCIATED_R_ID"] = null;
                $metadata["ASSOCIATED_R_NAME"] = null;
                $metadata["ASSOCIATED_RG_ID"] = null;
                $metadata["ASSOCIATED_RG_NAME"] = null;
            } else {
                //if it's not currently set, then just leave it not set
            }
        } else {
            $resource_id = (int)$_REQUEST["metadata_r"];
            $resource_model = new Resource();
            $resource_group_model = new ResourceGroup();
            $resource = $resource_model->fetchByID($resource_id);
            $resource_group = $resource_group_model->fetchByID($resource->resource_group_id);
            $metadata["ASSOCIATED_R_ID"] = $resource_id;
            $metadata["ASSOCIATED_R_NAME"] = $resource->name;
            $metadata["ASSOCIATED_RG_ID"] = $resource->resource_group_id;
            $metadata["ASSOCIATED_RG_NAME"] = $resource_group->name;
        }

        if($_REQUEST["metadata_vo"] == null) {
            if(isset($metadata["ASSOCIATED_VO_ID"])) {
                //reset existing values to null
                //TODO - not sure GOC-TX can handle metadata set to NULL..
                $metadata["ASSOCIATED_VO_ID"] = null;
                $metadata["ASSOCIATED_VO_NAME"] = null;
            } else {
                //if it's not currently set, then just leave it not set
            }
        } else {
            $vo_id = (int)$_REQUEST["metadata_vo"];
            $vo_model = new VO();
            $vo = $vo_model->get($vo_id);
            $metadata["ASSOCIATED_VO_ID"] = $vo_id;
            $metadata["ASSOCIATED_VO_NAME"] = $vo->name;
        }

        if($_REQUEST["metadata_sc"] == null) {
            if(isset($metadata["SUPPORTING_SC_ID"])) {
                //reset existing values to null
                //TODO - not sure GOC-TX can handle metadata set to NULL..
                $metadata["SUPPORTING_SC_ID"] = null;
                $metadata["SUPPORTING_SC_NAME"] = null;
            } else {
                //if it's not currently set, then just leave it not set
            }
        } else {
            $sc_id = (int)$_REQUEST["metadata_sc"];
            $sc_model = new SC();
            $sc = $sc_model->get($sc_id);
            $metadata["SUPPORTING_SC_ID"] = $sc_id;
            $metadata["SUPPORTING_SC_NAME"] = $sc->name;
        }

        if(!$good) {
            //TODO - implement mechanism to allow re-editing
            echo "Sorry, I haven't implemented the re-edit mechanism yet.. I have lost your update information";
        } else {
            //prepare and submit ticket update
            $footprint = new Footprint($ticket_id);
            $footprint->setTitle($title); 

            $agent = $this->getFPAgent(user()->getPersonName());
            if($description != "") {
                $footprint->addDescription($description);
                if($agent === null) {
                    $footprint->addDescription("\n\nby ".user()->getDN());
                }
            }
            if($agent !== null) {
                $footprint->setSubmitter($agent);
            }

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
            //$footprint->setDestinationVO($dest_vo);
            $footprint->setNextAction($next_action);
            $footprint->setNextActionTime($nad);
            $footprint->setPriority($priority);
            $footprint->setStatus($status);
            $footprint->setTicketType($type);

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

            //copy metadata
            foreach($metadata as $key=>$value) {
                $footprint->setMetadata($key, $value);
            }
        
            $footprint->submit();
            message("success", "Successfully updated ticket $ticket_id!");
            $close = "";
            if(isset($_REQUEST["closewindow"]) && $_REQUEST["closewindow"] == "on") {
                $close = "?close=true";
            }
            header("Location: ".fullbase()."/".$ticket_id.$close);
            exit;
        }
        $this->render("none", null, true);
    }

    public function updatebasicAction()
    {
        if(user()->isGuest()) {
            throw new AuthException();
        } 
        $ticket_id = (int)$_REQUEST["id"];
        $footprint = new Footprint($ticket_id);

        //cc list
        if(isset($_REQUEST["cc"])) {
            $ccs = $_REQUEST["cc"]; //TODO - validate
            foreach($ccs as $cc) {
                $cc = trim($cc);
                if($cc != "") {
                    $footprint->addCC($cc);
               }
            }
        }

        //new update
        $agent = $this->getFPAgent(user()->getPersonName());
        $description = trim($_REQUEST["description"]); //TODO - should validate?
        if($description != "") {
            $footprint->addDescription($description);
            if($agent === null) {
                $footprint->addDescription("\n\nby ".user()->getDN());
            }
        }
        if($agent !== null) {
            $footprint->setSubmitter($agent);
        }

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
        message("success", "Successfully updated this ticket!");
        header("Location: ".fullbase()."/".$ticket_id);
        exit;//needed?
    }
 
/*
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
*/

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

        if(isset($_REQUEST["close"])) {
        ?>
            <script type="text/javascript">
                //reload opener list
                if(window.opener && window.opener.name == "gocticket_list") {
                    window.opener.location.reload();
                }

                //try closing 
                if (!window.close()) {
                    <?
                    //can't close, then reload the page we are trying to close
                    $ticket_id = (int)trim($this->getRequest()->getParam("id"));
                    ?>
                    document.location = "<?=$ticket_id?>";
                }
            </script>
        <?
            $this->render("none", null, true);
            return;
        }

        if(user()->allows("update")) {
            //load additional stuff that we need for ticket edit
            $schema_model = new Schema();
            $this->view->originating_vos = $schema_model->getoriginatingvos();
            $this->view->destination_vos = $schema_model->getdestinationvos();

            $this->view->editable = true;
        }

/*
        if(config()->debug) {
            slog("setting non-editable for debuging purpose");
            $this->view->editable = false;
        }
*/

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
            if(sizeof($info_a) == 1) {
                elog("FP information [$info] is malformed..(no 'by' information)");
                $by = "unknown";
            } else {
                $by = str_replace(":", "", $info_a[1]);
            }

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

    public function uploadattachmentAction() {
        if(user()->isGuest()) {
            if(!user()->isGOCMachine()) {
                elog($_SERVER["REMOTE_ADDR"]." is not goc machine. can't access uploadattachment action");
                $this->render("error/access", null, true); 
                return;
            }
        }
        
        $id = (int)$_REQUEST["id"];
        //if($id > 20000) return; //small attempt to block some kind of random access attack

        $model = new Attachments();
        $datas = $model->upload($id);

        header('Pragma: no-cache');
        header('Cache-Control: private, no-cache');
        header('Content-Disposition: inline; filename="files.json"');
        header('X-Content-Type-Options: nosniff');
        header('Vary: Accept');
	if(!function_exists("json_encode")) {
		require_once("app/json.php");
	}
        echo json_encode($datas);
        $this->render("none", null, true);
    }

    //generate thumbnail from attachment - THIS IS A PUBLIC FUNCTION (better be rock solid..)
    public function thumbnailAction() {
        $id = (int)$_REQUEST["id"];
        $dirty_attachment_name = $_REQUEST["attachment"];
        $attachment_id = basename($dirty_attachment_name);

        $model = new Attachments();
        $path = $model->getpath($id, $attachment_id);

        require_once("app/thumbnail.php");
        $tg = new thumbnailGenerator;
        if(!$tg->generate($path, 100, 100)) {
            header("Content-Type: image/png");
            echo file_get_contents("images/unknown.png");
            slog("output default icon");
        }
        $this->render("none", null, true);
    }

    public function deleteattachmentAction() {
        if(user()->isGuest()) {
            $this->render("error/access", null, true); 
            return;
        }

        $id = (int)$_REQUEST["id"];
        $dirty_attachment_name = $_REQUEST["attachment"];
        $attachment_id = basename($dirty_attachment_name);

        $model = new Attachments();
        $ret = $model->remove($id, $attachment_id);

	if(!function_exists("json_encode")) {
		require_once("app/json.php");
	}
        echo json_encode($ret);
        $this->render("none", null, true);
    }

    public function loadattachmentsAction() {
        header('Pragma: no-cache');
        header('Cache-Control: private, no-cache');
        header('Content-Disposition: inline; filename="files.json"');
        header('X-Content-Type-Options: nosniff');
        header('Vary: Accept');

        $id = (int)$_REQUEST["id"];

        $model = new Attachments();
        $datas = $model->listattachments($id);
	if(!function_exists("json_encode")) {
		require_once("app/json.php");
	}
        echo json_encode($datas);
        $this->render("none", null, true);
    }
}

function ticketcmp($a, $b) {
    return (round($a->score, 2) > round($b->score, 2)) ? -1 : 1;
}


