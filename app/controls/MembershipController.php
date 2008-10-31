<?

class MembershipController extends BaseController
{ 
    public function indexAction() 
    { 
        $this->view->form = $this->getForm();
        $this->render();
    }

    public function submitAction()
    {
        $form = $this->getForm();

        //make vo_id_requested required if voknown is selected
        $knowvo = $_REQUEST["knowvo"];
        if($knowvo == "true") {
            $elem = $form->getelement("vo_id_requested");
            $elem->setRequired(true);
        }

        if($form->isValid($_POST)) {
            $footprint = $this->initSubmit($form);
            $footprint->addDescription($form->getValue('detail'));
            if($knowvo == "true") {
                $void = $form->getValue('vo_id_requested');

                //lookup sc_id from void
                $vo_model = new VO();
                $info = $vo_model->get($void);

                $footprint->setDestinationVO($info->sc_id);
                $voname = $footprint->lookupFootprintVOName($void);
                $footprint->addMeta("User is requesting a membership at $voname\n");
                $footprint->addAssignee($voname);

                $footprint->addMeta("VO Detail\n".print_r($info, true)."\n");
            
            } else {
                $footprint->addMeta("User does not know the VO to request membership to.\n");
            }

            //add DN as meta
            $dn = user()->getDN();
            if($dn == null) {
                $footprint->addMeta("User's DN is unknown.\n");
            } else {
                $footprint->addMeta("User's DN: $dn\n");
            }

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
            $this->view->errors = "Please correct following issues.";
            $this->view->form = $form;
            $this->render("index");
        }
    }

    public function composeTicketTitle($form)
    {
        return "OSG Membership Request";
    }

    private function getForm()
    {
        $form = $this->initForm("membership");

        $detail = new Zend_Form_Element_Textarea('detail');
        $detail->setLabel("Please describe why you are requesting this membership");
        $detail->setRequired(true);
        $form->addElement($detail);

        $submit = new Zend_Form_Element_Submit('submit_button');
        $submit->setLabel("Submit");
        $form->addElement($submit);

        return $form;
    }
} 
