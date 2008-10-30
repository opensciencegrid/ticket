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
            //prepare footprint ticket
            $footprint = new Footprint;
            $footprint->setTitle($this->composeTicketTitle());
            $footprint->setFirstName($form->getValue('firstname'));
            $footprint->setLastName($form->getValue('lastname'));
            $footprint->setOfficePhone($form->getValue('phone'));
            $footprint->setEmail($form->getValue('email'));
            $footprint->addDescription($form->getValue('detail'));

            if($knowvo == "true") {
                $void = $form->getValue('vo_id_requested');
                $footprint->setDestinationVO($void);
                $voname = $footprint->lookupFootprintVOName($void);
                $footprint->addDescription("\n(META) User is requesting a membership at $voname");
                $footprint->addAssignee($voname);
            } else {
                $footprint->addDescription("\n(META) User does not know the VO to request membership to.");
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

    public function composeTicketTitle()
    {
        return "OSG Membership Request";
    }

    private function getForm()
    {
        $form = new Zend_Form;
        $form->setAction(base()."/membership/submit");
        $form->setMethod("post");
        $form->setDecorators(array(
            array('ViewScript', 
                array('viewScript' => 'membership/form.phtml'),
                array('class' => 'form element')
        )));


        $firstname = new Zend_Form_Element_Text('firstname');
        $firstname->setLabel("Your First Name");
        $firstname->addValidator(new Zend_Validate_Alpha(false)); //ture for allowWhiteSpace
        $firstname->setRequired(true);
        $firstname->setValue(user()->getPersonFirstName());
        $form->addElement($firstname);

        $lastname = new Zend_Form_Element_Text('lastname');
        $lastname->setLabel("Your Last Name");
        $lastname->addValidator(new Zend_Validate_Alpha(false)); //ture for allowWhiteSpace
        $lastname->setRequired(true);
        $lastname->setValue(user()->getPersonLastName());
        $form->addElement($lastname);

        $email = new Zend_Form_Element_Text('email');
        $email->setLabel("Your Email Address");
        $email->addValidator(new Zend_Validate_EmailAddress());
        $email->setRequired(true);
        $email->setValue(user()->getPersonEmail());
        $form->addElement($email);

        $phone = new Zend_Form_Element_Text('phone');
        $phone->setLabel("Your Phone Number");
        $phone->addValidator('regex', false, validator::$phone);
        $phone->setRequired(true);
        $phone->setDescription("(Format: 123-123-1234)");
        $phone->setValue(user()->getPersonPhone());
        $form->addElement($phone);

        $element = new Zend_Form_Element_Select('knowvo');
        $element->setLabel("Do you know which VO you are requesting your membership to?");
        $element->addMultiOption("false", "I don't know / not sure");
        $element->addMultiOption("true", "Yes");
        $form->addElement($element);

        $vo_model = new VO;
        $vos = $vo_model->fetchAll();
        $vo = new Zend_Form_Element_Select('vo_id_requested');
        $vo->setLabel("VO where you need an access");
        $vo->addMultiOption(null, "(Please Select)");
        foreach($vos as $v) {
            $vo->addMultiOption($v->sc_id, $v->short_name);
        }
        $form->addElement($vo);

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
