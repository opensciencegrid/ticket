<?

class SubmitController extends BaseController
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
            if(isset($_POST["resources"])) {
                    $this->processResource($footprints, $_POST["resources"]);
            }
            $footprint->addDescription($form->getValue('detail'));
            $footprint->setTitle($form->getValue('title'));
            $footprint->setDestinationVO("MIS");//lie.. we should be deprecating this soon

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

    private function processResource($footprints, $dirty_resource_ids)
    {
        $rs_model = new ResourceSite();
        $resource_model = new Resource();
        $resource_group_model = new ResourceGroup();
        $sc_model = new SC();

        $meta_rg_ids = array();
        $meta_rg_names = array();
        $meta_r_ids = array();
        $meta_r_names = array();
        /*
        $meta_vo_ids = array();
        $meta_vo_names = array();
        */
        foreach($dirty_resource_ids as $dirty_rid) {
            $resource_id = (int)$dirty_rid;
            $resource = $resource_model->fetchByID($resource_id);
            $resource_group = $resource_group_model->fetchByID($resource->resource_group_id);
            /*
            $primary_vo = $resource_model->getPrimaryOwnerVO($resource_id);
            */

            $meta_r_ids[] = $resource_id;
            $meta_r_names[] = $resource->name;
            $meta_rg_ids[] = $resource->resource_group_id;
            $meta_rg_names[] = $resource_group->name;
            /*
            if(!$vo) {
                $meta_vo_ids[] = $primary_vo->id;
                $meta_vo_names[] = $primary_vo->name;
            }
            */

            $sc_id = $rs_model->fetchSCID($resource_id);
            if(!$sc_id) {
                $scname = "OSG-GOC";
                $footprint->addMeta("Couldn't find the support center that supports resource (".$resource->name."). Please see finderror page for more detail.\n");
            } else {
                $sc = $sc_model->get($sc_id);
                $meta_sc_ids[] = $sc->footprints_id;
            }

            $footprint->addMeta("Resource on which user is having this issue: ".$resource->name."($resource_id)\n");
            $footprint->addPrimaryAdminContact($resource_id);
        }

        $footprint->setMetadata("ASSOCIATED_R_ID", implode(",",$meta_r_ids));
        $footprint->setMetadata("ASSOCIATED_R_NAME", implode(",",$meta_r_names));
        $footprint->setMetadata("ASSOCIATED_RG_ID", implode(",",$meta_rg_ids));
        $footprint->setMetadata("ASSOCIATED_RG_NAME", implode(",",$meta_rg_names));
        $footprint->setMetadata("SUPPORTING_SC_ID", implode(",",$meta_sc_ids)); //TODO -- GOC_TX won't work...
        /*
        $footprint->setMetadata("ASSOCIATED_VO_ID", implode(",", $meta_vo_ids));
        $footprint->setMetadata("ASSOCIATED_VO_NAME", implode(",", $meta_vo_names));
        */
    }

    private function getForm()
    {
        $form = $this->initForm("submit");

        $e = new Zend_Form_Element_Text('title');
        $e->setAttribs(array('size'=>50));
        $e->setLabel("Short Description (Title)");
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
} 
