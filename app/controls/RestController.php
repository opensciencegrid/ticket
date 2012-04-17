<?

class RestController extends Zend_Controller_Action
{
    public function init()
    {
        header('content-type: text/xml'); 
        $this->render("none", null, true);
        if(!user()->isGOCMachine()) {
            echo "access error";
            exit;
        }
    }

    function indexAction()
    {
        echo "this controller provides various rest interface";
    }

    function getopencountsAction()
    {
        $model = new Tickets();
        $recs = $model->getopen();
        $counts = array();
        $recs_grouped = array();
        foreach($recs as $rec) {
            $assignees = $rec->mrassignees;
            foreach(explode(" ", $assignees) as $assignee) {
                $assignee = trim($assignee);
                if($assignee != "") {
                    if(isset($counts[$assignee])) {
                        $counts[$assignee]++;
                        $recs_grouped[$assignee][] = $rec;
                    } else {
                        $counts[$assignee] = 1;
                        $recs_grouped[$assignee] = array($rec);
                    }
                }
            }
        }
        echo "<OpenCounts>";
        foreach($counts as $id=>$count) {
            echo "<Assignee>";
            echo "<FootprintsID>".htmlspecialchars($id)."</FootprintsID><Count>$count</Count>";
            echo "<Tickets>";
            foreach($recs_grouped[$id] as $rec) {
                $nad = strtotime($rec->nad);
                echo "<Ticket><ID>$rec->mrid</ID><NAD>$nad</NAD><Priority>$rec->mrpriority</Priority><Status>$rec->mrstatus</Status></Ticket>";
            }
            echo "</Tickets>";
            echo "</Assignee>";
        }
        echo "</OpenCounts>";
    }

    function getnextassigneeAction()
    {
        $model = new NextAssignee();
        $assignee = $model->getNextAssignee();
        $reason = $model->getReason();

        echo "<NextAssignee>";
        echo "<FootprintsID>".htmlspecialchars($assignee)."</FootprintsID>";
        echo "<Reason>".htmlspecialchars($reason)."</Reason>";
        echo "</NextAssignee>";
    }

    function listopenAction()
    {
        $model = new Tickets();
        $tickets = $model->getopen();

        $meta = new Data();

        echo "<Tickets>";
        foreach($tickets as $ticket) {
            if($ticket->tickettype == "Security") {
                if(user()->isguest()) {
                    //don't show security ticket to guest user-
                    continue;
                }
            }

            echo "<Ticket>";
            echo "<ID>".$ticket->mrid."</ID>";
            echo "<Title>".htmlsafe($ticket->mrtitle)."</Title>";
            echo "<Priority>".htmlsafe(Footprint::getPriority($ticket->mrpriority))."</Priority>";
            echo "<Type>".htmlsafe(Footprint::parse($ticket->tickettype))."</Type>";
            echo "<NextAction>".htmlsafe($ticket->nextaction)."</NextAction>";
            echo "<NAD>".htmlsafe($ticket->nad)."</NAD>";
            echo "<URL>".fullbase()."/".$ticket->mrid."</URL>";
            echo "<Assignees>";
            foreach(explode(" ", $ticket->mrassignees) as $assignee) {
                if(substr($assignee, 0, 3) == "CC:") continue;
                if(trim($assignee) != "") {
                    echo "<Assignee>".htmlsafe($assignee)."</Assignee>";
                }
            }
            echo "</Assignees>";

            //pull metadata
            $recs = $meta->getAllMetadata($ticket->mrid);
            echo "<Metadata>";
            foreach($recs as $rec) {
                echo "<".$rec->key.">";
                echo htmlsafe($rec->value);
                echo "</".$rec->key.">";
            }
            echo "</Metadata>";

            echo "</Ticket>";
        }
        echo "</Tickets>";
    }

