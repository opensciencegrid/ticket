<?

class NextAssignee
{
    public $next_assignee = null;
    public $reason = "";
    public function getNextAssignee() { return $this->next_assignee; }
    public function getReason() { return $this->reason; }

    public function __construct() 
    {
        $time = localtime(time()+(3600*config()->timezone_offset), true);
        $hour = $time["tm_hour"];
        $month = $time["tm_mon"];
        $day = $time["tm_mday"];
        $weekday = $time["tm_wday"];

        //construct list of possible assignee based on each hours
/*
        if ($hour >= 8 and $hour < 13) {
            $members = array("echism", "kagross");
        } else if ($hour >= 13 and $hour < 17) {
            $members = array("echism", "kagross", "cpipes");
        } else if ($hour >= 17 and $hour < 21) {
            $members = array("cpipes");
        } else {
            $members = array("echism", "kagross");
        }
*/

        $members = array("echism", "kagross", "cpipes");

        //report the pool of possible staff
        $this->reason .= "possible assignees at this hour ($hour):";
        foreach($members as $member) {
            $this->reason .= " ".$member;
        }
        $this->reason .= ". ";

        //pick one person with least amount of tickets.
        if(count($members) > 0) {
            $model = new Tickets();
            $counts = $model->getopencounts();
            $id = null;
            $min = null;
            foreach($members as $member) {
                if(!isset($counts[$member])) {
                    $counts[$member] = 0;
                }
                $count = $counts[$member];

                if($member == "kagross") {
                    $count = $count*2;
                    $this->reason .= "doubling the ticket count for kyle";
                }

                if($id === null || $min > $count) {
                    $id = $member;
                    $min = $count;
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

        //apply override
        $model = new Override();
        $over = $model->apply($this->next_assignee);
        if($over != $this->next_assignee) {
            $this->reason .= " The original assignee ".$this->next_assignee." was overriden by $over";
            $this->next_assignee = $over;
        }

        slog("choose ".$this->next_assignee. " due to - ". $this->reason);
    }

}

