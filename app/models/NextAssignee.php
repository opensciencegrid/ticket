<?php

class NextAssignee
{
    public $next_assignee = null;
    public $reason = "";

    public function getNextAssignee() { 
        $time = localtime(time()+(3600*config()->timezone_offset), true);
        $hour = $time["tm_hour"];
        $month = $time["tm_mon"];
        $day = $time["tm_mday"];
        $weekday = $time["tm_wday"];

        $members = $this->getConfig(true); //pull non-disabled

        //report the pool of possible staff
        $this->reason .= "Possible assignees:";
        foreach($members as $member) {
            $this->reason .= " ".$member->uid;
        }
        $this->reason .= ". ";

        //pick one person with least amount of tickets.
        if(count($members) > 0) {
            $model = new Tickets();
            $counts = $model->getopencounts();
            $id = null;
            $min = null;
            foreach($members as $member) {
                if(!isset($counts[$member->uid])) {
                    $counts[$member] = 0;
                }
                $count = $counts[$member->uid] * $member->weight;
                /*
                if($member == "kagross") {
                    $count = $count*2;
                    $this->reason .= "doubling the ticket count for kyle";
                }
                if($member == "vjneal") {
                    $count = $count*2;
                    $this->reason .= "doubling the ticket count for vjneal";
                }
                */

                if($id === null || $min > $count) {
                    $id = $member->uid;
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
            $this->reason .= $member->uid."(weight: ".$member->weight.") has ".$counts[$member->uid]." tickets. ";
        }

        //apply override
        $model = new Override();
        $over = $model->apply($this->next_assignee);
        if($over != $this->next_assignee) {
            $this->reason .= " The original assignee ".$this->next_assignee." was overriden by $over";
            $this->next_assignee = $over;
        }

        slog("choose ".$this->next_assignee. " due to - ". $this->reason);

        return $this->next_assignee; 
    }
    public function getReason() { 
        if(is_null($this->next_assignee)) {
            $this->getNextAssignee();
        }
        return $this->reason; 
    }

    public function getConfig($nondisabled) {
        $sql = "select * from gocticket.assignment";
        if($nondisabled) {
            $sql .= " WHERE disable = 0";
        }
        return db("data")->fetchAll($sql);
    }

    public function setConfig($members)
    {
        //clear all records
        $sql = "truncate table gocticket.assignment";
        db("data")->exec($sql);
        
        //insert all
        if(count($members) > 0) {
            $sql = "INSERT INTO `gocticket`.`assignment` (uid,weight,disable) VALUES ";
            $first = true;
            foreach($members as $member) {
                if(!$first) {
                    $sql .= ",";
                } else {
                    $first = false;
                }
                $uid = $member->uid;
                $weight = $member->weight;
                $disable = ($member->disable? 1 : 0);
                $sql .= " ('$uid',$weight,$disable)";
            }
            $sql .= ";";
            slog($sql);
            db("data")->exec($sql);
        }
    }
}

