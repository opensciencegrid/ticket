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
        $counts = $model->getopencounts();
        echo "<OpenCounts>";
        foreach($counts as $id=>$count) {
            echo "<Count><FootprintsID>".htmlspecialchars($id)."</FootprintsID><Count>$count</Count></Count>";
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
