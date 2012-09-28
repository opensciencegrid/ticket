<?

if(!function_exists("json_decode")) {
        require_once("app/json.php");
}

class NavigatorController extends Zend_Controller_Action 
{
    public function init()
    {
	header("Location: list/open", true, 301);
	setcookie('opened_opened_navigator', null, -1, "/goc/");
	setcookie('closed_closed_navigator', null, -1, "/goc/");
	setcookie('opened_156_opened_navigator', null, -1, "/goc/");
	setcookie('closed_156_closed_navigator', null, -1, "/goc/");
	exit;

/*
        //$this->view->submenu_selected = "view";
        $this->view->page_title = "Ticket List";
        $this->view->dt_cookie_openprefix="opened_156_";
        $this->view->dt_cookie_closeprefix="closed_156_";
*/
    }

    public function indexAction()
    {
        //TODO - this is a hack until DataTable provides a way to query column visibility information natively
        //find cookie for datatables
        $this->view->opened_table_cols = array();
        $this->view->closed_table_cols = array();
        $this->view->table_search = array("opened"=>array(), "closed"=>array());
        foreach($_COOKIE as $key=>$c) {
            $c = str_replace("'", "\"", $c); //php53's json_decode doesn't parse single quote..
            if(strpos($key, $this->view->dt_cookie_openprefix) === 0){
                $json = json_decode($c);
                $this->view->opened_table_cols = $json->abVisCols;
                $this->view->table_search["opened"] = $json->aoSearchCols;
            } else if(strpos($key, $this->view->dt_cookie_closeprefix) === 0){
                $json = json_decode($c);
                $this->view->closed_table_cols = $json->abVisCols;
                $this->view->table_search["closed"] = $json->aoSearchCols;
            }
        }

        try {
            $model = new Tickets();
            $this->view->page_title = "View Tickets";

            //assigned tickets
            $closed_status = "('Closed', '_DELETED_', '_SOLVED_', 'Resolved')";
            $query = "WHERE mrstatus not in $closed_status";
            $this->view->assigned_tickets = $model->dosearch($query);

            //recently closed tickets
            $this->view->closed_days = config()->myticket_closed_days;
            $recent_date = date("Y-m-d G:i:s", time()-3600*24*$this->view->closed_days);

            $query = "WHERE mrstatus in $closed_status and mrupdatedate > '$recent_date'";
            $this->view->closed_tickets = $model->dosearch($query);

            //load metadata
            $this->view->metadata = $this->loadMetadata(array_merge($this->view->assigned_tickets, $this->view->closed_tickets));

            $model = new Schema();
            $this->view->teams = $model->getteams();

        } catch (SoapFault $e) {
            elog("SoapFault detected while ViewController:loaddetail()");
            elog($e->getMessage());
            $this->view->content = $e->getMessage();
            $this->render("error/error", null, true);
        }
    }

    function loadMetadata($tickets) {
        if(count($tickets) == 0) return;

        foreach($tickets as $ticket) {
            $ids[] = $ticket->mrid;
        }
        $model = new Data();
        $recs = $model->getAllMetadataForTickets($ids);
        $metadata = array();
        foreach($recs as $rec) {
            $key = $rec->key;
            if(!isset($metadata[$rec->ticket_id])) {
                $metadata[$rec->ticket_id] = array();
            }
            $metadata[$rec->ticket_id][$key] = $rec->value;
        }
        return $metadata;
    }
} 
