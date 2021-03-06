<?


class SecurityAnnounceTicketController extends BaseController
{ 
    public function init()
    {
        user()->check("notify");

        $this->view->page_title = "Security Ticket Submitter";
        $this->view->menu_selected = "user";
        $this->view->submenu_selected = "securityannounceticket";
    }
    public function indexAction() 
    { 
        $this->view->form = $this->getForm();
        $this->render();
    }

    public function submitAction()
    {
        $form = $this->getForm();
        if($form->isValid($_POST)) {
            $footprint = $this->initSubmit($form);
            //$footprint->addDescription($form->getValue('detail'));//done by init
            $footprint->addMeta("Opening security announcement ticket with normal priority.");

            //security ticket is assigned to rob - and CC Kyle
            $footprint->addAssignee("rquick", true);
            $footprint->addAssignee("kagross");

            $footprint->setTicketType("Security_Notification");
            $footprint->setTitle($form->getValue('title'));

            try 
            {
                $mrid = $footprint->submit();
                $this->view->mrid = $mrid;
                $this->render("success", null, true);
            } catch(exception $e) {
                $this->sendErrorEmail($e);
                $this->render("failed", null, true);
            }
        } else {
            $this->view->errors = "Please correct the following issues.";
            $this->view->form = $form;
            $this->render("index");
        }
    }

    private function getForm()
    {
        $today = date("Y-m-d");
        $form = $this->initForm("securityannounceticket");

        $e = new Zend_Form_Element_Text('title');
        $e->setAttribs(array('size'=>50));
        $e->setLabel("Title");
        $e->setValue("OSG-SEC-".$today);
        $e->setRequired(true);
        $form->addElement($e);

        $detail = new Zend_Form_Element_Textarea('detail');
        $detail->setRequired(true);
        $detail->setLabel("Detail");
        $form->addElement($detail);

        $submit = new Zend_Form_Element_Submit('submit_button');
        $submit->setLabel("   Submit   ");
        $form->addElement($submit);

        return $form;
    }
} 
