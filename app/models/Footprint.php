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
        $this->permanent_cc = array();
        $this->title = "no title";

        $this->assignees = array($this->chooseAssignee()); //remove hayashis later

        //DEBUG
        $this->assignees = array("tsilver");
        //$this->assignees = array("hayashis");

        $this->ab_fields = array();
        $this->project_fields = array();
        $this->project_fields["ENG__bNext__bAction__bItem"] = "ENG Action";
        $this->setNextActionTime(time() + 3600*24*7); //set next action time
    }

    //AB fields
    public function setTitle($v) { $this->title = $v; } //ticket title
    public function setFirstName($v) { $this->ab_fields["First__bName"] = $v; }
    public function setLastName($v) { $this->ab_fields["Last__bName"] = $v; }
    public function setOfficePhone($v) { $this->ab_fields["Office__bPhone"] = $v; }
    public function setEmail($v) { $this->ab_fields["Email__baddress"] = $v; }

    public function addDescription($v) { 
        $this->description .= $v; 
    }

    public function setNextActionTime($time)
    {
        $this->project_fields["ENG__bNext__bAction__bDate__fTime__b__PUTC__p"] = date("Y-m-d H:i:s", $time);
    }

    public function setVO($v) { 
        $name = $this->lookupFootprintVOName($v);
        $this->project_fields["Originating__bVO__bSupport__bCenter"] = $name; 
    }

    public function setVORequested($v) { 
        $name = $this->lookupFootprintVOName($v);
        $this->description .= "\n(META) User is requesting a membership at $name";
        $this->project_fields["Destination__bVO__bSupport__bCenter"]= $name;
        //$this->assignees[] = $name;
    }

    public function setResourceWithIssue($resource_id) { 
        $rs_model = new ResourceSite();
        $resource = $rs_model->fetch($resource_id);
        $this->description .= "\n(META) Resource where user is having this issue: ".$resource->resource_name."($resource_id)\n";

        $fp_sc_name = $this->lookupFootprintVOName($resource->sc_id);
        $this->project_fields["Destination__bVO__bSupport__bCenter"] = $fp_sc_name;
        $this->assignees[] = $fp_sc_name;

        //find primary resource admin email
        $prac_model = new PrimaryResourceAdminContact();
        $prac = $prac_model->fetch($resource_id);

        $this->permanent_cc[] = $prac->primary_email;
        $this->description .= "(META) Primary Admin for ".$resource->resource_name." is ".$prac->first_name." ".$prac->last_name." and has been CC'd regarding this ticket.";
    }

    private function chooseAssignee()
    {
        //randomly pick one of the GOCers
        $gocers = array("kagross", "echism", "tsiler");
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

    private function lookupFootprintVOName($id)
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
        $client = new SoapClient(null, array(
            'location' => "http://tick.globalnoc.iu.edu/MRcgi/MRWebServices.pl",
            'uri'      => "http://tick.globalnoc.iu.edu/MRWebServices"));

        $ret = $client->__soapCall("MRWebServices__createIssue_goc",
            array(config()->webapi_user, config()->webapi_password, "",
                array(
                    "projectID"=>71,
                    "submitter"=>"OSG-GOC",
                    "title" => $this->title,
                    "assignees" => $this->assignees,
                    "permanentCCs" => $this->permanent_cc,
                    "priorityNumber" => $this->priority_number,
                    "status" => $this->status,
                    "description" => $this->description,
                    "abfields" => $this->ab_fields,
                    "projfields" => $this->project_fields
                )
            )
        );

        return $ret;
    }
}


