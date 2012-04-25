<?
class VoController extends BaseController
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
            $footprint->addDescription($form->getValue("detail"));
            $footprint->setTitle($form->getValue("title"));

            $void = $form->getValue("vo");
            $footprint->setMetadata("ASSOCIATED_VO_ID", $void);
            $footprint->addPrimaryVOAdminContact($void);

            $model = new VO();
            $vo = $model->get($void);
            //$footprint->setDestinationVO($vo->footprints_id);

            //lookup SC name
            $sc_model = new SC();
            $sc = $sc_model->get($vo->sc_id);
            if(!$sc) {
                $footprint->addMeta("Failed to find active support center with id ".$vo->sc_id);
            } else {
                $footprint->setMetadata("SUPPORTING_SC_ID", $sc->id);
                $scname = $sc->footprints_id;
                $footprint->addAssignee($scname);
                /*
                if($footprint->isValidFPSC($scname)) {
                    $footprint->addAssignee($scname);
                } else {
                    $footprint->addMeta("Couldn't add ".$sc->name." (footprints_id:$scname) support center as assignee for selected VO:$void since it doesn't exist on FP yet.. (Please correct issues reporeted in admin/finderror page!)\n");
                }
                */
            }

            //now submit!
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

    private function getForm()
    {
        $form = $this->initForm("vo");

        $e = new Zend_Form_Element_Select('vo');
        $e->setLabel("I am having this issue in following Virtual Organization");
        $e->setRequired(true);
        $model = new VO();
        $vos = $model->fetchAll();
        $e->addMultiOption(null, "(Please Select)");
        foreach($vos as $vo) {
            $e->addMultiOption($vo->id, $vo->name);
        }
        $form->addElement($e);

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
