<?

class Tickets
{
    public function getopen()
    {
        $ret = $this->dosearch("where mrSTATUS not in ('Closed', '_DELETED_', '_SOLVED_', 'Resolved') order by mrDEST, mrID DESC");
        return $ret;
    }
    public function getclosed($start_time)
    {
        $start = date("Y-m-d", $start_time);
        $ret = $this->dosearch("where mrSTATUS in ('Closed', '_SOLVED_', 'Resolved') and mrUPDATEDATE > '$start' order by mrDEST, mrID DESC");
        return $ret;
    }
    public function getall()
    {
        $ret = $this->dosearch("where mrSTATUS <> '_DELETED_' order by mrID DESC");
        return $ret;
    }

    public function getoriginating($id)
    {
        $ret = $this->dosearch("where Originating__bTicket__bNumber = $id");
        return $ret;
    }


    public function getrecent()
    {
        $start_time = time() - 3600*24*30; //30 days
        $start = date("Y-m-d", $start_time);
        $ret = $this->dosearch("where mrUPDATEDATE > '$start'", true);
        return $ret;
    }

    public function search($query)
    {
        $ret = $this->dosearch("where mrID = '$query' or mrALLDESCRIPTIONS like '%$query%' or mrTITLE like '%$query%' order by mrID");
        return $ret;
    }

    public function dosearch($query, $bIncludeDesc = false)
    {
        $client = new SoapClient(null, array('location' => config()->fp_soap_location, 'uri' => config()->fp_soap_uri));
        $column = "mrID, mrSTATUS, mrTITLE, mrASSIGNEES, mrUPDATEDATE, Destination__bVO__bSupport__bCenter as mrDEST";
        if($bIncludeDesc) {
            $column .= ", mrALLDESCRIPTIONS";
        }
        $projectid = config()->project_id;
        $ret = $client->__soapCall("MRWebServices__search_goc",
				   //            array(config()->webapi_user, config()->webapi_password, "", "select $column from MASTER71 ".$query)); // agopu testing ITB project
            array(config()->webapi_user, config()->webapi_password, "", "select $column from MASTER$projectid ".$query));
        dlog("Ticket::dosearch($query)");
        return $ret;
    }
    public function getDetail($id)
    {
        $client = new SoapClient(null, 
            array(      'location' => config()->fp_soap_location,
                        'uri'      => config()->fp_soap_uri));
        $ret = $client->__soapCall("MRWebServices__getIssueDetails_goc", 
            array(config()->webapi_user, config()->webapi_password, "", config()->project_id, $id));
        return $ret;
    }
    public function getAttachments($id)
    {
        $client = new SoapClient(null, 
            array(      'location' => config()->fp_soap_location,
                        'uri'      => config()->fp_soap_uri));
        $ret = $client->__soapCall("MRWebServices__getAttachments_goc", 
            array(config()->webapi_user, config()->webapi_password, "", config()->project_id, $id));
        return $ret;
    }
}

?>
