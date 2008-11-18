<?

class OpenticketsController extends Zend_Controller_Action 
{ 
    public function indexAction() 
    { 
        $model = new Tickets();
        $tickets = $model->getopen();

        //group tickets by destination VO
        $this->view->tickets = array();
        foreach($tickets as $ticket) {
            $destination_vo = Footprint::parse($ticket->mrdest);
            if(!isset($this->view->tickets[$destination_vo])) {
                $this->view->tickets[$destination_vo] = array();
            }
            $this->view->tickets[$destination_vo][] = $ticket;
        }

        $this->render();
    }

    public function xmlAction()
    {
        header("Content-type: text/xml");
        $model = new Tickets();
        $this->view->tickets = $model->getopen();
    }
} 
