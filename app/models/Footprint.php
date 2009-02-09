<?

//require_once("app/httpspost.php");

class Footprint
{
    public function __construct()
    {
        $this->id = null; //if this is not null, we will do edit instead of insert

        $this->submitter = "OSG-GOC";
        $this->status = "Engineering";
        $this->priority_number = "4";
        $this->description = "";
        $this->meta = "";
        $this->permanent_cc = array();
        $this->title = "no title";

        $this->resetAssignee();
        $this->addAssignee($this->chooseGOCAssignee());

        $this->ab_fields = array();
        $this->project_fields = array();
        $this->setNextAction("ENG Action");
        $this->setNextActionTime(time());

        //since these are required items, let's set to other
        $this->setOriginatingVO("other"); 
        $this->setDestinationVO("other"); 

        $this->setTicketType("Problem__fRequest");
        $this->setReadyToClose("No");

        $this->send_no_ae = false;
    }

    public function resetAssignee()
    {
        $this->assignees = array(
            "OSG__bGOC__bSupport__bTeam", 
            "OSG__bSupport__bCenters");
    }

    //AB fields
    public function setTitle($v) { $this->title = $v; } //ticket title
    public function setSubmitter($v) { $this->submitter = $v; }
    public function setFirstName($v) { $this->ab_fields["First__bName"] = $v; }
    public function setLastName($v) { $this->ab_fields["Last__bName"] = $v; }
    public function setOfficePhone($v) { $this->ab_fields["Office__bPhone"] = $v; }
    public function setEmail($v) { $this->ab_fields["Email__baddress"] = $v; }
    public function setStatus($v) { $this->status = $v; }
    public function sendNoAEmail() { $this->send_no_ae = true; }

    //1 - critical
    //2 - high
    //3 - elevated 
    //4 - normal 
    public function setPriority($v) { $this->priority_number = $v; } 
    static public function priority2str($p) {
        switch($p) {
        case 1: return "Critical";
        case 2: return "High";
        case 3: return "Elevated";
        case 4: return "Normal";
        }
        return "(Unknown)";
    }

    public function addDescription($v) { 
        $this->description .= $v; 
    }
    public function addMeta($v) { 
        $this->meta .= $v;
    }
    public function addAssignee($v, $bClear = false) { 
        if($bClear) {
            $this->resetAssignee();
        } 
        $this->assignees[] = $v;//no unparsing necessary
    }

    //setting this means that we are doing ticket update
    public function setID($id)
    {
        $this->id = $id;
    }

    //from the current db, I see following ticket types
/*
NULL
Problem__fRequest
Provision__fModify__fDecom
Scheduled__bMaintenance
Security
Unscheduled__bOutage
*/
    public function setTicketType($type) {
        $this->project_fields["Ticket__uType"] = $type;
    }


    //"Yes" or "No"
    public function setReadyToClose($close) {
        $this->project_fields["Ready__bto__bClose__Q"] = $close;
    }

    public function setNextAction($action) {
        $this->project_fields["ENG__bNext__bAction__bItem"] = $action;
    }
    public function setNextActionTime($time)
    {
        $this->project_fields["ENG__bNext__bAction__bDate__fTime__b__PUTC__p"] = date("Y-m-d H:i:s", $time);
    }

    public function setOriginatingVO($voname) { 
        if(!$this->isValidFPOriginatingVO($voname)) {
            $this->addMeta("Couldn't set Originating VO to $voname - No such VO in FP (please sync!)\n");
            $voname = "other";
        }
        $this->project_fields["Originating__bVO__bSupport__bCenter"] = Footprint::unparse($voname);
    }

    public function setOriginatingTicketNumber($id)
    {
        $this->project_fields["Originating__bTicket__bNumber"] = $id;
    }

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

    public function setDestinationVO($voname) { 
        if(!$this->isValidFPDestinationVO($voname)) {
            $this->addMeta("Couldn't set DestinationVO to $voname - No such VO in FP (please sync!)\n");
            $voname = "other";
        }
        $this->project_fields["Destination__bVO__bSupport__bCenter"]= Footprint::unparse($voname);
    }

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
            $this->addMeta("Primary Admin for ".$resource_name." is ".$prac->first_name." ".$prac->last_name." and has been CC'd regarding this ticket.\n");
        }
    }

    public function addCC($address) {
        $this->permanent_cc[] = $address;
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
            $params = array(
                "mrID"=>$this->id,
                "projectID"=>71,
                "submitter"=>$this->submitter,
                "title" => $this->title,
                "assignees" => $this->assignees,
                "permanentCCs" => $this->permanent_cc,
                "priorityNumber" => $this->priority_number,
                "status" => $this->status,
                "description" => $desc,
                "abfields" => $this->ab_fields,
                "projfields" => $this->project_fields
            );


        } else {
            $call = "MRWebServices__editIssue_goc";
            $params = array(
                "mrID"=>$this->id,
                "projectID"=>71,
                "submitter"=>$this->submitter,
                "status" => $this->status,
                "description" => $desc
            );
        }

        if($this->send_no_ae) {
            //don't sent email to assignee
            $params["mail"] = array("assignees"=>0);
        }

        slog("[submit] Footprint Ticket Web API invoked with following parameters -------------------");
        slog(print_r($params, true));

        if(config()->simulate) {
            //simulation doesn't submit the ticket - just dump the content out.. (and no id..)
            $id = print_r($params, true);
        } else {
            //submit the ticket!
            $client = new SoapClient(null, array(
                'location' => "https://tick.globalnoc.iu.edu/MRcgi/MRWebServices.pl",
                'uri'      => "https://tick.globalnoc.iu.edu/MRWebServices"));
            slog("calling $call");
            $id = $client->__soapCall($call, array(config()->webapi_user, config()->webapi_password, "", $params));
            $this->send_notification($params["assignees"], $id); //TODO - this only works for ticket create..
        }

        return $id;
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


