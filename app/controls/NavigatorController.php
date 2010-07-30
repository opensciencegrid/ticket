<?

require_once("app/json.php");

class NavigatorController extends Zend_Controller_Action 
{ 
    public function init()
    {
        $this->view->submenu_selected = "view";
        $this->view->page_title = "Ticket List";
    }

    public function indexAction()
    {
        //TODO - this is a hack until DataTable provides a way to query column visibility information natively
        //find cookie for datatables
        $this->view->opened_table_cols = array();
        $this->view->closed_table_cols = array();
        $this->view->table_search = array("opened"=>array(), "closed"=>array());
        foreach($_COOKIE as $key=>$c) {
            if(strpos($key, "SpryMedia_DataTables_opened") === 0){
                $json = json_decode($c);
                $this->view->opened_table_cols = $json->abVisCols;
                $this->view->table_search["opened"] = $json->aaSearchCols;
            } else if(strpos($key, "SpryMedia_DataTables_closed") === 0){
                $json = json_decode($c);
                $this->view->closed_table_cols = $json->abVisCols;
                $this->view->table_search["closed"] = $json->aaSearchCols;
            }
        }

        try {
            $model = new Tickets();
            $this->view->page_title = "Open Tickets";

            //assigned tickets
            $closed_status = "('Closed', '_DELETED_', '_SOLVED_', 'Resolved')";
            $query = "WHERE mrstatus not in $closed_status";
            $this->view->assigned_tickets = $model->dosearch($query);

            //recently closed tickets
            $this->view->closed_days = config()->myticket_closed_days;
            $recent_date = date("Y-m-d G:i:s", time()-3600*24*$this->view->closed_days);

            $query = "WHERE mrstatus in $closed_status and mrupdatedate > '$recent_date'";
            $this->view->closed_tickets = $model->dosearch($query);

            $model = new Schema();
            $this->view->teams = $model->getteams();

        } catch (SoapFault $e) {
            elog("SoapFault detected while ViewController:loaddetail()");
            elog($e->getMessage());
            $this->view->content = $e->getMessage();
            $this->render("error/error", null, true);
        }
    }

} 
