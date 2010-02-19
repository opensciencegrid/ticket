<?

class BdiiController extends BaseController
{ 
    public function init()
    {
        $this->view->submenu_selected = "open";
    }

    public function indexAction() 
    { 
        $this->view->form = $this->getForm();
        $this->render();
    }

    public function submitAction()
    {
        slog("BDII form submitted with following requests");
        slog(print_r($_REQUEST, true));

        $form = $this->getForm();
        if($form->isValid($_POST)) {
            $footprint = $this->initSubmit($form);
            $footprint->addDescription($form->getValue('detail'));
            $footprint->setTitle($form->getValue('title'));

            $bdiiserver = $form->getValue("bdiiserver");
            $footprint->addMeta("Select BDII Server Instance: ".$bdiiserver."\n");

            $down = $form->getValue("down");
            $footprint->addMeta("Is the BDII completely down?: ".$down."\n");

            if($down == "true" && $bdiiserver == "is") {
                $footprint->addMeta("Opening ticket with CRITICAL priority\n");
                $footprint->setPriority(1); //set it to critical;
                $footprint->setTicketType("Unscheduled__bOutage");
            }

            //bdii ticket is assigned to arvind & soichi
            $footprint->addAssignee("agopu", true); 
            $footprint->addAssignee("hayashis");
            $footprint->setDestinationVO("MIS");

            try 
            {
                $mrid = $footprint->submit();
                //var_dump($footprint);
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
        $form = $this->initForm("bdii");

        $e = new Zend_Form_Element_Text('title');
        $e->setAttribs(array('size'=>50));
        $e->setLabel("Title");
        $e->setValue("BDII Issue");
        $e->setRequired(true);
        $form->addElement($e);

        $elem = new Zend_Form_Element_Select('down');
        $elem->setLabel("Is the BDII responding?");
        $elem->setRequired(true);
        $elem->addMultiOption("false", "Yes");
        $elem->addMultiOption("true", "No");
        //$elem->setDescription("* Selecting \"No\" will cause this ticket to be opened with CRITICAL priority.");
        //$elem->addDecorator("description");
        $form->addElement($elem);

        $elem = new Zend_Form_Element_Select('bdiiserver');
        $elem->setLabel("Which BDII instance are you having this issue?");
        $elem->setRequired(true);
        $elem->addMultiOption("is", "*.grid.iu.edu");
        $elem->addMultiOption("other", "(Other)");
        $form->addElement($elem);

        $detail = new Zend_Form_Element_Textarea('detail');
        $detail->setLabel("Description");
        $detail->setRequired(true);
        $form->addElement($detail);

        $submit = new Zend_Form_Element_Submit('submit_button');
        $submit->setLabel("   Submit   ");
        $form->addElement($submit);

        return $form;
    }
} 
