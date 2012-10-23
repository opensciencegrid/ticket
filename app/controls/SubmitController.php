<?

class SubmitController extends BaseController
{ 
    public function init()
    {
        $this->view->page_title = "Submit Ticket";
        $this->view->menu_selected = "submit";
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
            $footprints = $this->initSubmit($form);
            if(isset($_POST["resource_issue_check"]) && isset($_POST["resource"])) {
                //grab first key
                $ids = array_keys($_POST["resource"]);
                $this->processResource($footprints, $ids[0]);
            }
            if(isset($_POST["vo_issue_check"]) && isset($_POST["vo"])) {
                $ids = array_keys($_POST["vo"]);
                $this->processVO($footprints, $ids[0]);
            }
            if(isset($_POST["app_issue_check"])) {
                $this->processApp($footprints, $_POST["app_issue_type"]);
            }
            if(isset($_POST["security_issue_check"])) {
                $this->processSecurity($footprints);
            }
            if(isset($_POST["membership_issue_check"])) {
                $this->processMembership($footprints);
            }
            if(isset($_POST["campusvorequest_issue_check"])) {
                $this->processCampusVO($footprints);
            }
            $footprints->addDescription($form->getValue('detail'));
            $footprints->setTitle($form->getValue('title'));

            try
            {
                $mrid = $footprints->submit();
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

    private function processResource($footprints, $dirty_rid)
    {
        $rs_model = new ResourceSite();
        $resource_model = new Resource();
        $resource_group_model = new ResourceGroup();
        $sc_model = new SC();

        $resource_id = (int)$dirty_rid;
        $resource = $resource_model->fetchByID($resource_id);
        $resource_group = $resource_group_model->fetchByID($resource->resource_group_id);

        //optinally set VO
        $primary_vo = $resource_model->getPrimaryOwnerVO($resource_id);
        if(!$primary_vo) {
            $footprints->addMeta("Couldn't find the primary owner vo for resource (".$resource->name."). Please see finderror page for more detail.\n");
        } else {
            $footprints->setMetadata("ASSOCIATED_VO_ID", $primary_vo->vo_id);
            $footprints->setMetadata("ASSOCIATED_VO_NAME", $primary_vo->vo_name);
        }

        //optionally set SC
        $sc_id = $rs_model->fetchSCID($resource_id);
        if(!$sc_id) {
            $footprints->addMeta("Couldn't find the support center that supports resource (".$resource->name."). Please see finderror page for more detail.\n");
        } else {
            $sc = $sc_model->get($sc_id);
            $footprints->setMetadata("SUPPORTING_SC_ID", $sc->id);
            $footprints->setMetadata("SUPPORTING_SC_NAME", $sc->name);
            $footprints->addAssignee($sc->footprints_id);
        }

        $footprints->addMeta("Resource on which user is having this issue: ".$resource->name."($resource_id)\n");
        $footprints->addPrimaryAdminContact($resource_id);
        $footprints->setMetadata("ASSOCIATED_R_ID", $resource_id);
        $footprints->setMetadata("ASSOCIATED_R_NAME", $resource->name);
        $footprints->setMetadata("ASSOCIATED_RG_ID", $resource->resource_group_id);
        $footprints->setMetadata("ASSOCIATED_RG_NAME", $resource_group->name);
    }

    private function processVO($footprints, $dirty_void)
    {
        $void = (int)$dirty_void;

        $model = new VO();
        $vo = $model->get($void);
        //$footprint->setDestinationVO($vo->footprints_id);
        $footprints->addMeta("VO on which user is having this issue: ".$vo->name."($vo->id)\n");
        $footprints->setMetadata("ASSOCIATED_VO_ID", $vo->id);
        $footprints->setMetadata("ASSOCIATED_VO_NAME", $vo->name);
        $footprints->addPrimaryVOAdminContact($vo->id);

        //lookup SC name
        $sc_model = new SC();
        $sc = $sc_model->get($vo->sc_id);
        if(!$sc) {
            $footprints->addMeta("Failed to find active support center with id ".$vo->sc_id);
        } else {
            $footprints->setMetadata("SUPPORTING_SC_ID", $sc->id);
            $footprints->setMetadata("SUPPORTING_SC_NAME", $sc->name);
            $fpid = $sc->footprints_id;
            $footprints->addAssignee($fpid);
        }
    }

    private function processApp($footprints, $dirty_app_type) {

        switch($dirty_app_type) {
        case "bdii": $this->processAppBDII($footprints);break;
        case "ress_dev": $this->processAppSC($footprints, 40);break;//Ress SC
        case "ress_ops": $this->processAppSC($footprints, 7);break;//Fermilab SC (see ticket 11798 - Steve's comment)
        case "gratia_dev": $this->processAppSC($footprints, 47);break;//GRATIA Dev SC
        case "gratia_ops": $this->processAppSC($footprints, 39);break;//GRATIA Ops SC
        case "vdt": $this->processAppVDT($footprints);break;
        case "twiki": $this->processAppTWiki($footprints);break;
        case "gratiaweb": 
        case "goc":
            $this->processAppInfra($footprints);
            break;

        default: elog("unknown app_type given: ".$dirty_app_type);return;
        }
        $footprints->addMeta("Application issue with type: $dirty_app_type");
    }

    private function processAppBDII($footprints) {
        $bdiiserver = $_POST["bdiiserver"];
        $footprints->addMeta("BDII Issue on ".$bdiiserver);

        $down = $_POST["bdiidown"];
        $footprints->addMeta("Is the BDII completely down?: ".$down);

        if($down == "true" && $bdiiserver == "is-osg") {
            $footprints->addMeta("Opening ticket with CRITICAL priority\n");
            $footprints->setPriority(1); //set it to critical;
            //$footprints->setTicketType("Unscheduled__bOutage");
        }

        $footprints->addAssignee("steige", true); //clear list
        $footprints->addAssignee("hayashis");
    }

    private function processAppSC($footprints, $scid) {
        $sc_model = new SC();
        $sc = $sc_model->get($scid);//ReSS SC
        $footprints->setMetadata("SUPPORTING_SC_ID", $sc->id);
        $footprints->setMetadata("SUPPORTING_SC_NAME", $sc->name);
        $fpid = $sc->footprints_id;
        $footprints->addAssignee($fpid);
    }

    private function processAppInfra($footprints) {
        $footprints->addAssignee("steige", true); //clear list
        $footprints->addAssignee("hayashis");
        if(@$_POST["app_goc_url"] != "") {
            $footprints->addMeta("Affected URL: ".$_POST["app_goc_url"]);
        }
    }

    private function processAppVDT($footprints) {
        $footprints->addAssignee("osg-software-support-stream");//Software Support (Triage)
    }

    private function processAppTWiki($footprints) {
        if(@$_POST["twikitype"] == "bug") {
            //if it's bug, assign ticket to infrastructure
            $footprints->addAssignee("steige", true); //clear list
            $footprints->addAssignee("hayashis");
        }
    }

    private function processSecurity($footprints) {
        if(isset($_POST["security_issue_immediate"]) && isset($_POST["security_issue_available"])) {
            $footprints->addMeta("User need immediate attention, and available for contact - opening ticket with CRITICAL priority");
            $footprints->setPriority(1); //set it to critical
        }

        $footprints->addAssignee("rquick", true); 
        $footprints->addAssignee("kagross");
        $footprints->setTicketType("Security");
    }

    private function processMembership($footprints) {
        if($_POST["membership_vo"] == "") {
            $footprints->addMeta("Submitter doesn't know the VO they would like to request membership to.\n");
        } else {
            $void = (int)$_POST["membership_vo"];

            $vo_model = new VO();
            $info = $vo_model->get($void);
            $sc_model = new SC;
            $sc = $sc_model->get($info->sc_id);
            $fpid = $sc->footprints_id;

            $footprints->setMetadata("ASSOCIATED_VO_ID", $void);
            $footprints->setMetadata("ASSOCIATED_VO_NAME", $info->name);
            $footprints->setMetadata("SUPPORTING_SC_ID", $sc->id);
            $footprints->setMetadata("SUPPORTING_SC_NAME", $sc->name);
            $footprints->addMeta("Submitter is requesting membership to VO:$info->name which is supported by SC:$sc->name\n");
            $footprints->addAssignee($fpid);
        }
    }

    private function processCampusVO($footprints) {
        if($_POST["campusvorequest_name"] == "") {
            $footprints->addMeta("Submitter didn't specify the name of new campus grid VO");
        } else {
            $footprints->addMeta("Requested VO NAME: ".$_POST["campusvorequest_name"]);
            $footprints->addCC("dweitzel@cse.unl.edu");
            $footprints->addCC("fraser@anl.gov");
        }
    }

    private function getForm() {
        $form = $this->initForm("submit");

        $e = new Zend_Form_Element_Text('title');
        $e->setLabel("Ticket Title");
        //$e->setValue("Other Issues");
        $e->setRequired(true);
        $form->addElement($e);

        $detail = new Zend_Form_Element_Textarea('detail');
        $detail->setLabel("Description");
        $detail->setRequired(true);
        $form->addElement($detail);

        $submit = new Zend_Form_Element_Submit('submit_button');
        $submit->setLabel("   Submit   ");
        $form->addElement($submit);

        return $form;
    }

    public function templateAction() {
        //header("content-type", "text/html");
        $id = $_GET["id"];
        switch($id) {
        case "campusvorequest_issue_check":
            $this->render("template_campusvorequest");
            break;
        default:
            //error_log("invalid tepmlate id requested: $id");
            $this->render("template_na");
        }
    }
} 
