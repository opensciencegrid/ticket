<?

class TwikiController extends GOCServiceController
{ 
    function init()
    {
        $this->view->submenu_selected = "open";
        $this->form_title = "Twiki Bugs / Requests";
    }

    function addElements($form) {
        $e = new Zend_Form_Element_Radio("type");
        $e->setLabel("Issue Regarding")
        ->addMultiOptions(array(
            "bug" => "Bugs or Outage on TWiki itself",
            "content" => "Content or Account",
        ));
        $e->setRequired(true);

        $form->addElement($e);

    }
    function processFields($form, $footprint) {
        if($form->getValue('type') == "content") {
            //Assign GOC support
            $model = new NextAssignee();
            $gocid = $model->getNextAssignee();
            $footprint->addAssignee($gocid, true); //reset assignee first
        } else {
            //by default. all GOC Service ticket goes to infrastructure
        }
    }
} 
