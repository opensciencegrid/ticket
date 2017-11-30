<?

class BulkresourceController extends BaseController
{
    function init()
    {
        user()->allows("admin");

        $this->view->page_title = "Bulk Resource Ticket Submitter";
        $this->view->menu_selected = "user";
        $this->view->submenu_selected = "bulkresource";

        $model = new Resource();
        $kv = array();
        foreach($model->fetchAll() as $resource) {
            $id = $resource->id;
            $name  = $resource->name;
            $fqdn = $resource->fqdn;
            $kv[$id] = "$name ($fqdn)";
        }
        Zend_Registry::set("resource_kv", $kv);
        Zend_Registry::set("resource_ids", array());
    }

    public function indexAction()
    {
        $form = $this->getForm();

        //redo is set when user hit "cancel" button in the preview page
        if(isset($_REQUEST["redo"])) {
            //populate data from session
            $session = new Zend_Session_Namespace('bulkresource');

            Zend_Registry::set("resource_ids", $session->resource_ids);
            Zend_Registry::set("passback_ccs", $session->cc);
            $form->getElement("title")->setValue($session->title);
            $form->getElement("template")->setValue($session->template);
            $form->getElement("name")->setValue($session->name);
            $form->getElement("email")->setValue($session->email);
            $form->getElement("phone")->setValue($session->phone);
        }
        $this->view->form = $form;
        $this->render();
    }

