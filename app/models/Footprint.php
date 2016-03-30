<?php

class Footprint
{
    //var $assignee_override;

    //if id is null, we will do insert. If not, update
    public function __construct($id = null, $auto_assign = true)
    {
        $this->id = $id; 
        $this->submitter = "OSG-GOC";
        $this->submitter_name = null;
        $this->status = "Engineering";
        $this->priority_number = "4";
        $this->description = "";
        $this->title = "no title";
        $this->meta = "";
        $this->resetCC();
        $this->resetAssignee();
        $this->ab_fields = array();
        $this->project_fields = array();
        $this->metadata = array();

        //notification suppression
        $this->suppress_assignees = false;
        $this->suppress_submitter = false;
        $this->suppress_ccs = false;

        //process update flags
        if(is_null($id)) {
            //insert
            $f = true; //insert everything by default
            if($auto_assign) {
                $this->addAssignee($this->chooseGOCAssignee()); //auto assign someone
            }
            $this->project_fields["Originating__bVO__bSupport__bCenter"] = "other";
            $this->project_fields["Destination__bVO__bSupport__bCenter"] = "other";
            $this->setNextAction("ENG Action");
            $this->setNextActionTime(time());
            $this->setTicketType("Problem__fRequest");
            $this->setReadyToClose("No");
        } else {
            //update
            $f = false; //don't update anything by default

            //load current metadat
            $data = new Data();
            foreach($data->getAllMetadata($id) as $item) {
                $this->metadata[$item->key] = $item->value;
            }
        }

        //update flag - we will only send fields to FP that are true.
        $this->b_submitter = $f;
        $this->b_title = $f;
        $this->b_assignees = $f;
        $this->b_cc = $f;
        $this->b_priority = $f;
        $this->b_status = $f;
        $this->b_desc = $f;
        $this->b_contact = $f;
        $this->b_proj = $f;
    }

    public function setMetadataResourceID($resource_id) {
        //TODO - this has a bug where.. if someone opens a ticket with some resource selected,
        //then while the ticket is open, someone disabled the resource selected, then click
        //click update, $resource_id will be set to some disabled ID, but in reality it should be
        //set to null
        if(is_null($resource_id)) {
            if(isset($this->metadata["ASSOCIATED_R_ID"])) {
                //need to reset to null
                $this->metadata["ASSOCIATED_R_ID"] = null;
                $this->metadata["ASSOCIATED_R_NAME"] = null;
                $this->metadata["ASSOCIATED_RG_ID"] = null;
                $this->metadata["ASSOCIATED_RG_NAME"] = null;
            } else {
                //if not set yet, leave it not set
            }
        } else {
            //set to new resource
            $resource_model = new Resource();
            $resource = $resource_model->fetchByID((int)$resource_id);
            $resource_group_id = $resource->resource_group_id;
            $resource_group_model = new ResourceGroup();
            $resource_group = $resource_group_model->fetchByID($resource_group_id);

            //set description destination vo, assignee
            //$footprint->addMeta("Resource on which user is having this issue: ".$resource_name."($resource_id)\n");
            $this->metadata["ASSOCIATED_R_ID"] = $resource_id;
            $this->metadata["ASSOCIATED_R_NAME"] = $resource->name;
            $this->metadata["ASSOCIATED_RG_ID"] = $resource_group_id;
            $this->metadata["ASSOCIATED_RG_NAME"] = $resource_group->name;
        }
    }

    public function setMetadataVOID($vo_id) {
        if(is_null($vo_id)) {
            if(isset($this->metadata["ASSOCIATED_VO_ID"])) {
                //reset existing values to null
                //TODO - not sure GOC-TX can handle metadata set to NULL..
                $this->metadata["ASSOCIATED_VO_ID"] = null;
                $this->metadata["ASSOCIATED_VO_NAME"] = null;
            } else {
                //if it's not currently set, then just leave it not set
            }
        } else {
            $vo_model = new VO();
            $vo = $vo_model->get((int)$vo_id);
            $this->metadata["ASSOCIATED_VO_ID"] = $vo_id;
            $this->metadata["ASSOCIATED_VO_NAME"] = $vo->name;
        }
    }

