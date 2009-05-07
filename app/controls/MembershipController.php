<?

class MembershipController extends BaseController
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

        //make vo_id_requested required if voknown is selected
        $knowvo = $_REQUEST["knowvo"];
        if($knowvo == "true") {
            $elem = $form->getElement("vo_id_requested");
            $elem->setRequired(true);
        }

        if($form->isValid($_POST)) {
            $footprint = $this->initSubmit($form);
            $footprint->addDescription($form->getValue('detail'));

            $name = $form->getValue('name');
            $title = $name. " requesting VO membership to ";

            if($knowvo == "true") {
                $void = $form->getValue('vo_id_requested');

                //lookup sc_id from void
                $vo_model = new VO();
                $info = $vo_model->get($void);

                $footprint->setDestinationVO($info->footprints_id);

                //lookup SC name
                $sc_model = new SC;
                $sc = $sc_model->get($info->sc_id);
                $scname = $sc->footprints_id;

                $footprint->addMeta("Submitter is requesting a membership at VO:".$info->footprints_id."\n");
                $footprint->addMeta("This VO is supported at SC:$scname\n");

                if($footprint->isValidFPSC($scname)) {
                    $footprint->addAssignee($scname);
                } else {
                    $footprint->addMeta("Couldn't add assignee $scname since it doesn't exist on FP yet.. (Please sync!)\n");
                }
                //$footprint->addMeta("VO Detail for ".$info->footprints_id."\n".$this->dumprecord($info)."\n");
                $title .= $info->name;
            } else {
                $footprint->addMeta("Submitter doesn't know the VO to request membership to.\n");
                $title .= "unknown vo";
            }
            $footprint->setTitle($form->getValue('title'));

            //add DN as meta
            $dn = user()->getDN();
            if($dn == null) {
                $footprint->addMeta("Submitter's DN is unknown.\n");
            } else {
                $footprint->addMeta("Submitter's DN: $dn\n");
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

    private function getForm()
    {
        $form = $this->initForm("membership");

        $e = new Zend_Form_Element_Text('title');
        $e->setAttribs(array('size'=>50));
        $e->setLabel("Title");
        $e->setValue("Request for OSG membership");
        $e->setRequired(true);
        $form->addElement($e);

        $detail = new Zend_Form_Element_Textarea('detail');
        $detail->setLabel("Please describe why you are requesting this membership");
        $detail->setRequired(true);
        $form->addElement($detail);

        $submit = new Zend_Form_Element_Submit('submit_button');
        $submit->setLabel("Submit");
        $form->addElement($submit);

        $element = new Zend_Form_Element_Select('knowvo');
        $element->setLabel("Do you know which VO you are requesting your membership to?");
        $element->addMultiOption("false", "I don't know / not sure");
        $element->addMultiOption("true", "Yes");
        $form->addElement($element);

        $vo = new Zend_Form_Element_Select('vo_id_requested');
        $vo->setLabel("VO where you need an access");
        $vo->addMultiOption(null, "(Please Select)");
        $vo_model = new VO;
        $vos = $vo_model->fetchAll();
        foreach($vos as $v) {
            $vo->addMultiOption($v->id, $v->name);
        }
        $form->addElement($vo);

        return $form;
    }
} 
