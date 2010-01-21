<?

class GocticketController extends GOCServiceController
{ 
    function init()
    {
        $this->view->submenu_selected = "open";
        $this->form_title = "GOC Ticket Bugs / Requests";
    }

    function addElements($form) {
        $elem = new Zend_Form_Element_Text('ref');
        $elem->setLabel("URL");
        $elem->setDescription("URL that relates to this ticket");
        $elem->setValue(@$_REQUEST["ref"]);
        $form->addElement($elem);
    }
    function processFields($form, $footprint) {
        $url = $form->getValue("ref");
        $footprint->addMeta("URL: ".$url);
    }
} 