    public function setMetadataSCID($sc_id) {
        if(is_null($sc_id)) {
            if(isset($this->metadata["SUPPORTING_SC_ID"])) {
                //reset existing values to null
                //TODO - not sure GOC-TX can handle metadata set to NULL..
                $this->metadata["SUPPORTING_SC_ID"] = null;
                $this->metadata["SUPPORTING_SC_NAME"] = null;
            } else {
                //if it's not currently set, then just leave it not set
            }
        } else {
            $sc_model = new SC();
            $sc = $sc_model->get((int)$sc_id);
            $this->metadata["SUPPORTING_SC_ID"] = $sc_id;
            $this->metadata["SUPPORTING_SC_NAME"] = $sc->name;
        }
    }

    public function suppress_assignees() { $this->suppress_assignees = true; } //set to not send assignee emails
    public function suppress_submitter() { $this->suppress_submitter = true; } //set to not send assignee emails
    public function suppress_ccs() { $this->suppress_ccs = true; } //set to not send assignee emails

    public function resetCC()
    {
        $this->permanent_cc = array();
        $this->b_cc = true;
    }

    public function resetAssignee()
    {
        $this->assignees = array();
        $this->b_assignees = true;
    }

    public function setTitle($v) { $this->title = $v; $this->b_title = true; }
    public function setSubmitter($v) { $this->submitter = $v; $this->b_submitter = true; }

    //used by event notifier to show the real name of the person
    public function setSubmitterName($v) { $this->submitter_name = $v; }

    public function setName($v) { 
        //split first name and the last name
        $pos = strpos(trim($v), " ");
        if($pos === false) {
            $first_name = $v;
            $last_name = "";
        } else {
            $first_name = substr($v, 0, $pos);
            $last_name = substr($v, $pos+1);
        }

        $this->ab_fields["First__bName"] = $first_name;
        $this->ab_fields["Last__bName"] = $last_name;
        $this->b_contact = true; 
    }
    public function setOfficePhone($v) { $this->ab_fields["Office__bPhone"] = $v; $this->b_contact = true; }
    public function setEmail($v) { $this->ab_fields["Email__baddress"] = $v; $this->b_contact = true; }

    static public function GetStatusList()
    {
        return array(
            "Engineering",
            "Customer", 
            "Network Administration",
            "Support Agency",
            "Vendor",
            "Resolved",
            "Closed");
    }
    public function setStatus($v) { $this->status = $v; $this->b_status = true; }

    static public function GetPriorityList()
    {
        return array(
            "1" => "Critical",
            "2" => "High",
            "3" => "Elevated",
            "4" => "Normal"
        );
    }

    public function setPriority($v) { $this->priority_number = $v; $this->b_priority = true; } 

    public function addDescription($v) { 
        $this->description .= $v; 
        $this->b_desc = true;
    }
    public function addMeta($v) { 
        $this->meta .= trim($v)."\n"; //make sure there is newline at the end
        $this->b_desc = true;
    }
    public function setMetadata($key, $value) {
        $this->metadata[$key] = $value;
    }
    public function getMetadata($key) {
        return $this->metadata[$key];
    }

    public function isValidAssignee($assignee) {
        $schema_model = new Schema();
        $teams = $schema_model->getteams();
        foreach($teams as $team) {
            $fp_scs = explode(",", $team->members);
            foreach($fp_scs as $fp_sc) {
                $sc = Footprint::parse($fp_sc);
                if($sc == $assignee) {
                    return true;
                }
            }
        }
        return false;
    }
    public function addAssignee($v, $bClear = false) { 
        if($this->isValidAssignee($v)) {
            if($bClear) {
                $this->resetAssignee();
            } 
            $this->assignees[] = $v;//no unparsing necessary
            $this->b_assignees = true;
        } else {
            $this->addMeta("Couldn't add assignee $v since it doesn't exist on FP yet.. (Please sync!)\n");
            elog("Couldn't add assignee $v since it doesn't exist on FP yet.. (Please sync!)\n");
        }
    }

