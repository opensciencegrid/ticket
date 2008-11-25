<?

class ClosedticketsController extends Zend_Controller_Action 
{ 
    public function init()
    {
        $this->view->submenu_selected = "view";
    }    

    public function indexAction() 
    { 
        $model = new Tickets();
        $tickets = $model->getclosed();

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
} 
