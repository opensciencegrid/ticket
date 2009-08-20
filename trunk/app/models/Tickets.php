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
        $column = "mrID, mrSTATUS, mrTITLE, mrASSIGNEES, mrUPDATEDATE, Destination__bVO__bSupport__bCenter as mrDEST, Originating__bVO__bSupport__bCenter mrORIGIN, ENG__bNext__bAction__bItem as nextaction, ENG__bNext__bAction__bDate__fTime__b__PUTC__p as nad";
        if($bIncludeDesc) {
            $column .= ", mrALLDESCRIPTIONS";
        }
        $projectid = config()->project_id;

        $ret = fpcall("MRWebServices__search_goc", 
            array(config()->webapi_user, config()->webapi_password, "", "select $column from MASTER$projectid ".$query));
        return $ret;
    }
    public function getDetail($id)
    {
        $ret = fpcall("MRWebServices__getIssueDetails_goc", 
            array(config()->webapi_user, config()->webapi_password, "", config()->project_id, $id));
        return $ret;
    }
    public function getAttachments($id)
    {
        $ret = fpcall("MRWebServices__getAttachments_goc", 
            array(config()->webapi_user, config()->webapi_password, "", config()->project_id, $id));
        return $ret;
    }
}

