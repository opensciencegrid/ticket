<?

class Schema
{
    public function getemail()
    {
        //returns list of users and their current email addresses
        $ret = $this->doget("email");
        return $ret;
    }

    public function getteams()
    {
        $ret = $this->doget("teams");
        return $ret;
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
        $ret = $client->__soapCall("MRWebServices__schema_goc", array(config()->webapi_user, config()->webapi_password, "", $what));
        dlog("Schema::doget($what) -- done");
        return $ret;
    }
}

?>