    public function getForm()
    {
        $form = $this->initForm("bulkresource");

        $title = new Zend_Form_Element_Text('title');
        $title->setLabel("Title");
        $title->setRequired(true);
        $title->setValue("Resource Issue for \$RESOURCE_NAME");
        $form->addElement($title);

        $template = new Zend_Form_Element_Textarea('template');
        $template->setLabel("Description Template");
        $template->setRequired(true);
        $template->setValue("Dear \$PRIMARY_ADMIN_NAME,

Something is screwed up on resource \$RESOURCE_NAME.

Please fix it.

Thank You,
OSG Grid Operations Center (GOC)
Email/Phone: help@opensciencegrid.org, 317-278-9699
GOC Homepage: http://www.opensciencegrid.org/ops
RSS Feed: http://osggoc.blogspot.com");
        $form->addElement($template);
        return $form;
    }

    public function previewAction() 
    {
        $resource_ids = @$_REQUEST["list"];

        $form = $this->getForm();
        if($form->isValid($_POST)) {
            //do some additional validation - that wasn't checked via Zend form
            if(!isset($_REQUEST["list"])) {
                $this->view->errors = "Please specify list of resource to send tickets to.";
                $this->view->form = $form;
                $this->render("index");
                return;
            }

            $title = $_REQUEST["title"];
            $template = $_REQUEST["template"];

            //store various data into session - so that submiAction can use it (also used by createTickets)
            $session = new Zend_Session_Namespace('bulkresource');
            $resource_ids = $session->resource_ids = $resource_ids;
            $session->title = $title;
            $session->template = $template;
            if(isset($_REQUEST["cc"])) {
                $session->cc = $_REQUEST["cc"];
            }
            $session->name = $form->getValue("name");
            $session->email = $form->getValue("email");
            $session->phone = $form->getValue("phone");

            //all good... construct ticket & send to preview
            $tickets = $this->createTickets($resource_ids, $title, $template);
            $preview = array();
            $metadata = array();
            foreach($tickets as $rname=>$ticket) {
                $preview[$rname] = $ticket->prepareParams();
                $metadata[$rname] = $ticket->metadata;
            }
            $this->view->preview = $preview;
            $this->view->metadata = $metadata;
        } else {
            //selected resources are sent back through registry - since it's not controlled via zend form
            Zend_Registry::set("resource_ids", $resource_ids);

            //cc field is also non-zend, but BaseController::initForm pulls it from $_POST["cc"]

            $this->view->errors = "Please correct the following issues.";
            $this->view->form = $form;
            $this->render("index");
        }

        return $tickets;
    }

    function createTickets($resource_ids, $title, $template) 
    {
        $model = new Resource();
        $resources = $model->fetchAllGroupByID();
        $prac_model = new PrimaryResourceAdminContact();

        $tickets = array();
        foreach($resource_ids as $rid=>$ignore) {
            $resource = $resources[$rid];

            $prac = $prac_model->fetch($rid);
            $mytemplate = $this->applyTemplate($template, $resource, $prac);
            $mytitle = $this->applyTemplate($title, $resource, $prac);

            $ticket = $this->createTicket($mytitle, $mytemplate, $resource);
            $tickets[$resource->name] = $ticket;
        }
        return $tickets;
    }

    public function submitAction() 
    {
        $session = new Zend_Session_Namespace('bulkresource');
        $resource_ids = $session->resource_ids;
        $title = $session->title;
        $template = $session->template;

        $tickets = $this->createTickets($resource_ids, $title, $template);

        $success = array();
        $failed = array();
        foreach($tickets as $rname=>$ticket) {
            try {
                $mrid = $ticket->submit();
                $success[$rname] = $mrid;
            } catch(exception $e) {
                $this->sendErrorEmail($e);
                $failed[$rname] = $e;
            }
        }
        $this->view->success = $success;
        $this->view->failed = $failed;

        //clear session
        $session->resource_ids = null;
    }

/*
    private function getFPAgent($name)
    {
        $model = new Schema();
        $users = $model->getusers();
        foreach($users as $id=>$fpname) {
            if($fpname == $name) {
                return $id;
            }
        }
        return null;
    }
*/
    function createTicket($title, $desc, $resource) 
    {
        $name = user()->getPersonName();
        $session = new Zend_Session_Namespace('bulkresource');

        $footprint = new Footprint();

        $footprint->setName($session->name);
        $footprint->setEmail($session->email);
        $footprint->setOfficePhone($session->phone);

        //process CC
        if(isset($session->cc)) {
            $ccs = $session->cc;
            foreach($ccs as $cc) {
                $cc = trim($cc);
                if($cc != "") {
                    $footprint->addCC($cc);
                }
            }
        }

        $footprint->addDescription($desc);
        $footprint->setTitle($title);

        //set submitter to the ticket submitter's name ONLY IF the user is registered at FP - otherwise FP throws up
        $agent = $this->getFPAgent($name);
        if($agent !== null) {
            $footprint->setSubmitter($agent);
        } else {
            $footprint->addDescription("\n\n-- by $name");
        }
        $footprint->setSubmitterName(user()->getPersonName());

        //lookup service center
        $resource_id = $resource->id;
        $rs_model = new ResourceSite();
        $resource_name = $resource->name;
        $resource_group_id = $resource->resource_group_id;
        $resource_group_model = new ResourceGroup();
        $resource_group = $resource_group_model->fetchByID($resource_group_id);

        //set description destination vo, assignee
        $footprint->addMeta("Resource on which user is having this issue: ".$resource_name."($resource_id)\n");
        $footprint->setMetadata("ASSOCIATED_RG_ID", $resource_group_id);
        $footprint->setMetadata("ASSOCIATED_RG_NAME", $resource_group->name);
        $footprint->setMetadata("ASSOCIATED_R_ID", $resource_id);
        $footprint->setMetadata("ASSOCIATED_R_NAME", $resource_name);

        $footprint->setMetadata("SUBMITTER_NAME", $name);
        $footprint->setMetadata("SUBMITTER_IP", $_SERVER["REMOTE_ADDR"]);
        $footprint->setMetadata("SUBMITTER_AGENT", $_SERVER["HTTP_USER_AGENT"]);
        if(user()->getDN() !== null) {
            $footprint->setMetadata("SUBMITTER_DN", user()->getDN());
        }
        $footprint->setMetadata("SUBMITTED_VIA", "GOC Ticket/".$this->getRequest()->getControllerName());

        //$void = $footprint->setDestinationVOFromResourceID($resource_id);
        $model = new Resource();
        $vo = $model->getPrimaryOwnerVO($resource_id);
        if($vo !== null) {
            $footprint->addMeta("Primary Owner VO: $vo->name");
            $footprint->setMetadata("ASSOCIATED_VO_ID", $vo->vo_id);
        }

        $sc_id = $rs_model->fetchSCID($resource_id);
        if(!$sc_id) {
            $scname = "OSG-GOC";
            $footprint->addMeta("Couldn't find the support center that supports this resource. Please see finderror page for more detail.\n");
        } else {
            //lookup SC name form sc_id
            $sc_model = new SC;
            $sc = $sc_model->get($sc_id);
            $scname = $sc->footprints_id;
            $footprint->setMetadata("SUPPORTING_SC_ID", $sc_id);
        }

        $footprint->addAssignee($scname);
        $footprint->addPrimaryAdminContact($resource_id);

        return $footprint;
    }

    function applyTemplate($template, $resource, $prac) {
        $desc = $template;
        $desc = str_replace("\$RESOURCE_NAME", $resource->name, $desc);
        $desc = str_replace("\$RESOURCE_FQDN", $resource->fqdn, $desc);
        $desc = str_replace("\$PRIMARY_ADMIN_NAME", $prac->name, $desc);
        return $desc;
    }

} 
