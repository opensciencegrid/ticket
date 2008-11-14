<?

class Tickets
{
    public function getopen()
    {
        $ret = $this->dosearch("where mrSTATUS in ('Engineering', 'Support__bAgency', 'Customer') order by mrDEST, mrID DESC");
        return $ret;
    }

    public function search($query)
    {
        $ret = $this->dosearch("where mrID = '$query' or mrALLDESCRIPTIONS like '%$query%' or mrTITLE like '%$query%' order by mrID");
        return $ret;
    }

    public function dosearch($query)
    {
        $client = new SoapClient(null, array('location' => config()->fp_soap_location, 'uri' => config()->fp_soap_uri));
        $ret = $client->__soapCall("MRWebServices__search_goc",
            array(config()->webapi_user, config()->webapi_password, "", "select mrID, mrSTATUS, mrTITLE, Destination__bVO__bSupport__bCenter as mrDEST from MASTER71 ".$query));
        return $ret;
    }
}

?>
