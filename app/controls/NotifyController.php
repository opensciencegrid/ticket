<?

class NotifyController extends BaseController
{ 
    public function init()
    {
        $this->view->submenu_selected = "open";

    }

    public function indexAction() 
    { 
        //only security admin are allowed to access this form
        if(!in_array(role::$security_admin, user()->roles)) {
            $this->render("error/access", null, true);
            return;
        }

        $this->view->form = $this->getForm();
        $this->render();
    }

    public function submitAction()
    {
        //only security admin are allowed to access this form
        if(!in_array(role::$security_admin, user()->roles)) {
            $this->render("error/access", null, true);
            return;
        }

        $do_rss = false;
        if($_REQUEST["rss"] == 1) {
            $do_rss = true;
        }

        $form = $this->getForm();

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

            $model = new Person();
            $person_id = $form->getValue('email_from');
            if($person_id != "") {
                $person = $model->fetchPerson($person_id);
                $e->setFrom($person->first_name." ".$person->last_name." <".$person->primary_email.">");
            } else {
                $e->setFrom(config()->email_from);
            }

            $e->setTo('goc@opensciencegrid.org');
            $body = $form->getValue('body');
            $sig = $form->getValue('sig');
            $e->setBody($body."\n".$sig);
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
                        $r = new RSSFeed();
                        $r->insert($subject, $ticket_id, $body);
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

        $e = new Zend_Form_Element_Select('email_from');
        $e->setLabel("Sender Address");
        $e->addMultiOption(null, config()->email_from);
        $model = new PrimaryEmail();
        $contacts = $model->fetchAll(4);
        foreach($contacts as $contact) {
            $e->addMultiOption($contact->person_id, $contact->first_name." ".$contact->last_name." <".$contact->primary_email.">");
        }
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
        $form->addElement($detail);

        $e = new Zend_Form_Element_Textarea('sig');
        $e->setLabel("Email Signature");
        $e->setRequired(true);
        $e->setValue($this->getSigTemplate());
        $form->addElement($e);

        $e = new Zend_Form_Element_Checkbox('rss');
        $e->setLabel("Publish to RSS");
        $form->addElement($e);

        $submit = new Zend_Form_Element_Submit('submit_button');
        $submit->setLabel("Send Email");
        $form->addElement($submit);

        return $form;
    }
    private function getSigTemplate()
    {
        return "
Please submit problems, requests, and questions at:
https://oim.grid.iu.edu/gocticket/

Thank You,
OSG Grid Operations Center (GOC)
Submit a GOC ticket: https://oim.grid.iu.edu/gocticket
Email/Phone: goc@opensciencegrid.org, 317-278-9699
GOC Homepage: http://www.opensciencegrid.org/ops
RSS Feed: http://osggoc.blogspot.com
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
        $this->from = config()->email_from;
    }

    public function setFrom($val) { $this->from = $val; }
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
        echo "<hr>To: ".$this->to."\n\n";
        echo "<hr>From: ".htmlentities($this->from)."\n\n";
        echo "<hr>Subject: ".$this->subject."\n\n";
        echo "<hr>BCC: ".$this->bcc."\n\n";
        echo "<hr>Body:<pre> ".$this->body."</pre>\n\n";
        echo "<hr>\n\n";
    }

    public function send()
    {
        signedmail($this->to, $this->subject, $this->body, "Bcc: ".$this->bcc);

        slog("[submit] Signed notification email sent with following content --------------------------");
        slog(print_r($this, true));
    }
}
