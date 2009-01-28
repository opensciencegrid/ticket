<?

class NotifyController extends BaseController
{ 
    public function init()
    {
        $this->view->submenu_selected = "open";

        //only security admin are allowed to access this form
        if(!in_array(role::$security_admin, user()->roles)) {
            $this->render("error/404", null, true);
            return;
        }
    }

    public function indexAction() 
    { 
        $this->view->form = $this->getForm();
        $this->render();
    }

    public function submitAction()
    {
        if($_REQUEST["rss"] == 1) {
            $do_rss = true;
        }

        $form = $this->getForm();

        if($do_rss) {
            $e = $form->getElement("description");
            $e->setRequired(true);
        }

        if($form->isValid($_POST)) {
            //construct security email object
            $e = new SecurityEmail();
            if($form->getValue('rsecurity')) {
                $e->addResourceSecurityAddresses();
            }
            if($form->getValue('vsecurity')) {
                $e->addVOSecurityAddresses();
            }
            if($form->getValue('ssecurity')) {
                $e->addSCSecurityAddresses();
            }
            if($form->getValue('support')) {
                $e->addSupportAddresses();
            }
            if($form->getValue('general')) {
                $e->addAddress("osg-general@opensciencegrid.org");
            }
            if($form->getValue('operations')) {
                $e->addAddress("osg-operations@opensciencegrid.org");
            }
            if($form->getValue('sites')) {
                $e->addAddress("osg-sites@opensciencegrid.org");
            }
            $e->setTo('goc@opensciencegrid.org');
            $body = $form->getValue('body');
            $e->setBody($body);
            $subject = $form->getValue('subject');
            $ticket_id = $form->getValue('ticket');
            if($ticket_id != "") { 
                $subject .= " - GOC Ticket # " . $ticket_id;
            }
            $e->setSubject($subject);
        
            try 
            {
                if(config()->simulate) {
                    echo "<h1>Simulation</h2>";
                    $e->dump();
                    $this->render("none", null, true);
                } else {
                    $e->send();
                    if($do_rss) {
                        $description = $form->getValue('description');
                        $r = new RSSFeed();
                        $r->insert($subject, $ticket_id, $description, $body);
                    }
                    $this->render("processed", null, true);
                }
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

/*
    public function testAction()
    {
        $r = new RSSFeed();
        $r->insert("First Post", null, "Some desc..", "Here is the first post from GOC Ticket application.");
        $this->render("none", null, true);
    }
*/
    private function getForm()
    {
        $form = $this->initForm("notify", false); //false - no contact information necessary
        
        $e = new Zend_Form_Element_Checkbox('rsecurity');
        $form->addElement($e);
        $e = new Zend_Form_Element_Checkbox('vsecurity');
        $form->addElement($e);
        $e = new Zend_Form_Element_Checkbox('ssecurity');
        $form->addElement($e);
        $e = new Zend_Form_Element_Checkbox('support');
        $form->addElement($e);
        $e = new Zend_Form_Element_Checkbox('general');
        $form->addElement($e);
        $e = new Zend_Form_Element_Checkbox('sites');
        $form->addElement($e);
        $e = new Zend_Form_Element_Checkbox('operations');
        $form->addElement($e);

        $e = new Zend_Form_Element_Text('subject');
        $e->setLabel("Subject");
        $e->setRequired(true);
        $form->addElement($e);

        $e= new Zend_Form_Element_Text('ticket');
        $e->setLabel("Footprint Ticket ID");
        $e->addValidator(new Zend_Validate_Int()); //ture for allowWhiteSpace
        $e->setDescription("* Optional");
        $e->addDecorator("description");
        $e->setRequired(false);
        $form->addElement($e);

        $detail = new Zend_Form_Element_Textarea('body');
        $detail->addValidator(new Zend_Validate_StringLength(1, 1024*1024*16)); //max 16M for MEDIUMTEXT
        $detail->setLabel("Body");
        $detail->setRequired(true);
        $detail->setValue($this->getTemplate());
        $form->addElement($detail);

        $e = new Zend_Form_Element_Checkbox('rss');
        $e->setLabel("Publish to RSS");
        $form->addElement($e);

        $e = new Zend_Form_Element_Text('description');
        $e->setLabel("RSS Description");
        $e->setDescription("* For RSS Feed");
        $e->addDecorator("description");
        $form->addElement($e);

        $submit = new Zend_Form_Element_Submit('submit_button');
        $submit->setLabel("Send Email");
        $form->addElement($submit);

        return $form;
    }
    private function getTemplate()
    {
        return "(ENTER MESSAGE HERE)

Please submit problems, requests, and questions at:
https://oim.grid.iu.edu/gocticket/


Thank You,
OSG Grid Operations Center (GOC)
Submit a GOC ticket: https://oim.grid.iu.edu/gocticket
Email/Phone: goc@opensciencegrid.org, 317-278-9699
GOC Homepage: http://www.opensciencegrid.org/ops
RSS Feed: http://www.grid.iu.edu/news
";
    }
} 

class SecurityEmail
{
    public function __construct()
    {
        $this->h_email = array();
        $this->bcc = "";
        $this->pa_model = new PrimaryAddress();
    }

    public function setTo($val) { $this->to = $val; }
    public function setSubject($val) { $this->subject = $val; }
    public function setBody($val) { $this->body = $val; }

    public function addAddress($email)
    {
        if (!isset($this->h_email[$email])) {
            $this->bcc .= $email . ", "; 
            $this->h_email[$email]=1;
        }
    }
    private function addAddresses($recs)
    {
        foreach($recs as $rec) {
            $email = $rec->primary_email;
            $this->addAddress($email);
        }
    }

    public function addResourceSecurityAddresses()
    {
        $recs = $this->pa_model->get_resource_security();
        $this->addAddresses($recs);
    }

    public function addVOSecurityAddresses()
    {
        $recs = $this->pa_model->get_vo_security();
        $this->addAddresses($recs);
    }

    public function addSCSecurityAddresses()
    {
        $recs = $this->pa_model->get_sc_security();
        $this->addAddresses($recs);
    }

    public function addSupportAddresses()
    {
        $recs = $this->pa_model->get_sc();
        $this->addAddresses($recs);
    }
    public function dump()
    {
        echo "<hr><p>To: ".$this->to."\n\n";
        echo "<hr><p>From: ".config()->email_from."\n\n";
        echo "<hr><p>Subject: ".$this->subject."\n\n";
        echo "<hr><p>BCC: ".$this->bcc."\n\n";
        echo "<hr><p>Body: ".$this->body."\n\n";
        echo "<hr><p>\n\n";
    }

    public function send()
    {
        mail($this->to, 
            $this->subject, 
            $this->body,
            "From: " . config()->email_from . " \nBcc: " . $this->bcc);

        slog("[submit] Security Email Sent with following content --------------------------");
        slog(print_r($this, true));
    }
}
