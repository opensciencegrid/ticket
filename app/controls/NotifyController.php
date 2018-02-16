<?

class NotifyController extends BaseController
{ 
    public function init()
    {
        user()->check("notify");

        $this->view->page_title = "GOC Email / RSS Notification";
        $this->view->menu_selected = "user";
        $this->view->submenu_selected = "notify";
    }

    public function indexAction() 
    { 
	                        if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
                                        slog("oauth access token already set");
                                }
                                else {
                                        slog("creating oauth access token in RSS Feed");
                                        $redirect_uri = 'https://' . $_SERVER['HTTP_HOST'] . '/oauth';
                                        header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
                                }
        $this->view->form = $this->getForm();
        $this->render();
    }

    public function submitAction()
    {
        $do_rss = false;
        if(@$_REQUEST["rss"] == 1) { $do_rss = true; }

        $form = $this->getForm();
        if($form->isValid($_POST)) {
            $this->view->detail = "<h2>Process Detail</h2><br/>";

            //construct email object
            $e = new Email();
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

            //pki
            if($form->getValue('raa')) {
                $e->addRAAAddresses();
            }
            if($form->getValue('rasponsor')) {
                $e->addRASponsorAddresses();
            }
            if($form->getValue('gridadmin')) {
                $e->addGridAdminAddresses();
            }
            if($form->getValue('osgra')) {
                $e->addAddress("osg-ra@opensciencegrid.org");
            }

            $person_id = $form->getValue('email_from');
            if($person_id == -1) {
                $e->setFrom(config()->email_from);
            } else if($person_id == -2) {
                $e->setFrom(config()->email_from_security);
            } else {
                $model = new Person();
                $person = $model->fetchPerson($person_id);
                $e->setFrom($person->name." <".$person->primary_email.">");
            }

            /*
            //override to address for testing
            if(config()->debug) {
                $e->setTo("schmiecs@indiana.edu");
            } else {
                $e->setTo('goc@opensciencegrid.org');
            }
            */
            $e->setTo('help@opensciencegrid.org');

            $subject = $form->getValue('subject');
            $ticket_id = $form->getValue('ticket');
            if($ticket_id != "") { 
                $subject .= " - GOC Ticket # " . $ticket_id;
            }
            $e->setSubject($subject);

            $body = $form->getValue('body');
            $sig = $form->getValue('sig');
            if($ticket_id != "") { 
                $body .= "\n\nPlease see ticket $ticket_id at:\n".fullbase()."/$ticket_id\n";
            }
            $e->setBody($body."\n\n".$sig);

            try
            {
                if(config()->simulate) {
                    echo "<h2>Simulation</h2>";
                    echo $e->dump();
                    $this->render("none", null, true);
                    if($do_rss) {
                        echo "RSS feed will be added";
                    }
                    if($_REQUEST["sign"] == 1) {
                        echo "Email will be signed";
                    }
                } else {
                    if($_REQUEST["sign"] == 1) {
                        $e->setSign();
                    }
                    $e->send();

                    $this->view->detail .= "<br/><h3>Following Email has been sent</h3><br/>";
                    $this->view->detail .= $e->dump();

                    if($do_rss) {
                        $r = new RSSFeed();
                        $r->insert($subject, $ticket_id, $body);
                        $this->view->detail .= "<br/><h3>RSS Feed has been generated</h3><br/>";
                    }
                    $this->render("processed", null, true);
                }
            } catch(exception $e) {
                $this->sendErrorEmail($e);
		slog($e);
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
        $form = $this->initForm("notify", false);

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

        $e = new Zend_Form_Element_Checkbox('raa');
        $form->addElement($e);
        $e = new Zend_Form_Element_Checkbox('rasponsor');
        $form->addElement($e);
        $e = new Zend_Form_Element_Checkbox('gridadmin');
        $form->addElement($e);
        $e = new Zend_Form_Element_Checkbox('osgra');
        $form->addElement($e);

        $e = new Zend_Form_Element_Select('email_from');
        $e->setLabel("From");
        $e->addMultiOption(-1, config()->email_from);
        $e->addMultiOption(-2, config()->email_from_security);

        $model = new Person();
        $contacts = $model->fetchGOC(4);
        foreach($contacts as $contact) {
            $e->addMultiOption($contact->id, $contact->name." <".$contact->primary_email.">");
        }
        $form->addElement($e);

        $e = new Zend_Form_Element_Text('subject');
        $e->setLabel("Subject");
        $e->setRequired(true);
        $form->addElement($e);

        $e = new Zend_Form_Element_Text('ticket');
        $e->setLabel("Footprint Ticket ID");
        $e->addValidator(new Zend_Validate_Int()); //ture for allowWhiteSpace
        $e->setDescription("* Optional");
        $e->addDecorator("description");
        $e->setRequired(false);
        $form->addElement($e);

        $detail = new Zend_Form_Element_Textarea('body');
        //although the FP's db should allow max size of 16M for MEDIUMTEXT
        //the large text could cause submission error sometime.
        //restricting it to some small amount for now (1M)
        $detail->addValidator(new Zend_Validate_StringLength(1, 1024*1024*1)); 
        $detail->setLabel("Content");
        $detail->setRequired(true);
        $form->addElement($detail);

        $e = new Zend_Form_Element_Textarea('sig');
        $e->setLabel("Email Signature");
        //$e->setValue($this->getGOCSigTemplate());
        $form->addElement($e);

        $e = new Zend_Form_Element_Checkbox('rss');
        $e->setLabel("Publish to RSS");
        $e->setValue(true);
        $form->addElement($e);

        $e = new Zend_Form_Element_Checkbox('sign');
        $e->setLabel("Sign using GOC X509 certificate");
        $e->setValue(true);
        $form->addElement($e);

        $submit = new Zend_Form_Element_Submit('submit_button');
        $submit->setLabel("   Submit   ");
        $form->addElement($submit);

        return $form;
    }

    public function sigAction()
    {
/*
Please submit problems, requests, and questions at:
https://ticket.opensciencegrid.org/goc

Thank You,
*/
        switch($_REQUEST["type"]) {
        case "goc":
            $sig = "OSG Grid Operations Center (GOC)
Email/Phone: help@opensciencegrid.org, 317-278-9699
GOC Homepage: http://www.opensciencegrid.org/ops
RSS Feed: http://osggoc.blogspot.com
";
            break;
        case "software":
            $sig = "OSG Grid Operations Center (GOC) and OSG Software Team
https://www.opensciencegrid.org/
For support: help@opensciencegrid.org or 317-278-9699
";
            break;
        default:
            $sig = "?";
        }

        //$this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(); 
        $this->_response->setHeader('Content-Type', 'text/plain');        
        echo $sig;
    }
} 

