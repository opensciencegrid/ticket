<?

class ViewerController extends Zend_Controller_Action 
{ 
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
            $this->render("security");
            return;
        }
        
        //submitter 
        $this->view->page_title = "[$id] ".$detail->title;
        $this->view->submitter_name = $detail->First__bName." ".$detail->Last__bName;

        $this->view->submitter_email = $detail->Email__baddress;
        //$this->view->submitter_email = str_replace("@", " _at_ ", $this->view->submitter_email);

        $this->view->submitter_phone = $detail->Office__bPhone;
        $this->view->submitter_sc = Footprint::parse($detail->Originating__bVO__bSupport__bCenter);

        //ticket info
        $this->view->status = Footprint::parse($detail->status);
        $this->view->priority = Footprint::priority2str($detail->priority);
        $this->view->assignees = "";
        foreach(split(" ", $detail->assignees) as $a) {
            if(strlen($a) >= 3 and strpos($a, "CC:") === 0) continue;//skip CC:xxx line
            $this->view->assignees.= Footprint::parse($a)."<br/>";
        }
        $this->view->destination_sc= Footprint::parse($detail->Destination__bVO__bSupport__bCenter);
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
            $info = $desc_lines[0];
            $desc = strstr($desc, "\n");
            $descs[] = array($info, trim($desc)); 
        }
        $this->view->descs = $descs;

        //debug
/*
        foreach($detail as $key=>$value) {
            echo $key."<br/>";
            echo "<blockquote><pre>".$value."</pre></blockquote>";
        }
*/
    }

    public function getDetail($id)
    {
        $client = new SoapClient(null, 
            array(      'location' => "https://tick.globalnoc.iu.edu/MRcgi/MRWebServices.pl",
                        'uri'      => "https://tick.globalnoc.iu.edu/MRWebServices"));
        $ret = $client->__soapCall("MRWebServices__getIssueDetails_goc", 
            array(config()->webapi_user, config()->webapi_password, "", 71, $id));
        return $ret;
    }

}
