<?
abstract class GOCServiceController extends BaseController
{ 
    public function indexAction()
    {
        $this->view->form = $this->getForm();
        $this->view->form_title = $this->form_title;
        $this->render();
    }

    abstract function processFields($form, $footprint);

    public function submitAction()
    {
        slog("GOC Servicve form submitted with following requests");
        slog(print_r($_REQUEST, true));

        $form = $this->getForm();

        if($form->isValid($_POST)) {
            $footprint = $this->initSubmit($form);
            $footprint->addDescription($form->getValue('detail'));
            $footprint->setTitle($form->getValue('title'));
            $footprint->setDestinationVO("MIS");
            $footprint->addAssignee("hayashis", true); //reset assignee first
            $footprint->addAssignee("agopu");

            //let derived class add things
            $this->processFields($form, $footprint);

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

    abstract function addElements($form);

    private function getForm()
    {
        $form = $this->initForm(Zend_Controller_Front::getInstance()->getRequest()->getControllerName());
        $e = new Zend_Form_Element_Text('title');
        $e->setAttribs(array('size'=>50));
        $e->setLabel("Title");
        $e->setValue($this->form_title);
        $e->setRequired(true);
        $form->addElement($e);

        $this->addElements($form);

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
