<?

class ViewerController extends Zend_Controller_Action 
{ 
    public function init()
    {
        $this->view->submenu_selected = "view";
    }    
    public function indexAction() 
    { 
        $dirty_id = $_REQUEST["id"];
        $id = (int)$dirty_id;
        
        $detail = $this->getDetail($id);
        if($detail === "") {
            $this->render("nosuchticket");
            return;
        } 

        //prevent security ticket to be accessible
        if($detail->Ticket__uType == "Security") {
            //only certain users can see security ticket
            if(!in_array(role::$see_security_ticket, user()->roles)) {
                $this->render("security");
                return;
            } else {
                $this->view->warning = "You are authorized to see the security ticket";
            }
        }
        
        $this->view->ticket_id = $id;
        $this->view->page_title = "[$id] ".$detail->title;

        //submitter 
        $this->view->submitter_name = $detail->First__bName." ".$detail->Last__bName;

        $this->view->submitter_email = $detail->Email__baddress;
        //$this->view->submitter_email = str_replace("@", " _at_ ", $this->view->submitter_email);
        $this->view->cc = $detail->Email__baddress;

        $this->view->submitter_phone = $detail->Office__bPhone;
        $this->view->submitter_vo = Footprint::parse($detail->Originating__bVO__bSupport__bCenter);

        //ticket info
        $this->view->status = Footprint::parse($detail->status);
        $this->view->priority = Footprint::priority2str($detail->priority);
        $this->view->assignees = "";
        $this->view->cc = "";

        $aka_model = new AKA();
        $model = new Schema();
        $teams = array();
        foreach($model->getteams() as $team) {
            $team = Footprint::parse($team->team);
            $teams[] = $team;
        } 

        foreach(split(" ", $detail->assignees) as $a) {
            if(strlen($a) >= 3 and strpos($a, "CC:") === 0) {
                $this->view->cc .= substr($a, 3)."<br/>";
                continue;
            }
    
            //lookup AKA
            $ass = Footprint::parse($a);
            $aka = $aka_model->lookupName($ass);
            if($aka !== null) $ass = $aka;

            //lookup team
            if(in_array($ass, $teams)) {
                //this is a team name
                $this->view->assignees .= "<h4>".$ass."</h4>";
            } else {
                $this->view->assignees .= $ass."<br/>";
            }
        }
        $this->view->destination_vo = Footprint::parse($detail->Destination__bVO__bSupport__bCenter);
        $this->view->nad = $detail->ENG__bNext__bAction__bDate__fTime__b__PUTC__p;
        $this->view->ready_to_close = $detail->Ready__bto__bClose__Q;
        $this->view->ticket_type = Footprint::parse($detail->Ticket__uType);

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
            $time = strtotime(str_replace(" at ", "", $info_a[0]));
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
                $time = strtotime($fields[0].$fields[1]);
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

    public function getDetail($id)
    {
        dlog("getDetail::soap - $id");
        $client = new SoapClient(null, 
            array(      'location' => "https://tick.globalnoc.iu.edu/MRcgi/MRWebServices.pl",
                        'uri'      => "https://tick.globalnoc.iu.edu/MRWebServices"));
        $ret = $client->__soapCall("MRWebServices__getIssueDetails_goc", 
            array(config()->webapi_user, config()->webapi_password, "", 71, $id));
        dlog("done");
        return $ret;
    }
}

/*
function cmp_desc($a, $b)
{
    if($a["time"] == $b["time"]) return 0;
    return ($a["time"] > $b["time"]) ? -1 : 1;
}
*/
