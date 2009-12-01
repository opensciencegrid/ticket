<?

class SoftwareController extends GOCServiceController
{ 
    function init()
    {
        $this->view->submenu_selected = "open";
        $this->form_title = "Software Cache Bugs / Requests";
    }

    function addElements($form) {
        //nothing to add
    }
    function processFields($form, $footprint) {
        //nothing to pull
    }
} 