    //setting this means that we are doing ticket update
    public function setID($id)
    {
        $this->id = $id;
    }

    //from the current db, I see following ticket types 
    static public function getTicketTypes()
    {
        return array(
            "Problem/Request",
            //"Scheduled Maintenance",
            //"Unscheduled Outage",
            //"Provision/Modify/Decom",
            //"Field Service Request",
            //"RMA",
            "Security",
            "Security_Notification",
            "User Certificate Request",
            "Host Certificate Request"
        );
    }

    public function setTicketType($type) {
        $this->project_fields["Ticket__uType"] = $type;
        $this->b_proj = true;
    }

    //"Yes" or "No"
    public function setReadyToClose($close) {
        $this->project_fields["Ready__bto__bClose__Q"] = $close;
        $this->b_proj = true;
    }

    public function setNextAction($action) {
        $this->project_fields["ENG__bNext__bAction__bItem"] = $action;
        $this->b_proj = true;
    }
    public function setNextActionTime($time)
    {
        $this->project_fields["ENG__bNext__bAction__bDate__fTime__b__PUTC__p"] = date("Y-m-d H:i:s", $time);
        $this->b_proj = true;
    }

    //this is for resource
    public function addPrimaryAdminContact($resource_id)
    {
        $model = new Resource();
        $resource_name = $model->fetchName($resource_id);

        $prac_model = new PrimaryResourceAdminContact();
        $prac = $prac_model->fetch($resource_id);
        if($prac === false) {
            $this->addMeta("Primary Admin for ".$resource_name." couldn't be found in the OIM");
        } else {
            $this->addCC($prac->primary_email);
            $this->addMeta("Primary Admin for ".$resource_name." is ".$prac->name." and has been CC'd.\n");
            $this->b_cc = true;
        }
    }

    //this is for vo
    public function addPrimaryVOAdminContact($vo_id)
    {
        $model = new VO();
        $vo_name = $model->get($vo_id)->name;

        $prac_model = new PrimaryVOAdminContact();
        $prac = $prac_model->fetch($vo_id);
        if($prac === false) {
            $this->addMeta("Primary Admin for ".$vo_name." VO couldn't be found in the OIM");
        } else {
            $this->addCC($prac->primary_email);
            $this->addMeta("Primary Admin for ".$vo_name." VO is ".$prac->name." and has been CC'd.\n");
            $this->b_cc = true;
        }
    }

    public function addCC($address) {
        //validate emmail
        require_once("email_validator.php");
        if(validEmail($address)) {
            $this->permanent_cc[] = $address;
            $this->b_cc = true;
        } else {
            message("error", "Failed to validate email address: $address which is removed");
        }
    }

    private function chooseGOCAssignee()
    {
        $model = new NextAssignee();
        $assignee = $model->getNextAssignee();
        //$this->addMeta("Assignment Reason: ".$model->getReason()."\n");
        return $assignee;
    }

    private function lookupVOName($id)
    {
        $vo_model = new VO();
        $vos = $vo_model->fetchAll();
        foreach($vos as $vo) {
            if($vo->vo_id == $id) return $vo->short_name;
        }
        return "(unknown vo_id $id)";
    }

    //deprecated
    public function isValidFPSC($name)
    {
        $schema_model = new Schema();
        $teams = $schema_model->getteams();
        foreach($teams as $team) {
            if($team->team == "OSG__bSupport__bCenters" || $team->team == "Ticket__bExchange") {
            $fp_scs = explode(",", $team->members);
            foreach($fp_scs as $fp_sc) {
                $sc = Footprint::parse($fp_sc);    
                if($sc == $name) {
                    return true;
                }
            }
            }
        }
        return false;
    }

