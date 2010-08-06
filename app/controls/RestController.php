<?

class RestController extends Zend_Controller_Action
{
    //TODO - apply access restriction

    function indexAction()
    {
        echo "this controller provides various rest interface";
        $this->render("none", null, true);
    }

    function getopencountsAction()
    {
        header('content-type: text/xml'); 
        $model = new Tickets();
        $recs = $model->getopen();
        $counts = array();
        $recs_grouped = array();
        foreach($recs as $rec) {
            $assignees = $rec->mrassignees;
            foreach(split(" ", $assignees) as $assignee) {
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

        $this->render("none", null, true);
    }

    function getnextassigneeAction()
    {
        header('content-type: text/xml'); 
        $model = new NextAssignee();
        $assignee = $model->getNextAssignee();
        $reason = $model->getReason();
        
        //apply override
        $model = new Override();
        $over = $model->apply($assignee);
        if($over != $assignee) {
            $reason .= " The original assignee ".$assignee." was overriden by $over";
            $assignee = $over;
        }

        echo "<NextAssignee>";
        echo "<FootprintsID>".htmlspecialchars($assignee)."</FootprintsID>";
        echo "<Reason>".htmlspecialchars($reason)."</Reason>";
        echo "</NextAssignee>";

        $this->render("none", null, true);
    }

    function listopenAction()
    {
        header('content-type: text/xml'); 
        $model = new Tickets();
        $tickets = $model->getopen();
        echo "<Tickets>";
        foreach($tickets as $ticket) {
            echo "<Ticket>";
            echo "<ID>".$ticket->mrid."</ID>";
            echo "<Title>".htmlsafe($ticket->mrtitle)."</Title>";
            echo "<Priority>".Footprint::getPriority($ticket->mrpriority)."</Priority>";
            echo "<NextAction>".htmlsafe($ticket->nextaction)."</NextAction>";
            echo "<NAD>".$ticket->nad."</NAD>";
            echo "<URL>".fullbase()."/viewer?id=".$ticket->mrid."</URL>";
            echo "<Assignees>";
            foreach(explode(" ", $ticket->mrassignees) as $assignee) {
                if(substr($assignee, 0, 3) == "CC:") continue;
                if(trim($assignee) != "") {
                    echo "<Assignee>$assignee</Assignee>";
                }
            }
            echo "</Assignees>";
            echo "</Ticket>";
        }
        echo "</Tickets>";

        $this->render("none", null, true);
        
    }
} 
