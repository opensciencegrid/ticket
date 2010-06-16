<?

class BulkresourceController extends Zend_Controller_Action
{
    function init()
    {
        $this->view->submenu_selected = "admin";

        $model = new Resource();
        $kv = array();
        foreach($model->fetchAll() as $resource) {
            $id = $resource->id;
            $name  = $resource->name;
            $fqdn = $resource->fqdn;
            $kv[$id] = "$name ($fqdn)";
        }
        $this->view->kv = $kv;

        $session = new Zend_Session_Namespace('bulkresource');
        if(isset($session->resource_ids)) {
            $this->view->resource_ids = $session->resource_ids;
        }
        if(isset($session->title)) {
            $this->view->title = $session->title;
        } else {
            $this->view->title = "Resource Issue for \$RESOURCE_NAME";
        }
        if(isset($session->template)) {
            $this->view->template = $session->template;
        } else {
            $this->view->template = "Dear \$PRIMARY_ADMIN_NAME,

Something is screwed up on resource \$RESOURCE_NAME.

Please fix it.

Thank You,
OSG Grid Operations Center (GOC)
Email/Phone: goc@opensciencegrid.org, 317-278-9699
GOC Homepage: http://www.opensciencegrid.org/ops
RSS Feed: http://osggoc.blogspot.com";
        }
    }

    public function indexAction()
    {
        if(!user()->allows("admin")) {
            $this->render("error/access", null, true);
            return;
        }
    }

    public function previewAction() 
    {
        if(!user()->allows("admin")) {
            $this->render("error/access", null, true);
            return;
        }

        if(!isset($_REQUEST["list"])) {
            addMessage("Please specify list of resource to send tickets to."); 
            $this->render("index");
            return;
        }

        $resource_ids = $_REQUEST["list"];
        $title = $_REQUEST["title"];
        $template = $_REQUEST["template"];

        $session = new Zend_Session_Namespace('bulkresource');
        $session->resource_ids = $resource_ids;
        $session->title = $title;
        $session->template = $template;

        $tickets = $this->createTickets($resource_ids, $title, $template);

        $preview = array();
        foreach($tickets as $rname=>$ticket) {
            $preview[$rname] = $ticket->prepareParams();
        }
        $this->view->preview = $preview;

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
        if(!user()->allows("admin")) {
            $this->render("error/access", null, true);
            return;
        }

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
    }

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

    function createTicket($title, $desc, $resource) 
    {
        $name = user()->getPersonName();

        $footprint = new Footprint();
        $footprint->setName($name);
        $footprint->setEmail(user()->getPersonEmail());
        $footprint->setOfficePhone(user()->getPersonPhone());
        $footprint->setOriginatingVO("MIS");
        $footprint->addDescription($desc);
        $footprint->setTitle($title);

        //set submitter to the ticket submitter's name ONLY IF the user is registered at FP - otherwise FP throws up
        $agent = $this->getFPAgent($name);
        if($agent !== null) {
            $footprint->setSubmitter($agent);
        } else {
            $footprint->addMeta("Real Submitter: $name (not a registered Footprint Agent - using default submitter)\n");
        }

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

        $void = $footprint->setDestinationVOFromResourceID($resource_id);
        if($void) {
            $footprint->setMetadata("ASSOCIATED_VO_ID", $void);
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

        if($footprint->isValidFPSC($scname)) {
            $footprint->addAssignee($scname);
            $footprint->addMeta("Assigned support center: $scname which supports this resource\n");
        } else {
            $footprint->addMeta("Couldn't add assignee $scname since it doesn't exist on FP yet.. (Please sync!)\n");
            elog("Couldn't add assignee $scname since it doesn't exist on FP yet.. (Please sync!)\n");
        }
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