    public function prepareParams() {
        $desc = $this->description;
        if($this->meta != "") {
            $desc .= "\n\n".config()->metatag."\n";
            $desc .= $this->meta;
        }

        //populate params to insert/update
        $params = array();
        $params["mrID"] = $this->id;
        $params["projectID"] = config()->project_id;
        if($this->b_submitter) {
            $params["submitter"] = $this->submitter;
        }
        if($this->b_title) {
            $params["title"] = $this->title;
        }
        if($this->b_assignees) {
            $params["assignees"] = $this->assignees;
        }
        if($this->b_cc) {
            $params["permanentCCs"] = $this->permanent_cc;
        }
        if($this->b_priority) {
            $params["priorityNumber"] = $this->priority_number;
        }
        if($this->b_status) {
            $params["status"] = $this->status;
        }
        if($this->b_desc) {
            $params["description"] = $desc;
        }
        if($this->b_contact) {
            $params["abfields"] = $this->ab_fields;
        }
        if($this->b_proj) {
            $params["projfields"] = $this->project_fields;
        }

        //handle suppression request
        $params["mail"] = array();
        if($this->suppress_assignees) {
            slog("suppressing notification email for assignee.");
            $params["mail"]["assignees"]=0;
        }
        if($this->suppress_submitter) {
            slog("suppressing notification email for submitter(contact).");
            $params["mail"]["contact"]=0;
        }
        if($this->suppress_ccs) {
            slog("suppressing notification email for permanent CCs");
            $params["mail"]["permanentCCs"]=0;
        }

        /*
        //override to always suppress submitter / cc for security tickets (TICKET-84)
        if(isset($this->project_fields["Ticket__uType"])) {
            $type = $this->project_fields["Ticket__uType"];
            if($type == "Security" || $type == "Security_Notification") {
                slog("This is security/_notificatio nticket. Suppressing notification email for submitter / ccs");
                //why don't we suppress for assignee? because I don't know how to lookup email addresses to send to for each assignee
                $params["mail"]["contact"]=0;
                $params["mail"]["permanentCCs"]=0;
            }
        }
        */

        //don't pass empty mail array - FP API will throw up
        // -- Can't coerce array into hash at /usr/local/footprints//cgi/SUBS/MRWebServices/createIssue_goc.pl
        if(count($params["mail"]) == 0) {
            unset($params["mail"]);
        }

        return $params;
    }

    public function submit() {
        //determine if we are doing create or update
        if($this->id === null) {
            $call = "MRWebServices__createIssue_goc";
        } else {
            $call = "MRWebServices__editIssue_goc";
        }

        $params = $this->prepareParams();

        slog("[submit] Footprint Ticket Web API invoked with following parameters -------------------");
        slog(print_r($params, true));
        slog(print_r($this->metadata, true));

        if(config()->simulate) {
            //simulation doesn't submit the ticket - just dump the content out.. (and no id..)
            $this->id = print_r($params, true);

            $this->id.="[Metadata Dump]\n";
            foreach($this->metadata as $key=>$value) {
                $this->id.="$key: $value\n";
            }
            //slog($this->id);
        } else {
            //submit the ticket!
            slog("making fp call");
            $newid = fpCall($call, array(config()->webapi_user, config()->webapi_password, "", $params));
            slog("finished fp call");
            if(is_null($this->id)) {
                $this->id = $newid; //reset ticket ID with new ID that we just got
				slog("adding new id");
                //For new ticket, send SMS - if the ticket didn't come from other ticketing system.
                if(trim(@$this->project_fields["Originating__bTicket__bNumber"]) == "") {
                	
                    $this->send_notification($params["assignees"], $this->id);
                }
				slog("sending notification");
                $this->sendEventNotification(true); //true == new ticket notification
            } else {
                $this->sendEventNotification(false); //false == ticket update notification
            }

            //if assignee notification was suppressed, then send GOC-TX trigger email ourselves
            if(@$params["mail"]["assignees"] === 0) {
                slog("sending trigger emails to GOC-TX");
                $this->sendGOCTXTrigger($this->assignees, $this->id);
            }
			slog("store metadata");
            //store metadata
            $data = new Data();
            foreach($this->metadata as $key=>$value) {
                $data->setMetadata($this->id, $key, $value);
                slog("Setmetadata : $this->id $key = $value");
            }
        }
        return $this->id;
    }

