<?

class SitemapController extends Zend_Controller_Action 
{ 
    public function xmlAction()
    {
        header("Content-type: text/xml");
        $model = new Tickets();
        $this->view->tickets = $model->getall();
    }
} 
