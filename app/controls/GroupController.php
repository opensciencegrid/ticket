<?

class GroupController extends Zend_Controller_Action 
{ 
    public function init()
    {
        $this->view->submenu_selected = "admin";
    }    

    public function indexAction() 
    { 
        if(!in_array(role::$goc_admin, user()->roles)) {
            $this->render("error/access", null, true);
            return;
        }

        $xml_file = "/tmp/gocticket.groupticket.xml";
        try {
            $this->view->groups = new SimpleXmlElement(file_get_contents($xml_file), LIBXML_NOCDATA);
        } catch(exception $e) {
            ob_start();
            passthru("xsltproc $xml_file 2>&1");
            $xslt_out = ob_get_contents();
            ob_end_clean();
            throw new exception($e->getMessage()."\n\n".$xslt_out);
        }
    }
} 