    function searchAction() {
        $q = $_REQUEST["q"];
        echo "<SearchResult>";
        echo "<Query>".htmlsafe($q)."</Query>";

        echo "<Tickets>"; 

        $final_list = array();

        //search for title
        $safe_q  = addslashes($q);
        $tokens = explode(" ", $safe_q);
        $where = "WHERE ";
        $first = true;
        foreach($tokens as $token) {
            if($first) $first = false;
            else $where .= " OR ";
            $where .= "mrTITLE LIKE '%".$token."%'";
        }
        $query = "select mrID, mrTITLE from MASTER".config()->project_id." $where";
        $tickets = fpCall("MRWebServices__search_goc", array(config()->webapi_user, config()->webapi_password, "", $query));
        foreach($tickets as $ticket) {
            if($this->containsTokens($tokens, $ticket, array())) {
                $final_list[$ticket->mrid] = $ticket;
            }
        }

        //search for metadata
        $model = new Data();
        $groups = $model->searchMetadataByValue($q);
        if(count($groups) > 0) {
            //add some meat
            $query = "SELECT mrID, mrTITLE from MASTER".config()->project_id." WHERE mrid in (".implode(",",array_keys($groups)).")";
            $tickets = fpCall("MRWebServices__search_goc", array(config()->webapi_user, config()->webapi_password, "", $query));
            //and add it to the list
            foreach($tickets as $ticket) {
                if($this->containsTokens($tokens, $ticket, $groups)) {
                    $final_list[$ticket->mrid] = $ticket;
                }
            }
        }

        //finally.. display tickets
        foreach($final_list as $ticket_id=>$ticket) {
            $title = htmlsafe($ticket->mrtitle);
            echo "<Ticket>";
            echo "<ID>$ticket_id</ID>";
            echo "<Title>$title</Title>";
            if(isset($groups[$ticket_id])) {
                echo "<Metadatas>";
                foreach($groups[$ticket_id] as $value) {
                    echo "<Metadata>";
                    echo "<Key>".htmlsafe($value->key)."</Key>";
                    echo "<Value>".htmlsafe($value->value)."</Value>";
                    echo "</Metadata>";
                }
                echo "</Metadatas>";
            }
            echo "</Ticket>";
        }
        echo "</Tickets>"; 
        echo "</SearchResult>";
    }

    function containsTokens($tokens, $ticket, $metadata) {
        $id = $ticket->mrid;
        foreach($tokens as $token) {
            $token = strtolower($token);
            //search in title
            if(strpos(strtolower($ticket->mrtitle), $token) !== FALSE) {
                continue; //found it.
            }

            //search in metadata value
            if(isset($metadata[$id])) {
                $found = false;
                foreach($metadata[$id] as $meta) {
                    if(strpos(strtolower($meta->value), $token) !== FALSE) {
                        $found = true;
                        break;
                    }
                }
                if($found) continue;
            }
            return false; //token failed
        }
        //passed all tokens
        return true;
    }

    //open ticket and return ticket id
    function openAction() {
        $footprint = new Footprint(null, false);

        //set basic info
        $footprint->setTitle($_POST["title"]);
        $footprint->addDescription($_POST["description"]);
        $footprint->setName($_POST["name"]);
        $footprint->setOfficePhone($_POST["phone"]);
        $footprint->setEmail($_POST["email"]);
        $footprint->setNextAction($_POST["nextaction"]);

        //set ccs
        $footprint->resetCC();
        foreach(@$_POST["cc"] as $cc) {
            $footprint->addCC($cc);
        }

        //set assignee
        $footprint->resetAssignee();
        foreach(@$_POST["assignee"] as $assignee) {
            $footprint->addAssignee($assignee);
        }

        //set metadata
        foreach(@$_POST["metadata"] as $kv) {
            $kv = explode("=", $kv, 2);
            $footprint->setMetadata($kv[0], $kv[1]);
        }

        $mrid = $footprint->submit();
        //TODO - need to deal with error condition
        echo "<Result><Status>success</Status><TicketID>$mrid</TicketID></Result>";
    }

    //update existing ticket
    function updateAction() {    
        $footprint = new Footprint($_GET["id"]);
        $footprint->addDescription($_POST["description"]);
        $footprint->submit();
        //TODO - need to deal with error condition
        echo "<Result><Status>success</Status></Result>";
    }
}
