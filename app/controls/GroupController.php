<?

function ticketcmp($a, $b) {
    return (round($a->score, 2) > round($b->score, 2)) ? -1 : 1;
}

class GroupController extends Zend_Controller_Action 
{
    public function init()
    {
        $this->view->submenu_selected = "admin";
    }

    public function indexAction()
    {
        if(!user()->allows("admin")) {
            $this->render("error/access", null, true);
            return;
        }

        $xml_file = config()->group_xml_path;
        try {
            $groups = new SimpleXmlElement(file_get_contents($xml_file), LIBXML_NOCDATA);
            $this->view->groups = array();
            foreach($groups as $group) {
                $tickets = array();
                foreach($group as $ticket) {
                    $tickets[] = $ticket;
                }
                uasort($tickets, "ticketcmp");
                $this->view->groups[] = $tickets;
            }
        } catch(exception $e) {
            ob_start();
            passthru("xsltproc $xml_file 2>&1");
            $xslt_out = ob_get_contents();
            ob_end_clean();
            throw new exception($e->getMessage()."\n\n".$xslt_out);
        }
    }
}
