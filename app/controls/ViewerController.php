<?php

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
            //$this->render("noid");
            $this->_redirect("/search?q=");//show all opened
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
        if($detail === "") { //ugly..
            $this->render("nosuchticket");
            return;
        }
        $detail->id = $id;

        $this->view->ticket_id = $id;
        $this->view->title = @$detail->title;
        $this->view->page_title = "[$id] ".@$detail->title;
        if(!user()->isGuest()) {
            //$this->view->load_comet = true;
            $this->view->contact_id = user()->contact_id;
            $this->view->contact_name = user()->contact_name;

            //register cid/cname for node to allow access
            $nodekey = sha1(rand());
            $url = config()->local_chatjs_url."/ac?key=$nodekey&cid=".user()->contact_id."&name=".urlencode(user()->contact_name);
            $status = file_get_contents($url);
            if($status == "registered") {
                $this->view->nodekey = $nodekey;
            } else {
                elog("failed to registere nodejs access with url [$url]");
            }
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
        if(isset($plist[$detail->priority])) {
            $this->view->priority = $plist[$detail->priority];
        } else {
            elog("failed to lookup priority: ".@$detail->priority);
            $this->view->priority = "unknown_prioriry:".@$detail->priority;
        }
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
        foreach(explode(" ", @$detail->assignees) as $a) {
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

        $this->view->destination_vo = Footprint::parse(@$detail->Destination__bVO__bSupport__bCenter);
        $this->view->nad = date("Y-m-d", strtotime(@$detail->ENG__bNext__bAction__bDate__fTime__b__PUTC__p));
        $this->view->next_action = @$detail->ENG__bNext__bAction__bItem;
        $this->view->ready_to_close = @$detail->Ready__bto__bClose__Q;
        $this->view->ticket_type = Footprint::parse(@$detail->Ticket__uType);

        $model = new TX();
        $this->view->txlinks = array();
        try {
            foreach($model->getLinks($id) as $txid=>$tid) {
                $info = config()->lookupFPID($txid, $tid);
                $fpid = $info[0];
                $url = $info[1];
                if(isset($info[2])) {
                    $tid = $info[2];
                }
                if($fpid !== null) {
                    $this->view->txlinks[$fpid] = array($tid, $url);
                }
            }
        } catch (Exception $e) {
            elog("Failed to connect to TX db - ignoring\n".$e->getMessage());
        }

        $model = new Data();
        $metadata = array();
        foreach($model->getAllMetadata($id) as $rec) {
            $metadata[$rec->key] = $rec->value;
        }
        $this->view->metadata = $metadata;

        //error_log("############################");
        //error_log("[".$metadata["ticket_links"]."]");
        
        //lookup ticket titles for each ticket links
        if(isset($metadata["ticket_links"])) {
            $this->loadTicketLinks($metadata["ticket_links"]);
        }

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
        $footprint = new Footprint($ticket_id);

        $footprint->setTitle($_REQUEST["title"]); //TODO Validate
        $footprint->setName($_REQUEST["submitter_name"]);//TODO validate
        $footprint->setEmail($_REQUEST["submitter_email"]);//TODO email
        $footprint->setOfficePhone($_REQUEST["submitter_phone"]);//TODO phone

        if(isset($_REQUEST["ticket_links"])) {
            $links = $_REQUEST["ticket_links"];
            //TODO validate?
            $footprint->setMetadata("ticket_links", $links);
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

        //detail
        $ccs = @$_REQUEST["cc"]; //TODO - validate
        $description = trim($_REQUEST["description"]); //TODO - validate?
        $nad = strtotime($_REQUEST["nad"]);
        $next_action = trim($_REQUEST["next_action"]);//TODO - validate?
        $priority = (int)$_REQUEST["priority"];
        $status = $_REQUEST["status"]; //TODO - validate?
        $type = $_REQUEST["ticket_type"]; //TODO - validate

        if($_REQUEST["metadata_r"] != "") {
            $footprint->setMetadataResourceID($_REQUEST["metadata_r"]);
        } else {
            //need to reset
            $footprint->setMetadataResourceID(null);
        }

        if($_REQUEST["metadata_vo"] != "") {
            $footprint->setMetadataVOID($_REQUEST["metadata_vo"]);
        } else {
            //need to reset
            $footprint->setMetadataVOID(null);
        }

        if($_REQUEST["metadata_sc"] != "") {
            $footprint->setMetadataSCID($_REQUEST["metadata_sc"]);
        } else {
            //need to reset
            $footprint->setMetadataSCID(null);
        }

        //set description & submitter
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
        $footprint->setSubmitterName(user()->getPersonName()); //used for notification

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

        //finally..
        if(!$good) {
            //TODO - pass error message via json return object
        } else {
            try {
                //need to do link process before I update the ticket so that I know which tickets to unlink
                $ticket_links = $footprint->getMetadata("ticket_links");
                $this->processTicketLinks($ticket_id, $ticket_links);

                $mrid = $footprint->submit();
                if(!config()->simulate) {
                    message("success", "Successfully updated ticket <a href=\"".fullbase()."/$mrid\">$mrid</a>", true);
                    /*
                    if(isset($_REQUEST["closewindow"]) && $_REQUEST["closewindow"] == "on") {
                        //do close
                        $close = "?close=true";
                        header("Location: ".fullbase()."/".$ticket_id.$close);
                        exit;
                    }
                    */
                } 
            
                //$this->view->mrid = $mrid;
                //$this->render("success", null, true);
            } catch(exception $e) {
                $this->sendErrorEmail($e);
                //$this->render("failed", null, true);
            }
        }
        $this->render("none", null, true);
    }

    public function processspamAction() {
        user()->check("update");

        $ticket_id = (int)$_REQUEST["id"];

        //submit to akismet
        $model = new Tickets();
        $detail = $model->getDetail($ticket_id);
        $descs = $this->parse_descs($detail);
        $first = end($descs);

        //strip last line (like.. by Soichi Hayashi (guest)) - assuming we don't spam filter ticket submitte by user
        $content_lines = explode("\n", $first["content"]);
        array_pop($content_lines);
        $content = implode("\n", $content_lines);

        $model = new Data();
        $metadata = $model->getAllMetadata($ticket_id);
        $sip = null;
        $sagent  = null;
        foreach($metadata as $entry) {
            if($entry->key == "SUBMITTER_IP") $sip = $entry->value;
            if($entry->key == "SUBMITTER_AGENT") $sagent = $entry->value;
        }
        
        $data = array(
            'user_ip'              => $sip,
            'user_agent'           => $sagent,
            'comment_type'         => 'comment',
            'comment_author'       => $detail->First__bName." ".$detail->Last__bName, 
            'comment_author_email' => $detail->Email__baddress,
            'comment_content'      => trim($content)
        );
        if(is_null($sip)) {
            elog("can't spam submit to akismet without ip/agent");
        } else { 
            $akismet = new Zend_Service_Akismet(config()->akismet_key, fullbase());
            if ($akismet->verifyKey()) {
                $akismet->submitSpam($data);
                slog("reported following spam to akismet");
                slog(print_r($data, true));
            } else {
                elog("can't submit spam to akismet: bad key");
            }
        }

        //update ticket
        $footprint = new Footprint($ticket_id);
        $agent = $this->getFPAgent(user()->getPersonName());

        if($agent !== null) {
            $footprint->setSubmitter($agent);
        }
        $footprint->setSubmitterName(user()->getPersonName()); //used for notification

        $footprint->setStatus("Closed");
        $footprint->setTicketType("Security_Notification");
        $mrid = $footprint->submit();

        $this->render("success", null, true);
    }

    public function updatebasicAction()
    {
        if(user()->isGuest()) {
            throw new AuthException("guest can't update ticket");
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
        $footprint->setSubmitterName(user()->getPersonName()); //used for notification

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

        //I need to setTitle so that notification object will contain valid ticket title.
        $sess = new Zend_Session_Namespace("ticket_$ticket_id");
        if(isset($sess->title)) {
            $footprint->setTitle($sess->title);
        } else {
            elog("failed to lookup ticlet title from session.. notification will probably contain invalid title title");
        }
        

        $mrid = $footprint->submit();
        if(!config()->simulate) {
            message("success", "Successfully updated ticket <a href=\"".fullbase()."/$mrid\">$mrid</a>", true);
        } 
        $this->view->mrid = $mrid;
        $this->render("success", null, true);
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

        if(isset($_REQUEST["close"])) {
        ?>
            <script type="text/javascript">
                //reload opener list
                if(window.opener && window.opener.name == "gocticket_list") {
                    window.opener.location.reload();
                }

                //try closing 
                if (!window.close()) {
                    <?php
                    //can't close, then reload the page we are trying to close
                    $ticket_id = (int)trim($this->getRequest()->getParam("id"));
                    ?>
                    document.location = "<?=$ticket_id?>";
                }
            </script>
        <?php
            $this->render("none", null, true);
            return;
        }

        if(user()->allows("update")) {
            //load additional stuff that we need for ticket edit
            $schema_model = new Schema();
            $this->view->originating_vos = $schema_model->getoriginatingvos();
            $this->view->destination_vos = $schema_model->getdestinationvos();
            $this->view->editable = true;
        } else {
            //if the ticket is not editable, store ticket detail in session 
            //so we can use it when user submit - like ticket title in event notification
            $sess = new Zend_Session_Namespace("ticket_".$detail->id);
            $sess->title = @$detail->title;
        }

        $descs = $this->parse_descs($detail);

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

        if(!user()->isGuest()) {
            $this->view->contact_id = user()->contact_id;
            $this->view->contact_name = user()->contact_name;
        }

    }

    private function parse_descs($detail) {
        $descs = array();
        //some ticket doesn't have any descs
        if(isset($detail->alldescs)) {
            $alldesc = $detail->alldescs;
            $alldescs = explode("Entered on", $alldesc);

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
        }
        return $descs;
    }

    //basically this loads the ticket title for each ticket ids linked
    private function loadTicketLinks($ticket_links) {
         if($ticket_links != "") {
            $ids = explode(",", $ticket_links);

            $url = config()->solr_host."/select?wt=json";
            $ids = str_replace(",","%20",$ticket_links);
            $url .= "&q=id:($ids)";
            $url .= "&fl=id,title,status";
            $ret_json = file_get_contents($url);
            //error_log($url);
            $ret = json_decode($ret_json);
            $info = array();
            foreach($ret->response->docs as $doc) {
                $info[$doc->id] = $doc;
            }
            //error_log(print_r($ticket_titles, true));
            $this->view->ticket_links_info = $info;
        }
    }

    //ticket linking information needs to be updated on each destination
    private function processTicketLinks($srcid, $new_links) {
        $data = new Data();

        if(!$new_links) {
            $new_links = array();
        } else {
            $new_links = explode(",",$new_links);
        }

        //handle removal
        $old_links = $data->getMetadata($srcid, "ticket_links");
        if(!$old_links) {
            $old_links = array();
        } else {
            $old_links = explode(",",$old_links);
        }
        /*
        error_log("old_links");
        error_log(print_r($old_links, true));
        error_log("new_links");
        error_log(print_r($new_links, true));
        */

        foreach($old_links as $destid) {
            if(!$destid) continue; //kiss..
            if(!in_array($destid, $new_links)) {
                //error_log("need to remove linking on $destid");
                $dest_links = $data->getMetadata($destid, "ticket_links");
                $dest_links = explode(",",$dest_links);
                $pos = array_search($srcid, $dest_links);
                //error_log("before updated dest links");
                //error_log(print_r($dest_links, true));
                unset($dest_links[$pos]);
                //error_log("updated dest links");
                //error_log(print_r($dest_links, true));
                $data->setMetadata($destid, "ticket_links", implode(",",$dest_links));
                
            }
        }

        //handle insert
        foreach($new_links as $destid) {
            $dest_links = $data->getMetadata($destid, "ticket_links");
            if(!$dest_links) {
                $dest_links = array();
            } else {
                $dest_links = explode(",",$dest_links);
            }
            //error_log("destination ".$destid. ":".$dest_links);
            if(!in_array($srcid, $dest_links)) {
                $dest_links[] = $srcid;
            }
            $data->setMetadata($destid, "ticket_links", implode(",",$dest_links));
        }
    }
}

function ticketcmp($a, $b) {
    return (round($a->score, 2) > round($b->score, 2)) ? -1 : 1;
}


