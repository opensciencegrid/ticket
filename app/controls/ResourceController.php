<?

class ResourceController extends BaseController
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
        $issue_element = $this->getIssueElement($form);
        $issue_element->setRequired(true);

        if($form->isValid($_POST)) {
            $footprint = $this->initSubmit($form);
            $footprint->addDescription($form->getValue('detail'));

            //lookup service center
            //$resource_id = $form->getValue($issue_element_name);
            $resource_id = $issue_element->getValue();
            $rs_model = new ResourceSite();
            $resource = $rs_model->fetch($resource_id);

            //set description destination vo, assignee
            $footprint->addMeta("Resource where user is having this issue: ".$resource->resource_name."($resource_id)\n");
            $footprint->setTitle($form->getValue('title'));

            $admin = $_REQUEST["admin"];
            if($admin) {
                //this is their own resource - maybe installation issue..
                $footprint->addMeta("User is the admin for this resource, and this is an installation issue.\n");
                $footprint->setDestinationVO("OSG-GOC");
            } else {
                //someone else's resource..

                //lookup SC name
                if($resource === false) {
                    $scname = "OSG-GOC";
                    $footprint->addMeta("Couldn't find the SC associated with this resource. Please see finderror page for more detail.");
                } else {
                    $scname = $footprint->setDestinationVOFromSC($resource->sc_id);
                }

                if($footprint->isValidFPSC($scname)) {
                    $footprint->addAssignee($scname);
                } else {
                    $footprint->addMeta("Couldn't add assignee $scname since it doesn't exist on FP yet.. (Please sync!)\n");
                }
                $footprint->addPrimaryAdminContact($resource_id);
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

    public function getIssueElement($form)
    {
        //make one of resource_issue field required based on resource_type selection
        $resource_type = $_REQUEST["resource_type"];
        $issue_element_name = "resource_id_with_issue_$resource_type";
        $issue_element = $form->getElement($issue_element_name);
        return $issue_element;

    }

    private function getForm()
    {
        $form = $this->initForm("resource");

        $element = new Zend_Form_Element_Select('resource_type');
        $element->setLabel("I am having this issue in following resource");
        $element->setRequired(true);
        $gridtype_model = new GridType;
        $gridtypes = $gridtype_model->fetchAll();
        foreach($gridtypes as $gridtype) {
            $element->addMultiOption($gridtype->grid_type_id, $gridtype->description);
        }
        $form->addElement($element);

        $element = new Zend_Form_Element_Select('resource_id_with_issue_1');
        $element->setLabel("Resource Name");
        $element->addMultiOption(null, "(Please Select)");
        $resource_model = new Resource;
        $resources = $resource_model->fetchAll(1);
        foreach($resources as $resource) {
            $element->addMultiOption($resource->resource_id, $resource->name);
        }
        $form->addElement($element);

        $element = new Zend_Form_Element_Select('resource_id_with_issue_2');
        $element->setLabel("Resource Name");
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

        $e = new Zend_Form_Element_Text('title');
        $e->setAttribs(array('size'=>50));
        $e->setLabel("Title");
        $e->setValue("(TBD)");
        $e->setRequired(true);
        $form->addElement($e);

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
