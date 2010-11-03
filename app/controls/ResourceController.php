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

    public function loadtypeAction() {
        $rid = (int)$_REQUEST["rid"];
        $model = new ResourceServices();
        $services = $model->fetchByID($rid);
        echo "<select name=\"service_id\">";
        foreach($services as $service) {
            $sid = $service->service_id;
            $desc = $service->description;
            echo "<option value=\"$sid\">$desc</option>";
        }
        echo "<option id=\"-1\">(N/A)</option>";
        echo "</select>";
        $this->render("none", null, true);
    }

    public function submitAction()
    {
        $form = $this->getForm();
        $issue_element = $this->getIssueElement($form);
        if($issue_element === null) {
            elog("didn't receive any form data - maybe user's browser has issue"); 
            $this->view->content = "<p>We did not receive necessary form data.</p>";
            $this->view->content .= "<p>Following is everything I got from your browser..</p>";
            $this->view->content .= "<pre>".print_r($_REQUEST, true)."</pre>";
            $this->render("error/error", null, true);
            return;
        }
        $issue_element->setRequired(true);

        if($form->isValid($_POST)) {
            $footprint = $this->initSubmit($form);
            $footprint->addDescription($form->getValue('detail'));

            //lookup service center
            $resource_id = $issue_element->getValue();
            $rs_model = new ResourceSite();
            $resource_model = new Resource();
            $resource = $resource_model->fetchByID($resource_id);
            $resource_name = $resource->name;
            $resource_group_id = $resource->resource_group_id;
            $resource_group_model = new ResourceGroup();
            $resource_group = $resource_group_model->fetchByID($resource_group_id);

            //set description destination vo, assignee
            $footprint->addMeta("Resource on which user is having this issue: ".$resource_name."($resource_id)\n");
            $footprint->setMetadata("ASSOCIATED_RG_ID", $resource_group_id);
            $footprint->setMetadata("ASSOCIATED_RG_NAME", $resource_group->name);
            $footprint->setMetadata("ASSOCIATED_R_ID", $resource_id);
            $footprint->setMetadata("ASSOCIATED_R_NAME", $resource_name);
            $footprint->setTitle($form->getValue('title'));

            //set destination VO, primary admin, SC, etc..
            $admin = $_REQUEST["admin"];
            if($admin) {
                //this is their own resource - maybe installation issue..
                $footprint->addMeta("User is the administator for this resource.\n");
                $footprint->setDestinationVO("MIS");
            } else {
                $void = $footprint->setDestinationVOFromResourceID($resource_id);
                if($void) {
                    $footprint->setMetadata("ASSOCIATED_VO_ID", $void);
                }

                $sc_id = $rs_model->fetchSCID($resource_id);
                if(!$sc_id) {
                    $scname = "OSG-GOC";
                    $footprint->addMeta("Couldn't find the support center that supports this resource. Please see finderror page for more detail.\n");
                } else {
                    //lookup SC name form sc_id
                    $sc_model = new SC;
                    $sc = $sc_model->get($sc_id);
                    $scname = $sc->footprints_id;
                    $footprint->setMetadata("SUPPORTING_SC_ID", $sc_id);
                }

                if($footprint->isValidFPSC($scname)) {
                    $footprint->addAssignee($scname);
                    $footprint->addMeta("Assigned support center: $scname which supports this resource\n");
                } else {
                    $footprint->addMeta("Couldn't add assignee $scname since it doesn't exist on FP yet.. (Please sync!)\n");
                    elog("Couldn't add assignee $scname since it doesn't exist on FP yet.. (Please sync!)\n");
                }
                $footprint->addPrimaryAdminContact($resource_id);
            }

            //add few other assignee based on the service type
            if(isset($_REQUEST["service_id"])) {
                $service_id = $_REQUEST["service_id"];
                switch($service_id){
                case -1: //NA
                    break;
                //case 5: //GridFTP
                case 3: //SRMv2
                    $footprint->addAssignee("tlevshin"); //Tanya Levshina
                    $footprint->addAssignee("neha"); //Neha Sharma
                default:
                    $footprint->setMetadata("SERVICE_ID", $service_id);
                }
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
            $this->view->errors = "Please correct the following issues.";
            $this->view->form = $form;
            $this->render("index");
        }
    }

    public function getIssueElement($form)
    {
        //make one of resource_issue field required based on resource_type selection
        if(isset($_REQUEST["resource_type"])) {
            $resource_type = $_REQUEST["resource_type"];
            $issue_element_name = "resource_id_with_issue_$resource_type";
            $issue_element = $form->getElement($issue_element_name);
            return $issue_element;
        }
        return null;
    }

    private function getForm()
    {
        $form = $this->initForm("resource");

        $element = new Zend_Form_Element_Select('resource_type');
        $element->setLabel("I am having this issue on the following resource");
        $element->setRequired(true);
        $gridtype_model = new GridType;
        $gridtypes = $gridtype_model->fetchAll();
        foreach($gridtypes as $gridtype) {
            $element->addMultiOption($gridtype->id, $gridtype->description);
        }
        if(config()->role_prefix == "itbticket_") {
            //set it to ITB list
            $element->setValue(2);
        }
        //override with requested param
        if(isset($_REQUEST["resource_type"])) {
            $element->setValue((int)$_REQUEST["resource_type"]);
        }
        $form->addElement($element);

        $element = new Zend_Form_Element_Select('resource_id_with_issue_1');
        $element->setLabel("Resource Name");
        $element->addMultiOption(null, "(Please Select)");
        $resource_model = new Resource;
        $resources = $resource_model->fetchAll(1);
        foreach($resources as $resource) {
            $element->addMultiOption($resource->id, $resource->name);
        }
        //override with requested param
        if(isset($_REQUEST["resource_id"])) {
            $element->setValue((int)$_REQUEST["resource_id"]);
        }
        $form->addElement($element);

        $element = new Zend_Form_Element_Select('resource_id_with_issue_2');
        $element->setLabel("Resource Name");
        $element->addMultiOption(null, "(Please Select)");
        $resources = $resource_model->fetchAll(2);
        foreach($resources as $resource) {
            $element->addMultiOption($resource->id, $resource->name);
        }
        //override with requested param
        if(isset($_REQUEST["resource_id"])) {
            $element->setValue((int)$_REQUEST["resource_id"]);
        }
        $form->addElement($element);

        $element = new Zend_Form_Element_Checkbox('admin');
        $element->setLabel("Check here if you are the admin for this resource (this will prevent the ticket from getting routed back to you!)");
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
        $element->setLabel("   Submit   ");
        $form->addElement($element);

        return $form;
    }
} 
