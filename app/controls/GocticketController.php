<?

class GocticketController extends GOCServiceController
{ 
    function init()
    {
        $this->view->submenu_selected = "open";
        $this->form_title = "GOC Ticket Bugs / Requests";
    }

    function addElements($form) {
        //nothing to add
    }
    function processFields($form, $footprint) {
        //nothing to pull
    }
} 
