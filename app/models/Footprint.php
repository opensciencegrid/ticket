<?

//require_once("app/httpspost.php");

class Footprint
{
    public function __construct()
    {
        $this->project_id = "71";
        $this->project_name  = "Open Science Grid";
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
        $this->project_fields["ENG__bNext__bAction__bItem"] = "ENG Action";
        //$this->setNextActionTime(time() + 3600*24); //set next action time
        $this->setNextActionTime(time());

        //since these are required items, let's set to other
        $this->setOriginatingVO("other"); 
        $this->setDestinationVO("other"); 

        $this->setTicketType("Problem__fRequest");
        $this->setReadyToClose("No");
    }

    public function resetAssignee()
    {
        $this->assignees = array(
            "OSG__bGOC__bSupport__bTeam", 
            "OSG__bSupport__bCenters");

        //DEBUG
        //$this->assignees = array("tsilver");
        //$this->assignees = array("hayashis");
    }

    //AB fields
    public function setTitle($v) { $this->title = $v; } //ticket title
    public function setFirstName($v) { $this->ab_fields["First__bName"] = $v; }
    public function setLastName($v) { $this->ab_fields["Last__bName"] = $v; }
    public function setOfficePhone($v) { $this->ab_fields["Office__bPhone"] = $v; }
    public function setEmail($v) { $this->ab_fields["Email__baddress"] = $v; }

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
        $this->assignees[] = $v; 
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

    public function setDestinationVO($voname) { 
        if(!$this->isValidFPDestinationVO($voname)) {
            $this->addMeta("Couldn't set DestinationVO to $voname - No such VO in FP (please sync!)\n");
            $voname = "other";
        }
        $this->project_fields["Destination__bVO__bSupport__bCenter"]= Footprint::unparse($voname);
    }

    public function addCC($address) {
        $this->permanent_cc[] = $address;
    }

    private function chooseGOCAssignee()
    {
        //randomly pick one of the GOCers
        $gocers = array("kagross", "echism");
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
        $params = array(
            "projectID"=>71,
            "submitter"=>"OSG-GOC",
            "title" => $this->title,
            "assignees" => $this->assignees,
            "permanentCCs" => $this->permanent_cc,
            "priorityNumber" => $this->priority_number,
            "status" => $this->status,
            "description" => $desc,
            "abfields" => $this->ab_fields,
            "projfields" => $this->project_fields
        );

        if(config()->simulate) {
            //simulation doesn't submit the ticket - just dump the content out..
            echo "<pre>";
            var_dump($params);
            echo "<\pre>";
            return 999; //bogus ticket id
        } else {
            //submit the ticket!
            $client = new SoapClient(null, array(
                'location' => "https://tick.globalnoc.iu.edu/MRcgi/MRWebServices.pl",
                'uri'      => "https://tick.globalnoc.iu.edu/MRWebServices"));

            $ret = $client->__soapCall("MRWebServices__createIssue_goc",
                array(config()->webapi_user, config()->webapi_password, "", $params));

            return $ret;
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


