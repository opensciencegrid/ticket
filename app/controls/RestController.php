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
        echo "<NextAssignee>";
        echo "<FootprintsID>".htmlspecialchars($model->getNextAssignee())."</FootprintsID>";
        echo "<Reason>".htmlspecialchars($model->getReason())."</Reason>";
        echo "</NextAssignee>";

        $this->render("none", null, true);
    }
} 
