<?

class CampusController extends BaseController
{ 
    public function init()
    {
        $this->view->page_title = "Campus Researcher Club Request Form";
        $this->view->menu_selected = "user";
        $this->view->submenu_selected = "campus";
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
            $footprints->setTitle("Campus Researcher Club Request Form from ".$form->getValue('institution'));
            $desc = "";
            $desc .= "Institution: ".$form->getValue("institution")."\n\n";
            $desc .= "Departmennt: ".$form->getValue("department")."\n\n";
            $desc .= "Preferred Sponsor: ".$_REQUEST["sponsor"]."\n\n";
            $desc .= "Research Description:\n".$_REQUEST["desc"]."\n\n";
            $desc .= "Why Good Fit:\n".$_REQUEST["fit"]."\n\n";
            $desc .= "How Heard About:\n".$_REQUEST["heard"]."\n\n";
            $desc .= "Comments:\n".$_REQUEST["comments"]."\n\n";
            $desc .= "When To Call:\n".$_REQUEST["call"]."\n\n";
            $footprints->addDescription($desc);
            $footprints->addCC("osg-crc@opensciencegrid.org");

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

    private function getForm() {
        $form = $this->initForm("campus");

        $e = new Zend_Form_Element_Text('institution');
        $e->setAttribs(array('size'=>50));
        $e->setLabel("Your Institution");
        $e->setRequired(true);
        $form->addElement($e);

        $e = new Zend_Form_Element_Text('department');
        $e->setAttribs(array('size'=>50));
        $e->setLabel("Your Department");
        $e->setRequired(true);
        $form->addElement($e);

        $submit = new Zend_Form_Element_Submit('submit_button');
        $submit->setLabel("   Submit   ");
        $form->addElement($submit);

        return $form;
    }
} 
