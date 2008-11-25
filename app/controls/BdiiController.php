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
        $form = $this->getForm();
        if($form->isValid($_POST)) {
            $footprint = $this->initSubmit($form);
            $footprint->addDescription($form->getValue('detail'));
            $footprint->setTitle("BDII Issue");

            if($form->getValue("down") == "true") {
                $footprint->addMeta("BDII is not responding!!");
                $footprint->setPriority(1); //set it to critical
                $footprint->setTicketType("Unscheduled__bOutage");
            }

            //bdii ticket is assigned to arvind
            $footprint->addAssignee("agopu", true); 
            $footprint->setDestinationVO("OSG-GOC");

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
            $this->view->errors = "Please correct following issues.";
            $this->view->form = $form;
            $this->render("index");
        }
    }

/*
    public function composeTicketTitle($form)
    {
        return "BDII Issue";
    }
*/
    private function getForm()
    {
        $form = $this->initForm("bdii");

        $elem = new Zend_Form_Element_Select('down');
        $elem->setLabel("Is the BDII responding?");
        $elem->setRequired(true);
        $elem->addMultiOption("false", "Yes");
        $elem->addMultiOption("true", "No");
        $elem->setDescription("* Selecting \"No\" will cause this ticket to be opened with CRITICAL priority.");
        $elem->addDecorator("description");
        $form->addElement($elem);

        $form->addElement($elem);

        $detail = new Zend_Form_Element_Textarea('detail');
        $detail->setLabel("Description");
        $detail->setRequired(true);
        $form->addElement($detail);

        $submit = new Zend_Form_Element_Submit('submit_button');
        $submit->setLabel("Submit");
        $form->addElement($submit);

        return $form;
    }
} 
