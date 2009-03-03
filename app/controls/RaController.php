<?

class RaController extends BaseController
{ 
    public function init()
    {
        $this->view->submenu_selected = "open";
        //load sponsor list
        $model = new Sponsor();
        $this->sponsors = $model->fetchall();
        $this->view->sponsors = $this->sponsors;
    }

    public function indexAction() 
    { 
        //only goc users are allowed
        if(!in_array(role::$goc_admin, user()->roles)) {
            $this->render("error/access", null, true);
            return;
        }

        $this->view->form = $this->getForm();
        $this->render();
    }

    public function submitAction()
    {
        //only goc users are allowed
        if(!in_array(role::$goc_admin, user()->roles)) {
            $this->render("error/access", null, true);
            return;
        }

        $form = $this->getForm();
        if($form->isValid($_POST)) {
            //validate sponsor (just in case)
            $sp_name = $form->getValue('req_sponsor');
            $sp_email = null;
            foreach($this->sponsors as $sponsor) {
                $name = trim($sponsor->first_name. " ".$sponsor->last_name);
                if($name == $sp_name) {
                    $sp_email = $sponsor->primary_email;
                    break;
                }
            }
            if($sp_email === null) {
                dlog("wierd... (maybe user is tinkering with the form?)");
                return;
            }

            //generate ra email content
            $ra = new RaEmail();
            $ra->setFromName($form->getValue('firstname')." ".$form->getValue('lastname'));
            $ra->setFromEmail($form->getValue('email'));
            $ra->setFromPhone($form->getValue('phone'));
            $ra->setType($form->getValue('req_type'));
            $ra->setName($form->getValue('req_name'));
            $ra->setEmail($form->getvalue('req_email'));
            $ra->setPhone($form->getvalue('req_phone'));
            $ra->setVO($form->getvalue('vo'));
            $ra->setDN($form->getvalue('req_dn'));
            $ra->setSponsorName($sp_name);
            $ra->setSponsorEmail($sp_email);
            $ra->setID($form->getvalue('req_id'));

            $email_content = $ra->getBody();
            $email_header = $ra->getHeader();
            $email_recipient = $ra->getRecipient();
            $email_subject = $ra->getSubject();

            //generate footprint content
            $footprint = $this->initSubmit($form);
            $footprint->setTitle("FYI: Certificate Request Email for ".$form->getValue('req_name'));
            $description = "Following Email has been sent.\n";
            $description .= "[Recipient]\n".$email_recipient."\n";
            $description .= "[Header]\n".$email_header."\n";
            $description .= "[Subject]\n".$email_subject."\n";
            $description .= "[Content]\n".$email_content."\n";
            $footprint->addDescription($description);

            try 
            {
                $this->view->mrid = $footprint->submit();
                if(!config()->simulate) {
                    //Send email..
                    mail($email_recipient, $email_subject, $email_content, $email_header);
                }
                $this->render("success", null, true);

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
        $form = $this->initForm("ra");

        $e = new Zend_Form_Element_Select('req_type');
        $e->setLabel("Request Type");
        $e->setRequired(true);
        $e->addMultiOption("personal", "Personal Certificate");
        $e->addMultiOption("host", "Host/Service Certificate");
        $form->addElement($e);

        $e= new Zend_Form_Element_Text('req_id');
        $e->setLabel("Request ID");
        $e->addValidator(new Zend_Validate_Int()); //ture for allowWhiteSpace
        $e->setRequired(true);
        $form->addElement($e);

        $e = new Zend_Form_Element_Select('vo');
        $e->setLabel("VO");
        $e->setRequired(true);
        $e->addMultiOption("OSG", "OSG");
        $e->addMultiOption("MIS", "MIS");
        $e->addMultiOption("OSGEDU", "OSGEDU");
        $form->addElement($e);

        $name = new Zend_Form_Element_Text('req_name');
        $name->setLabel("Requestor Name");
        $name->addValidator(new Zend_Validate_Alpha(true)); //ture for allowWhiteSpace
        $name->setRequired(true);
        $form->addElement($name);

        $email = new Zend_Form_Element_Text('req_email');
        $email->setLabel("Requestor Email Address");
        $email->addValidator(new Zend_Validate_EmailAddress());
        $email->setRequired(true);
        $form->addElement($email);

        $phone = new Zend_Form_Element_Text('req_phone');
        $phone->setLabel("Requestor Phone Number");
        $phone->addValidator('regex', false, validator::$phone);
        $phone->setRequired(true);
        $form->addElement($phone);

        $e = new Zend_Form_Element_Text('req_dn');
        $e->setLabel("Requestor DN");
        $e->setRequired(true);
        $form->addElement($e);

        $e = new Zend_Form_Element_Text('req_sponsor');
        $e->setLabel("Requestor Sponsor");
        $e->setRequired(true);
        $form->addElement($e);

        $submit = new Zend_Form_Element_Submit('submit_button');
        $submit->setLabel("Send Email");
        $form->addElement($submit);

        return $form;
    }
} 

class RaEmail
{
    function __construct()
    {
    }

    public function setType($val) { $this->type = $val; }
    public function setName($val) { $this->name = $val; }
    public function setEmail($val) { $this->email = $val; }
    public function setPhone($val) { $this->phone = $val; }
    public function setVO($val) { $this->vo = $val; }
    public function setDN($val) { $this->dn = $val; }
    public function setSponsorName($val) { $this->sponsor_name = $val; }
    public function setSponsorEmail($val) { $this->sponsor_email = $val; }
    public function setFromName($val) { $this->from_name = $val; }
    public function setFromEmail($val) { $this->from_email = $val; }
    public function setFromPhone($val) { $this->from_phone = $val; }
    public function setID($val) { $this->id = $val; }

    public function getRecipient()
    {
        return $this->sponsor_name. " <".$this->sponsor_email.">";
    }
    public function getHeader()
    {
        $header = "";
        $header .= "From: ". $this->from_name. " <" . $this->from_email . ">\r\n";
        $header .= "Cc: ". $this->name. " <".$this->email.">, ";
        $header .= "Robert E Quick <rquick@iupui.edu>, Elizabeth Chism <echism@iupui.edu>, Kyle Gross <kagross@indiana.edu>";
        return $header;
    }

    public function getSubject()
    {
        return "[OSG RA: OSG/$this->vo] Certificate Request to the DOEGrids CA ".$this->id;
    }

    public function getBody()
    {
        $template = $this->getTemplate();
        $template = str_replace("__SPONSOR_NAME__", $this->sponsor_name, $template);

        $template = str_replace("__NAME__", $this->name, $template);
        $template = str_replace("__EMAIL__", $this->email, $template);
        $template = str_replace("__PHONE__", $this->phone, $template);
        $template = str_replace("__DN__", $this->dn, $template);

        $template = str_replace("__FROM_NAME__", $this->from_name, $template);
        $template = str_replace("__FROM_EMAIL__", $this->from_email, $template);
        $template = str_replace("__FROM_PHONE__", $this->from_phone, $template);

        if($this->type == "host") {
            $template = str_replace("__TYPE__", "host/service certificate", $template);
        } else {
            $template = str_replace("__TYPE__", "personal certificate", $template);
        }

        return $template;
    }

    private function getTemplate()
    {

        $instruction = "- You need to have a secure communication with the requestor and
verify that he or she has indeed requested a certificate from the
DOEGrids CA with the subject name as shown above.

- You and I need to have a secure communication where you convey to me
that you and the requestor have communicated securely and that the
request has been verified.";
        $note = "Please verify the authenticity of the request and then either send me
a digitally-signed email or phone me at __FROM_PHONE__ to let me know
you have verified the request.";

        $template = "Hello __SPONSOR_NAME__,

The following person has requested a __TYPE__ from the
DOEGrids Certificate Authority and has listed you as the sponsor:

Name: __NAME__
Email: __EMAIL__
Phone: __PHONE__

The subject name of the certificate request is

__DN__

In order for me to sign this certificate request and grant the
certificate the following chain of events needs to take place:

$instruction

Please note the following acceptable forms of secure communication:

- a face-to-face meeting
- a telephone call if you have previously met the person
  face-to-face and are capable of recognizing his or her voice
- an email digitally signed using a DOEGrids certificate

Note that general email is not acceptable as a form of secure
communication.

$note

__FROM_NAME__ <__FROM_EMAIL__> for the OSG Operations Workgroup
";
        return $template;
    }
}
