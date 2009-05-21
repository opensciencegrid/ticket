<?

//require_once("app/httpspost.php");

class Footprint
{
    //if id is null, we will do insert. If not, update
    public function __construct($id = null)
    {
        $this->id = $id; 

        $this->submitter = "OSG-GOC";
        $this->status = "Engineering";
        $this->priority_number = "4";
        $this->description = "";
        $this->title = "no title";
        $this->meta = "";
        $this->resetCC();
        $this->resetAssignee();
        $this->ab_fields = array();
        $this->project_fields = array();
        $this->send_no_ae = false;

        //process update flags
        if($id === null) {
            //insert
            $f = true; //insert everything by default
            $this->addAssignee($this->chooseGOCAssignee()); //auto assign someone
            $this->setOriginatingVO("other"); 
            $this->setDestinationVO("other"); 
            $this->setNextAction("ENG Action");
            $this->setNextActionTime(time());
            $this->setTicketType("Problem__fRequest");
            $this->setReadyToClose("No");
        } else {
            //update
            $f = false; //don't update anything by default
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
    public function setName($v) { 
        list($first_name, $last_name)  = split(" ", $v, 2);
        $this->ab_fields["First__bName"] = $first_name;
        $this->ab_fields["Last__bName"] = $last_name;
        $this->b_contact = true; 
    }
    public function setOfficePhone($v) { $this->ab_fields["Office__bPhone"] = $v; $this->b_contact = true; }
    public function setEmail($v) { $this->ab_fields["Email__baddress"] = $v; $this->b_contact = true; }
    public function sendNoAEmail() { $this->send_no_ae = true; } //set to not send assignee emails

    static public function GetStatusList()
    {
        return array(
            "Open", 
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
        $this->meta .= $v;
        $this->desc = true;
    }
    public function addAssignee($v, $bClear = false) { 
        if($bClear) {
            $this->resetAssignee();
        } 
        $this->assignees[] = $v;//no unparsing necessary
        $this->b_assignees = true;
    }

    //setting this means that we are doing ticket update
    public function setID($id)
    {
        $this->id = $id;
    }

    //from the current db, I see following ticket types //TODO - pull this from field schema
    static public function getTicketTypes()
    {
        return array(
            "Problem/Request",
            "Scheduled Maintenance",
            "Unscheduled Outage",
            "Provision/Modify/Decom",
            "Field Service Request",
            "RMA",
            "Security"
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

    public function setOriginatingVO($voname) { 
        if(!$this->isValidFPOriginatingVO($voname)) {
            $this->addMeta("Couldn't set Originating VO to $voname - No such VO in FP (please sync!)\n");
            $voname = "other";
        }
        $this->project_fields["Originating__bVO__bSupport__bCenter"] = Footprint::unparse($voname);
        $this->b_proj = true;
    }

    public function setOriginatingTicketNumber($id)
    {
        $this->project_fields["Originating__bTicket__bNumber"] = $id;
        $this->b_proj = true;
    }

    public function setDestinationTicketNumber($id)
    {
        $this->project_fields["Destination__bTicket__bNumber"] = $id;
        $this->b_proj = true;
    }

    public function setDestinationVO($voname) { 
        if(!$this->isValidFPDestinationVO($voname)) {
            $this->addMeta("Couldn't set DestinationVO to $voname - No such VO in FP (please sync!)\n");
            $voname = "other";
        }
        $this->project_fields["Destination__bVO__bSupport__bCenter"]= Footprint::unparse($voname);
        $this->b_proj = true;
    }

    public function setDestinationVOFromResourceID($resource_id)
    {
        $model = new Resource();
        $vo = $model->getPrimaryOwnerVO($resource_id);
        if($vo->footprints_id === null) {
            $this->addMeta("No VOs are associated with Resource $vo->name. Setting destination VO to other\n");
            $this->setDestinationVO("other");
        } else {
            $this->addMeta("Selecting $vo->vo_name(FP name: $vo->footprints_id) for Destination VO since it has the highest resource ownership.\n");
            $this->setDestinationVO($vo->footprints_id);
        }
    }

/*
    public function setDestinationVOFromSC($sc_id)
    {
        $sc_model = new SC;
        $sc = $sc_model->get($sc_id);
        $scname = $sc->footprints_id;

        //lookup VOs associated with this sc
        $vomodel = new VO();
        $vos = $vomodel->getfromsc($sc_id);
        if($vos === false or count($vos) == 0) {
            $this->addMeta("No VOs are associated with Support Center=$scname where this resource belongs.\n");
        } else {
            $this->addMeta("Following VOs are associated with Support Center=$scname where this resource belongs.\n");
            foreach($vos as $vo) {
                $this->addMeta("\t".$vo->short_name."\n");
            }
            $fpvo = $vos[0]->footprints_id;
            $oimvo = $vos[0]->short_name;
            $this->addMeta("Selecting $oimvo for Destination VO - just the first one in the list..\n");
            $this->setDestinationVO($fpvo);
        }

        return $scname;
    }
*/

    public function addPrimaryAdminContact($resource_id)
    {
        $model = new Resource();
        $resource_name = $model->fetchName($resource_id);

        $prac_model = new PrimaryResourceAdminContact();
        $prac = $prac_model->fetch($resource_id);
        if($prac === false) {
            $footprint->addMeta("Primary Admin for ".$resource_name." couldn't be found in the OIM");
        } else {
            $this->addCC($prac->primary_email);
            $this->addMeta("Primary Admin for ".$resource_name." is ".$prac->name." and has been CC'd.\n");
            $this->b_cc = true;
        }
    }

    public function addCC($address) {
        $this->permanent_cc[] = $address;
        $this->b_cc = true;
    }

    private function chooseGOCAssignee()
    {
        //randomly pick one of the GOCers
        $gocers = config()->goc_assignees;
        $lucky = rand(0, sizeof($gocers)-1);
        return $gocers[$lucky]; 
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

    public function isValidFPOriginatingVO($name)
    {
        $schema_model = new Schema();
        $vos = $schema_model->getoriginatingvos();
        foreach($vos as $vo) {
            $vo2 = Footprint::parse($vo);    
            if($name == $vo2) return true;
        } 
        return false;
    }

    public function isValidFPDestinationVO($name)
    {
        $schema_model = new Schema();
        $vos = $schema_model->getdestinationvos();
        foreach($vos as $vo) {
            $vo2 = Footprint::parse($vo);    
            if($name == $vo2) return true;
        } 
        return false;
    }

    public function isValidFPSC($name)
    {
        $schema_model = new Schema();
        $teams = $schema_model->getteams();
        foreach($teams as $team) {
            if($team->team == "OSG__bSupport__bCenters") {
                $fp_scs = split(",", $team->members);
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

    public function submit()
    {
        $desc = $this->description;
        if($this->meta != "") {
            $desc .= "\n\n".config()->metatag."\n";
            $desc .= $this->meta;
        }

        //determine if we are doing create or update
        if($this->id === null) {
            $call = "MRWebServices__createIssue_goc";
        } else {
            $call = "MRWebServices__editIssue_goc";
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
        if($this->send_no_ae) {
            //don't sent email to assignee
            $params["mail"] = array("assignees"=>0);
        }

        slog("[submit] Footprint Ticket Web API invoked with following parameters -------------------");
        slog(print_r($params, true));

        if(config()->simulate) {
            //simulation doesn't submit the ticket - just dump the content out.. (and no id..)
            $this->id = print_r($params, true);
        } else {
            //submit the ticket!
            $newid = fpCall($call, array(config()->webapi_user, config()->webapi_password, "", $params));
            if($this->id === null) {
                $this->id = $newid;
            }
            $this->send_notification($params["assignees"], $this->id);
        }

        return $this->id;
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
            switch($this->priority_number) {
            case 1: $subject .= "CRITICAL ";
                    break;
            case 2: $subject .= "HIGH Priority ";
                    break;
            case 3: $subject .= "ELEVATED ";
                    break;
            case 4: $subject .= "";
                    break;
            }
            $subject .= "Ticket ID:$id has been submitted";
            $body .= $this->title."\n".$this->description;

            //truncate body
            $body = substr($body, 0, 100)."...";

            sendSMS($sms_to, $subject, $body);
        }
    }

    static public function parse($str)
    {
        $str = str_replace("__u", "-", $str);
        $str = str_replace("__b", " ", $str);
        $str = str_replace("__f", "/", $str);
        return $str;
    }
    static public function unparse($str)
    {
        $str = str_replace("-", "__u", $str);
        $str = str_replace(" ", "__b", $str);
        $str = str_replace("/", "__f", $str);
        return $str;
    }
}


