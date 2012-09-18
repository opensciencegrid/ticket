<?

class ListController extends Zend_Controller_Action 
{
    public function init()
    {
        $this->view->menu_selected = "view";
        $this->closed_status = "('Closed', '_DELETED_', '_SOLVED_', 'Resolved')";

        //remove cookie from old navigator - we are exceeding max cookie size of 8k
        setcookie('opened_opened_navigator', null, -1, "/goc/");
        setcookie('closed_closed_navigator', null, -1, "/goc/");
    }

    public function openAction()
    {
        $this->view->submenu_selected = "listopen";
        $this->view->page_title = "Open Tickets";
        $this->loadcookie("open");

        try {
            $model = new Tickets();

            //assigned tickets
            $query = "WHERE mrstatus not in $this->closed_status";
            $this->view->tickets = $model->dosearch($query);
            $this->view->metadata = $this->loadMetadata($this->view->tickets);

            $model = new Schema();
            $this->view->teams = $model->getteams();

        } catch (SoapFault $e) {
            elog("SoapFault detected while ViewController:loaddetail()");
            elog($e->getMessage());
            $this->view->content = $e->getMessage();
            $this->render("error/error", null, true);
        }

        $this->render("list");
    }

    public function recentcloseAction()
    {
        $this->view->submenu_selected = "listrecentclose";
        $this->view->page_title = "Recently Closed Tickets";
        $this->loadcookie("recentclose");

        try {
            $model = new Tickets();

            //recently closed tickets
            $this->view->closed_days = config()->myticket_closed_days;
            $recent_date = date("Y-m-d G:i:s", time()-3600*24*$this->view->closed_days);

            $query = "WHERE mrstatus in $this->closed_status and mrupdatedate > '$recent_date'";
            $this->view->tickets = $model->dosearch($query);
            $this->view->metadata = $this->loadMetadata($this->view->tickets);

            $model = new Schema();
            $this->view->teams = $model->getteams();

        } catch (SoapFault $e) {
            elog("SoapFault detected while ViewController:loaddetail()");
            elog($e->getMessage());
            $this->view->content = $e->getMessage();
            $this->render("error/error", null, true);
        }
        $this->render("list");
    }

    function loadcookie($prefix) {

        //TODO - this is a hack until DataTable provides a way to query column visibility information natively
        //find cookie for datatables
        $this->view->dt_cookie_prefix="gocticket_156_$prefix";
        $this->view->table_cols = array();
        foreach($_COOKIE as $key=>$c) {
            $c = str_replace("'", "\"", $c); //php53's json_decode doesn't parse single quote..
            if(strpos($key, $this->view->dt_cookie_prefix) === 0){
                $json = json_decode($c);
                $this->view->table_cols = $json->abVisCols;
                $this->view->table = $json->aoSearchCols;
            }
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
