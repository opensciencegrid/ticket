<?

class Schema
{
    private function cache($token) {
        //returns list of users and their current email addresses
        $c = new Cache("/tmp/goctiket.".$token);
        if($c->isFresh(600)) { //10 minutes..
            return $c->get();
        } else {
            $ret = $this->doget($token);
            $c->set($ret);
            return $ret;
        }
    }

    public function getemail()
    {
        return $this->cache("email");
    }

    public function getteams()
    {
        return $this->cache("teams");
    }

    public function getvos()
    {
        $ret = $this->doget("vos");
        return $ret;
    }
    public function getoriginatingvos()
    {
        $ret = $this->getvos();
        return split(",", $ret[0]->originating_vo);
    }
    public function getdestinationvos()
    {
        $ret = $this->getvos();
        return split(",", $ret[1]->destination_vo);
    }

    public function doget($what)
    {
        dlog("Schema::doget($what)");
        $client = new SoapClient(null, array('location' => config()->fp_soap_location, 'uri' => config()->fp_soap_uri));
        $ret = $client->__soapCall("MRWebServices__schema_goc_itb", array(config()->webapi_user, config()->webapi_password, "", $what));
        dlog("Schema::doget($what) -- done");
        return $ret;
    }
}

?>
