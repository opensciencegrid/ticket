<?

class AdminController extends BaseController
{ 
    public function init()
    {
        //don't use this - we are really doing 2 things here..
        //1) rendering the real admin page via browser
        //2) accepting request from cron from localhost
    }

    public function indexAction()
    {
        $this->view->submenu_selected = "admin";

        if(!in_array(role::$goc_admin, user()->roles)) {
            $this->render("error/404", null, true);
            return;
        }
    }

    private function accesscheck()
    {
        //make sure the request originated from localhost
        if($_SERVER["REMOTE_ADDR"] != $_SERVER["SERVER_ADDR"]) {
            //pretend that this page doesn't exist
            $this->getResponse()->setRawHeader('HTTP/1.1 404 Not Found');
            echo "local access only";
            exit;
        }
    }
    public function logrotateAction()
    {
        $this->accesscheck();

        dlog("Writing config file for logrotate...");
        $root = getcwd()."/";
        $statepath = "/tmp/ticket.rotate.state";
        $config = "compress \n".
            $root.config()->logfile. " ". 
            $root.config()->error_logfile. " ". 
            $root.config()->audit_logfile." {\n".
            "   rotate 5\n".
            "   size=50M\n".
            "}";
        $confpath = "/tmp/ticket.rotate.conf";
        $fp = fopen($confpath, "w");
        fwrite($fp, $config);
        
        dlog("running logroate with following config\n$config");
        passthru("/usr/sbin/logrotate -s $statepath $confpath");
    }

    public function groupticketAction()
    {
        $this->accesscheck();

        $model = new Tickets();
        $tickets = $model->getrecent();
        header('content-type: text/xml');
        echo "<groups>";

        $grouped = array();
        while(sizeof($tickets) > 1) {
            $master = array_shift($tickets);
            if(in_array($master->mrid, $grouped)) {
                //already in other group..
                continue;
            }

            $master_desc = $this->grabwhatmatters($master);
            $master_size = strlen($master_desc);
            $date = $master->mrupdatedate;
            echo "<group>";
            $this->outputgroup($master, $master_desc, 100); //master get 100% score
            foreach($tickets as $ticket) {
                if(!in_array($ticket->mrid, $grouped)) {
                    $ticket_desc = $this->grabwhatmatters($ticket);
                    $ticket_size = strlen($ticket_desc);
                    similar_text($master_desc, $ticket_desc, $p);
                    $p = round($p, 2);
                    if($p > 40) {
                        $this->outputgroup($ticket, $ticket_desc, $p);
                        $grouped[] = $ticket->mrid;
                    }
                }
            }
            echo "</group>";
        }
        echo "</groups>";

        $this->render("none", true);
    }

    private function outputgroup($ticket, $content, $p) 
    {
        echo "<ticket>";
        echo "<id>$ticket->mrid</id>";
        echo "<title>".$this->formattitle($ticket->mrtitle)."</title>";
        echo "<status>".Footprint::parse($ticket->mrstatus)."</status>";
        echo "<dest>$ticket->mrdest</dest>";
        echo "<url>https://oim.grid.iu.edu/gocticket/viewer?id=$ticket->mrid</url>";
        echo "<desc><![CDATA[$content]]></desc>";
        echo "<score>$p</score>";
        echo "</ticket>";
    }

    private function formattitle($title)
    {
        $title = preg_replace('/[^(\x20-\x7F)]*/','', $title);
        $title = htmlentities($title, ENT_QUOTES);
        return $title;
    }

    private function grabwhatmatters($ticket)
    {
        $desc_all = $ticket->mralldescriptions;

        //use the first description
        $desc_sections = preg_split("/<! [^>]* >/", $desc_all, 2, PREG_SPLIT_NO_EMPTY);
        $desc = trim($desc_sections[0]);

        //clean up some html ness
        $desc = html_entity_decode($desc, ENT_QUOTES);
        //$desc = preg_replace("/&[a-z]+;/", "?", $desc);

        //truncate if it's too long
        $desc = substr($desc, 0, 1000);

        //remove standard GOC signature
        $sig = "OSG Grid Operations Center
goc@opensciencegrid.org, 317-278-9699
Visit the OSG Support Page:
http://www.opensciencegrid.org/ops
RSS: http://www.grid.iu.edu/news";
        $desc = str_replace($sig, "", $desc);

        //remove Thank you note..
        $sig = "/\nThank you(.|\n)*/i";
        $desc = preg_replace($sig, "", $desc);
        $sig = "/\nThanks(.|\n)*/i";
        $desc = preg_replace($sig, "", $desc);

        //remove meta info
        $sig = "/\n\[META Information for OSG GOC Support Staff\](.|\n)*/";
        $desc = preg_replace($sig, "", $desc);

        //remove GGUS Info
        $sig = "/Other GGUS Ticket Info:(.|\n)*/"; //old format
        $desc = preg_replace($sig, "", $desc);
        $sig = "/\n\[Other GGUS Ticket Info\](.|\n)*/"; //new format
        $desc = preg_replace($sig, "", $desc);

        return $desc;
    }    
    public function quoteAction()
    {
        echo $_REQUEST["xml"];
        $this->render("none", true);
    }

