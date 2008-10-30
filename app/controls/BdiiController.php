<?

class BdiiController extends BaseController
{ 
    public function indexAction() 
    { 
        $this->view->form = $this->getForm();
        $this->render();
    }

    public function submitAction()
    {
        $form = $this->getForm();
        if($form->isValid($_POST)) {
            //prepare footprint ticket
            $footprint = new Footprint;
            $footprint->setTitle($this->composeTicketTitle());
            $footprint->setFirstName($form->getValue('firstname'));
            $footprint->setLastName($form->getValue('lastname'));
            $footprint->setOfficePhone($form->getValue('phone'));
            $footprint->setEmail($form->getValue('email'));
            $footprint->addDescription($form->getValue('detail'));

            $footprint->setOriginatingVO($form->getValue('vo_id'));

            if($form->getValue("down") == "true") {
                $footprint->addDescription("(BDII is not responding)");
                $footprint->setPriority(1); //set it to critical
            }

            //bdii ticket is assigned to arvind
            $footprint->addAssignee("agopu", true); 

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

    public function composeTicketTitle()
    {
        return "BDII Issue";
    }

    private function getForm()
    {
        $form = new Zend_Form;
        $form->setAction(base()."/bdii/submit");
        $form->setMethod("post");
        $form->setDecorators(array(
            array('ViewScript', 
                array('viewScript' => 'bdii/form.phtml'),
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
        //$phone->setDescription("(Format: 123-123-1234)");
        $phone->setValue(user()->getPersonPhone());
        $form->addElement($phone);

        $vo_model = new VO;
        $vos = $vo_model->fetchAll();
        $vo = new Zend_Form_Element_Select('vo_id');
        $vo->setLabel("Your Suppor Center");
        $vo->setRequired(true);
        $vo->addMultiOption(null, "(Please Select)");
        foreach($vos as $v) {
            $vo->addMultiOption($v->sc_id, $v->short_name);
        }
        $form->addElement($vo);

/*
        $elem = new Zend_Form_Element_Checkbox('down');
        $elem->setCheckedValue("true");
        $elem->setUncheckedValue("false");
        $elem->setLabel("BDII is not responding");
        $elem->setDescription("* Selecting this checkbox will cause this ticket to be opened with CRITICAL priority.");
*/
        $elem = new Zend_Form_Element_Select('down');
        $elem->setLabel("Is the BDII responding?");
        $elem->setRequired(true);
        $elem->addMultiOption("false", "Yes");
        $elem->addMultiOption("true", "No");
        $elem->setDescription("* Selecting \"No\" will cause this ticket to be opened with CRITICAL priority.");
        $elem->addDecorator("description");
        $form->addElement($elem);

/*
        $elem->setDescription("ahahaha");
        $elem->setDecorators(array(
            'ViewHelper',
            'Description',
            'Errors',
            array('HtmlTag', array('tag' => 'dd')),
            array('Label', array('tag' => 'dt')),
        ));
*/
        //$label = $elem->getDecorator('label');
        //$label->setOption('placement', 'append');
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
