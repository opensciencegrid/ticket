<?

class OpenticketsController extends Zend_Controller_Action 
{ 
    public function indexAction() 
    { 
        $model = new Tickets();
        $this->view->tickets = $model->getopen();

        $this->render();
    }
} 
