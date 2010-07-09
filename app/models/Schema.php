<?

class Schema
{
    private function cache($token) {
        //returns list of users and their current email addresses
        $c = new Cache("/tmp/goctiket.".$token."_".config()->project_id);
        if($c->isFresh(600)) { //10 minute
            return $c->get();
        } else {
            $ret = $this->doget($token);
            $c->set($ret);
            return $ret;
        }
    }

    public function getusers()
    {
        $ret = $this->cache("users");
        return $ret[0]; //ret is somewhat wrapped with array..
    }

    public function getemail()
    {
        $ret = $this->cache("email_fp9");
        return $ret[0];
    }

    public function getquickdesc()
    {
        $descs = array();

        $rows = $this->cache("quickdesc");
        $count = 0;
        $name = "";
        foreach($rows as $row) {
            if($count % 2 == 0) {
                $name = $row;
            } else {
                $descs[$name] = html_entity_decode($row->DESCRIPTION);
            }
            $count++;
        }
        return $descs;
    }

    public function getquickticket()
    {
        return $this->cache("quickticket");
    }

    public function getteams()
    {
        return $this->cache("teams");
    }
    public function getteammembers($team_name)
    {
        $teams = $this->getteams();
        foreach($teams as $team) {
            if($team->team == $team_name) {
                return split(",", $team->members);
            }
        }
        return null;
    }

    public function getvos()
    {
        $ret = $this->cache("vos");
        return $ret;
    }
    public function getoriginatingvos()
    {
        $ret = $this->getvos();
        $list = split(",", $ret[0]->originating_vo);
        asort($list);
        return $list;
    }
    public function getdestinationvos()
    {
        $ret = $this->getvos();
        $list = split(",", $ret[1]->destination_vo);
        asort($list);
        return $list;
    }

    public function doget($what)
    {
        slog("Making fpcall to get schema:$what");
        return fpcall("MRWebServices__schema_goc", array(config()->webapi_user, config()->webapi_password, "", $what, config()->project_id));
    }
}

