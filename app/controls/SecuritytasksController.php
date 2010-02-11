<?

class SecuritytasksController extends BaseController
{ 
    public function init()
    {
        //don't use this - we are really doing 2 things here..
        //1) rendering the real admin page via browser
        //2) accepting request from cron from localhost
    }

    public function indexAction()
    {
        $this->view->submenu_selected = "securitytasks";

        if(!user()->allows("notify")) {
        //if(!in_array(role::$goc_admin, user()->roles)) {
            $this->render("error/access", null, true);
            return;
        }
    }

    private function accesscheck($remote_addr = null)
    {
        //make sure the request originated from localhost or $remote_addr
        if($_SERVER["REMOTE_ADDR"] != $remote_addr and !islocal()) {
            //pretend that this page doesn't exist
            $this->getResponse()->setRawHeader('HTTP/1.1 404 Not Found');
            elog("Illegal access to ticket Security Tasks controller from ".$_SERVER["REMOTE_ADDR"]);
            exit;
        }
    }
    public function logrotateAction()
    {
        $this->accesscheck();

        dlog("Writing config file for logrotate...");
        $root = getcwd()."/";
        $statepath = "/tmp/ticket.rotate.state";
        $config = "compress \n".
            $root.config()->logfile. " ". 
            $root.config()->error_logfile. " ". 
            $root.config()->audit_logfile." {\n".
            "   rotate 5\n".
            "   size=50M\n".
            "}";
        $confpath = "/tmp/ticket.rotate.conf";
        $fp = fopen($confpath, "w");
        fwrite($fp, $config);
        
        dlog("running logroate with following config\n$config");
        passthru("/usr/sbin/logrotate -s $statepath $confpath");
    }
}
