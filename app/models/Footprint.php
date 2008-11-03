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

        $this->ab_fields = array();
        $this->project_fields = array();
        $this->project_fields["ENG__bNext__bAction__bItem"] = "ENG Action";
        //$this->setNextActionTime(time() + 3600*24); //set next action time
        $this->setNextActionTime(time());

        //since these are required items, let's set to OSG-GOC by default..
        $this->setOriginatingVO(2); //CSC
        $this->setDestinationVO(21); //OSG-GOC
    }

    public function resetAssignee()
    {
        $this->assignees = array(
            "OSG__bGOC__bSupport__bTeam", 
            "OSG__bSupport__bCenters",
            $this->chooseGOCAssignee());

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
            //$this->assignees = array();
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

    public function setNextActionTime($time)
    {
        $this->project_fields["ENG__bNext__bAction__bDate__fTime__b__PUTC__p"] = date("Y-m-d H:i:s", $time);
    }

    public function setOriginatingVO($v) { 
        $name = $this->lookupFootprintVOName($v);
        $this->project_fields["Originating__bVO__bSupport__bCenter"] = $name; 
    }

    public function setDestinationVO($v) { 
        $name = $this->lookupFootprintVOName($v);
        $this->project_fields["Destination__bVO__bSupport__bCenter"]= $name;
    }

    public function addCC($address) {
        $this->permanent_cc[] = $address;
    }

    private function chooseGOCAssignee()
    {
        //randomly pick one of the GOCers
        $gocers = array("kagross", "echism", "tsilver");
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

    //I am really confusing vo v.s. sc..
    public function lookupFootprintVOName($id)
    {
        static $id2name = array(
/*
//following SC only exists in Footprint Schema
CDF 
GGUS 
GIP Support 
GRATIA 
ILC 
OSG 
ReSS-Ops 
RSV-Ops 
Troubleshooting 
*/
-1=>"other", 
2=>"CIGI",
3=>"CSC",    //"CSC"
4=>"DOSAR",     //"DOSAR"
5=>"DZero",     //"DZero"
6=>"Engagement",     //"Engagement"
7=>"Fermilab",     //"Fermilab"
8=>"fGOC",     //"fGOC"
9=>"GADU",    //"GADU"
10=>"GLOW-TECH",    //"GLOW-TECH"
11=>"GPN",    //"GPN"
12=>"GRASE",    //"GRASE"
14=>"GUGrid",    //"GUGrid"
15=>"LBNL-DSD-suppot",    //"LBNL-DSD-support"
16=>"LIGO",    //"LIGO"
17=>"mariach-support",    //"mariach-support"
18=>"nanoHUB",    //"nanoHUB-SC"
19=>"NERSC",    //"NERSC"
20=>"NWICG",    //"NWICG"
21=>"OSG-GOC",    //"OSG-GOC"
22=>"PROD_SLAC",    //"PROD_SLAC"
23=>"SBGrid",    //"SBGrid"
24=>"SDSS",    //"SDSS"
25=>"STAR",    //"STAR"
26=>"TIGRE",    //"TIGRE"
27=>"UC CI",    //"UC CI"
28=>"UCHC",    //"UCHC"
29=>"USATLAS",    //"USATLAS"
30=>"USCMS",    //"USCMS"
31=>"VDT",    //"VDT"
34=>"SBGrid",    //"SBGrid"
        );
        return $id2name[$id];
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
}


