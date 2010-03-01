<?

class NextAssignee
{
    public $next_assignee = null;
    public $reason = "";
    public function getNextAssignee() { return $this->next_assignee; }
    public function getReason() { return $this->reason; }

    public function __construct() 
    {
        $time = localtime(time(), true);
        $hour = $time["tm_hour"];
        $month = $time["tm_mon"];
        $day = $time["tm_mday"];
        $weekday = $time["tm_wday"];

        //pull everyone from support team
        $model = new Schema();
        $members = $model->getteammembers("OSG__bGOC__bSupport__bTeam");

        //TODO - pick the assignee based on following
        //6am - 8am - Alain only
        //8am - 1pm - Alain / Kyle / E
        //1pm - 3pm - everybody
        //3pm - 5pm - E / K / C
        //5pm - 6am - everybody (execept C)

        //don't assign to chris from 9 - 1pm 
        $chris = array_search("cpipes", $members);
        if($chris !== false) {
            if($hour > 9 and $hour < 17) {
                $this->reason .= "Can't assign to Chris.. it's 9am-5pm. ";
                unset($members[$chris]);
            }
        }

        //pick one person with least amount of tickets.
        if(count($members) > 0) {
            $model = new Tickets();
            $counts = $model->getopencounts();
            $id = null;
            $min = null;
            foreach($members as $member) {
                if($id === null || $min > $counts[$member]) {
                    $id = $member;
                    $min = $counts[$member];
                }
            }
            $this->next_assignee = $id;
            $this->reason .= "$id has the least amount of tickets.";
        } else {
            //nobody's available?
            $this->next_assignee = "rquick";
            $this->reason .= "Nobody is currently available. Defaulting to Rob.";
        }

        //add some current ticket stattistics..
        $this->reason .= "\n";
        foreach($members as $member) {
            $this->reason .= $member." has ".$counts[$member]." tickets. ";
        }
    }

}