    private function sendEventNotification($newticket) {
    	slog("in sendEventNotification");
        $type = "n/a";
        if(isset($this->project_fields["Ticket__uType"])) {
        	slog("ticket type");
            $type = $this->project_fields["Ticket__uType"];
        }
        /* TICKET-84 is now undone
        if($type == "Security" || $type == "Security_Notification") {
            slog("This is security/_notification ticket. Sending signed email notification - instead of publishing to EventPublisher");
            $e = new Email();
            $e->setSubject($this->title);
            $e->setTo($this->ab_fields["Email__baddress"]);  //customer
            foreach($this->permanent_cc as $cc) {
                $e->addAddress($cc);
            }
            $date = date("D M j G:i:s T Y");
            if($newticket) {
                $msg = "New $type ticket was opened on $date.";
            } else {
                $msg = "$type ticket has been updated on $date.";
            }
            $e->setBody("$msg\n\nPlease visit ".fullbase()."/".$this->id);
            $e->addAddress("hayashis+test@iu.edu");
            $e->setSign();
            $e->send();
        } else {
        */

        //prepare message to publish on event server
        slog("new event publisher");
        $event = new EventPublisher();
        $msg = "<ticket>";
        $msg .= "<submitter>".htmlspecialchars($this->submitter_name)."</submitter>";
        $msg .= "<title>".htmlspecialchars($this->title)."</title>";
        $msg .= "<description>".htmlspecialchars($this->description)."</description>";
        $msg .= "<status>".htmlspecialchars($this->status)."</status>";
        $msg .= "</ticket>";

        if($newticket) {
        	slog("new ticket");
            $event->publish($msg, $this->id.".create");
            slog("published");
        } else {
            //ticket updated
            slog("updating ticket");
            $event->publish($msg, $this->id.".update");
        }
        //}
    }

    private function sendGOCTXTrigger($assignees, $id) {
        $model = new Schema();
        $emails = $model->getemail();
        foreach($assignees as $assignee) {
            $email = $emails[$assignee];
            //is this TX trigger address? (it starts with tx+)
            if(strpos($email, "tx+") === 0) {
               slog("sending trigger to $assignee($email) for issue $id");
               if(!mail($email, "ISSUE=".$id." PROJ=".config()->project_id, "trigger email...")) {
                    elog("Failed to send trigger to $assignee($email) for issue $id");
               }
            }
        }
    }

    private function send_notification($assignees, $id) 
    {
        //send SMS notification to assignees
        $sms_users = config()->sms_notification[$this->priority_number];
        $sms_to = array();
        //pick users to send to..
        foreach($assignees as $ass) {
            if(in_array($ass, $sms_users)) {
                $sms_to[] = $ass;
            }
        }
        if(count($sms_to) > 0) {
            dlog("sending SMS notification to ".print_r($sms_to, true));
            $subject = "";
            $body = "";
            $subject = Footprint::getPriority($this->priority_number);
            $subject .= " Ticket ID:$id has been submitted";
            $body .= $this->title."\n".$this->description;

            //truncate body
            $body = substr($body, 0, 100)."...";

            sendSMS($sms_to, $subject, $body);
        }
    }

    static public function getPriority($number) {
        switch($number) {
        case 1: return "Critical";
        case 2: return "High Priority";
        case 3: return "Elevated";
        case 4: return "Normal";
        }
        return "(Unknown Priority)";
    }

    static public function parse($str)
    {
        $str = str_replace("__u", "-", $str);
        $str = str_replace("__b", " ", $str);
        $str = str_replace("__f", "/", $str);
        $str = str_replace("__P", "(", $str);
        $str = str_replace("__p", ")", $str);
        return $str;
    }
    static public function unparse($str)
    {
        $str = str_replace("-", "__u", $str);
        $str = str_replace(" ", "__b", $str);
        $str = str_replace("/", "__f", $str);
        $str = str_replace("(", "__P", $str);
        $str = str_replace(")", "__p", $str);
        return $str;
    }
    static public function preserve_whitespace($str)
    {
        $str = str_replace("^ ", ". ", $str);
        $str = str_replace("^\t", ".\t", $str);
        $str = str_replace("\n ", "\n. ", $str);
        $str = str_replace("\n\t", "\n.\t", $str);
        return $str;
    }
}


