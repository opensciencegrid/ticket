<?

class ResourceController extends Zend_Controller_Action 
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
            $footprint->setTitle($this->composeTicketTitle($form->getValue('resource_id_with_issue')));
            $footprint->setFirstName($form->getValue('firstname'));
            $footprint->setLastName($form->getValue('lastname'));
            $footprint->setOfficePhone($form->getValue('phone'));
            $footprint->setEmail($form->getValue('email'));
            $footprint->setDescription($form->getValue('detail'));

            $footprint->setVO($form->getValue('vo_id'));
            $footprint->setResourceWithIssue($form->getValue('resource_id_with_issue'));

            if($footprint->submit()) {
                $this->render("success", null, true);
            } else {
                $this->render("failed", null, true);
            }
        } else {
            $this->view->errors = "Please correct following issues.";
            $this->view->form = $form;
            $this->render("index");
        }
    }

    public function composeTicketTitle($resource_id)
    {
        $resource_model = new Resource();
        $resource_name = $resource_model->fetchName($resource_id);
        return "(Resource Specific issue on $resource_name)";
    }

    private function getForm()
    {
        $form = new Zend_Form;
        $form->setAction(base()."/resource/submit");
        $form->setAttrib("id", "resource_form");
        $form->setMethod("post");

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

        $vo_model = new VO;
        $vos = $vo_model->fetchAll();
        $vo = new Zend_Form_Element_Select('vo_id');
        $vo->setLabel("Your Suppor Center");
        $vo->setRequired(true);
        $vo->addMultiOption(null, "(Please Select)");
        foreach($vos as $v) {
            $vo->addMultiOption($v->vo_id, $v->short_name);
        }
        $form->addElement($vo);

        $resource_model = new Resource;
        $resources = $resource_model->fetchAll();
        $element = new Zend_Form_Element_Select('resource_id_with_issue');
        $element->setLabel("Resource where you are having this issue");
        $element->setRequired(true);
        $element->addMultiOption(null, "(Please Select)");
        foreach($resources as $resource) {
            $element->addMultiOption($resource->resource_id, $resource->name);
        }
        $form->addElement($element);

        $detail = new Zend_Form_Element_Textarea('detail');
        $detail->setLabel("Description");
        $detail->setRequired(true);
        $form->addElement($detail);

        /*
        $form->addDisplayGroup(array('firstname', 'lastname', 'email', 'phone'), "your_info", 
            array(      "label"=>"Your Information",
                        "disableLoadDefaultDecorators"=>true));
        $form->addDisplayGroup(array('detail'), "issue_details", 
            array("label"=>"Issue Details"));
        */

        $submit = new Zend_Form_Element_Submit('submit_button');
        $submit->setLabel("Submit");
        $form->addElement($submit);

        return $form;
    }
} 
