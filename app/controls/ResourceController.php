<?

class ResourceController extends BaseController
{ 
    public function indexAction() 
    { 
        $this->view->form = $this->getForm();
        $this->render();
    }

    public function submitAction()
    {
        $form = $this->getForm();
    
        //make one of resource_issue field required based on resource_type selection
        $resource_type = $_REQUEST["resource_type"];
        $issue_element_name = "resource_id_with_issue_$resource_type";
        $issue_element = $form->getelement($issue_element_name);
        $issue_element->setRequired(true);

        if($form->isValid($_POST)) {
            //prepare footprint ticket
            $footprint = new Footprint;
            $footprint->setTitle($this->composeTicketTitle($form->getValue($issue_element_name)));
            $footprint->setFirstName($form->getValue('firstname'));
            $footprint->setLastName($form->getValue('lastname'));
            $footprint->setOfficePhone($form->getValue('phone'));
            $footprint->setEmail($form->getValue('email'));
            $footprint->addDescription($form->getValue('detail'));
            $footprint->setOriginatingVO($form->getValue('vo_id'));

            $admin = $_REQUEST["admin"];

            //lookup service center
            $resource_id = $form->getValue($issue_element_name);
            $rs_model = new ResourceSite();
            $resource = $rs_model->fetch($resource_id);

            //set description destination vo, assignee
            $footprint->addDescription("\n(META) Resource where user is having this issue: ".$resource->resource_name."($resource_id)\n");

            if($admin) {
                //this is their own resource - maybe installation issue..
                $footprint->addDescription("(META) User is the admin for this resource, and this is an installation issue.");

            } else {
                //someone else's resource..
                $footprint->setDestinationVO($resource->sc_id);
                $voname = $footprint->lookupFootprintVOName($resource->sc_id);
                $footprint->addAssignee($voname);

                //find primary resource admin email
                $prac_model = new PrimaryResourceAdminContact();
                $prac = $prac_model->fetch($resource_id);
                $footprint->addCC($prac->primary_email);
                $footprint->addDescription("(META) Primary Admin for ".$resource->resource_name." is ".$prac->first_name." ".$prac->last_name." and has been CC'd regarding this ticket.");
            }

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

    public function composeTicketTitle($resource_id)
    {
        $resource_model = new Resource();
        $resource_name = $resource_model->fetchName($resource_id);
        return "Resource Specific issue on $resource_name";
    }

    private function getForm()
    {
        $form = new Zend_Form;
        $form->setAction(base()."/resource/submit");
        $form->setAttrib("id", "resource_form");
        $form->setMethod("post");
        $form->setDecorators(array(
            array('ViewScript', 
                array('viewScript' => 'resource/form.phtml'),
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

        $element = new Zend_Form_Element_Select('resource_type');
        $element->setLabel("I am having this issue in following resource");
        $element->setRequired(true);
        $gridtype_model = new GridType;
        $gridtypes = $gridtype_model->fetchAll();
        foreach($gridtypes as $gridtype) {
            $element->addMultiOption($gridtype->grid_type_id, $gridtype->description);

            //add element for this grid type
        }
        $form->addElement($element);

        $element = new Zend_Form_Element_Select('resource_id_with_issue_1');
        $element->addMultiOption(null, "(Please Select)");
        $resource_model = new Resource;
        $resources = $resource_model->fetchAll(1);
        foreach($resources as $resource) {
            $element->addMultiOption($resource->resource_id, $resource->name);
        }
        $form->addElement($element);

        $element = new Zend_Form_Element_Select('resource_id_with_issue_2');
        $element->addMultiOption(null, "(Please Select)");
        $resource_model = new Resource;
        $resources = $resource_model->fetchAll(2);
        foreach($resources as $resource) {
            $element->addMultiOption($resource->resource_id, $resource->name);
        }
        $form->addElement($element);

        $element = new Zend_Form_Element_Checkbox('admin');
        $element->setLabel("Check here if you are the admin for this resource, and this is an installation issue.");
        $form->addElement($element);

        $detail = new Zend_Form_Element_Textarea('detail');
        $detail->setLabel("Description");
        $detail->setRequired(true);
        $form->addElement($detail);

        $element = new Zend_Form_Element_Submit('submit_button');
        $element->setLabel("Submit");
        $form->addElement($element);

        return $form;
    }
} 
