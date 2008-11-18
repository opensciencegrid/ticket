<?

class SearchController extends Zend_Controller_Action 
{ 
    public function indexAction() 
    { 
        if(!isset($_REQUEST["query"])) {
            $this->render("error/404.phtml", true);
            return;
        }

        $dirty_query = trim($_REQUEST["query"]);
        $query = addslashes($dirty_query);

        $model = new Tickets();
        $this->view->tickets = $model->search($query);

        $this->render();
    }
} 