    public function ggussubmitAction()
    {
        //TODO - restrict action to tick-indy server

        if(isset($_REQUEST["xml"])) {
            $xml_content = $_REQUEST["xml"];
            $xml = new SimpleXMLElement($xml_content);

            $footprint = new Footprint;

            $node = "GHD_Request-ID";
            $id = (int)$xml->$node;
            $footprint->setOriginatingTicketNumber($id);

            //check if the ticket is already in FP
            $model = new Tickets(); 
            dlog("searching for $id");
            $orig = $model->getoriginating($id);
            if(count($orig) == 0) {
                slog("inserting new FP ticket $id");
                $footprint->addDescription($xml->GHD_Description);
                $desc = "\n
[Other GGUS Ticket Info]
Date GGUS Ticket Opened: $xml->GHD_Date_Time_Of_Problem
GGUS Ticket ID:          $id
Short Description:       $xml->GHD_Short_Description
Solution:                $xml->GHD_Short_Solution
Experiment:              $xml->GHD_Experiment
Affected Site:           $xml->GHD_Affected_Site
Responsible Unit:        $xml->GHD_Responsible_Unit";
                $footprint->addDescription($desc);
            } else {
                //cause FP ticket update by setting ticket ID.
                $fpid = $orig[0]->mrid;
                slog(print_r($orig, true));
                slog("Originating ticket $id already exists in FP as $fpid . Doing Update..");

                //I don't know which one of these fields really contain the update-description..
                $footprint->addDescription($xml->GHD_Public_Diary);
                $footprint->addDescription($xml->GHD_Diary_Of_Steps); 
                $footprint->addDescription($xml->GHD_Internal_Diary);

                $footprint->setID($fpid);
            }

            //populate ticket info
            $this->populateGGUSTicket($footprint, $xml);

            //send data to FP
            try
            {
                $mrid = $footprint->submit();
                dlog("GGUS Ticket insert / update success - FP Ticket ID $mrid");
            } catch(exception $e) {
                $this->sendErrorEmail($e);
            }
        }
        $this->render("none", true);
    }

    private function populateGGUSTicket($footprint, $xml)
    {
        //contact info
        $fullname = split(" ", $xml->GHD_Name);
        $footprint->setFirstName($fullname[0]);
        $footprint->setLastName($fullname[1]);
        $footprint->setOfficePhone((string)$xml->GHD_Phone);
        $node = "GHD_E-Mail";
        $footprint->setEmail((string)$xml->$node);
        $footprint->setSubmitter("ggus");

        //title
        $title = str_replace("\n", "", $xml->GHD_Short_Description);
        $footprint->setTitle($title);

        $footprint->setOriginatingVO("Ops"); 
        $footprint->setNextAction("Operator Review");

        //lookup resource from resource name
        if(isset($xml->GHD_Affected_Site)) {
            dlog("setting affected resource info");

            $model = new Resource();
            $name = (string)$xml->GHD_Affected_Site;
            $resource_id = $model->fetchID($name);
            if($resource_id === false) {
                $footprint->addMeta("Resource '$name' as specified in the GHD_Affected_Site field couldn't be found in OIM.");
            } else {
                $rs_model = new ResourceSite();
                $resource = $rs_model->fetch($resource_id);

                //set description destination vo, assignee
                $footprint->addMeta("Resource where user is having this issue: ".$name."($resource_id)\n");

                //lookup SC name
                if($resource === false) {
                    $scname = "OSG-GOC";
                    $footprint->addMeta("Couldn't find the SC associated with this resource. Please see finderror page for more detail.");
                } else {
                    $scname = $footprint->setDestinationVOFromSC($resource->sc_id);
                }

                if($footprint->isValidFPSC($scname)) {
                    $footprint->addAssignee($scname);
                } else {
                    $footprint->addMeta("Couldn't add assignee $scname since it doesn't exist on FP yet.. (Please sync!)\n");
                }
                $footprint->addPrimaryAdminContact($resource_id);
            }
        }
    }

/*
<GHD_Request-ID>606</GHD_Request-ID>
<GHD_Loginname>/O=GermanGrid/OU=FZK/CN=Guenter
Grein</GHD_Loginname>
<GHD_Name>Guenter Grein</GHD_Name>
<GHD_E-Mail>guenter.grein@iwr.fzk.de</GHD_E-Mail>
<GHD_Phone></GHD_Phone>
<GHD_Experiment>atlas</GHD_Experiment>
<GHD_Responsible_Unit>OSG</GHD_Responsible_Unit>
<GHD_Status>assigned</GHD_Status>
<GHD_Priority>less urgent</GHD_Priority>
<GHD_Short_Description>new test</GHD_Short_Description>
<GHD_Description>new test</GHD_Description>

<GHD_Experiment_Specific_Problem>No</GHD_Experiment_Specific_Problem>
<GHD_Type_Of_Problem>GGUS Internal Tests</GHD_Type_Of_Problem>
<GHD_Date_Time_Of_Problem>2009-01-12
14:12:17</GHD_Date_Time_Of_Problem>
<GHD_Diary_Of_Steps></GHD_Diary_Of_Steps>
<GHD_Public_Diary></GHD_Public_Diary>
<GHD_Short_Solution></GHD_Short_Solution>
<GHD_Detailed_Solution></GHD_Detailed_Solution>
<GHD_Internal_Diary></GHD_Internal_Diary>
<GHD_Origin_ID></GHD_Origin_ID>
<GHD_Last_Modifier>Paul Mustermann</GHD_Last_Modifier>
<GHD_Affected_Site>BNL_ATLAS_1</GHD_Affected_Site>
</Ticket>

I guess the most important information for you is
<GHD_Experiment>atlas</GHD_Experiment>
<GHD_Affected_Site>BNL_ATLAS_1</GHD_Affected_Site>
<GHD_Responsible_Unit>OSG</GHD_Responsible_Unit>
*/
} 
