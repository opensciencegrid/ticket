<?

class LogController extends Zend_Controller_Action 
{ 
    public function xmlAction()
    {
        header("Content-type: text/xml");
        echo file_get_contents("/tmp/gocticket.log.xml");
        $this->render("none", null, true);
    }
} 
