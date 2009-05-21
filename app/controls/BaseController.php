<?

class BaseController extends Zend_Controller_Action
{ 
    //send email & sms
    protected function sendErrorEmail($e)
    {
        //construct message body
        $mail_body = "GOC Ticket Form has received a ticket, but the submission to Footprint has failed. Please fix the issue, and resubmit the issue on behalf of the user ASAP.\n\n";
        $mail_body .= "[Exception Message]\n";
        $mail_body .= $e->getMessage()."\n";

        $mail_body .= "[Stack Trace]\n";
        $mail_body .= $e->getTraceAsString()."\n\n";

        $mail_body .= "\n[User has submitted following]\n";
        $mail_body .= print_r($_REQUEST, true);

        if(config()->elog_email) {
            $Name = config()->app_name;
            $email = "hayashis@indiana.edu"; //senders e-mail adress (needs to be valid GOC user?)
            $recipient = config()->elog_email_address;
            $subject = "[ticket_form] Submission Failed";
            $header = "From: ".config()->email_from."\r\n";

            //now, send the email
            mail($recipient, $subject, $mail_body, $header);

            //also send SMS
            $subject = "GOC Ticket submission failure";
            $body = "GOC Ticket form submission error has occured.";
            sendSMS(config()->error_sms_to, $subject, $body);
        }
        elog($mail_body);
    }
    
    protected function getCaptchaCode()
    {
        $session = new Zend_Session_Namespace('captcha');

        //if we have set captcha in the session for this request - use it, else generate new one
        if (isset($session->registerCaptcha))
        {
            $captchaCode = $session->registerCaptcha;
            dlog("using captchacode: ".$captchaCode);
        }
        else
        {
            $md5Hash = md5($_SERVER['REQUEST_TIME']);
            $captchaCode = substr($md5Hash, rand(0, 25), 5);
            $session->registerCaptcha = $captchaCode ;
            dlog("generated captchacode: ".$captchaCode);
        }
        return $captchaCode;
    }

    protected function initForm($page, $has_yourinfo = true)
    {  
        $this->has_yourinfo = $has_yourinfo;

        //init form
        $form = new Zend_Form;
        $form->setAction(base()."/$page/submit");
        $form->setAttrib("id", $page."_form");
        $form->setMethod("post");
        $form->setDecorators(array(
            array('ViewScript', 
                array('viewScript' => "$page/form.phtml"),
                array('class' => 'form element')
        )));

        if($has_yourinfo) {
            $name = new Zend_Form_Element_Text('name');
            $name->setLabel("Full Name");
            $name->addValidator(new Zend_Validate_Alpha(true)); //ture for allowWhiteSpace
            $name->setRequired(true);
            $name->setValue(user()->getPersonName());
            $form->addElement($name);

            $email = new Zend_Form_Element_Text('email');
            $email->setLabel("Email Address");
            $email->addValidator(new Zend_Validate_EmailAddress());
            $email->setRequired(true);
            $email->setValue(user()->getPersonEmail());
            $form->addElement($email);

            $phone = new Zend_Form_Element_Text('phone');
            $phone->setLabel("Phone Number");
            $phone->addValidator('regex', false, validator::$phone);
            $phone->setRequired(true);
            //$phone->setDescription("(Format: 123-123-1234)");
            $phone->setValue(user()->getPersonPhone());
            $form->addElement($phone);

            $vo_model = new VO;
            $vos = $vo_model->fetchAll();
            $vo = new Zend_Form_Element_Select('vo_id');
            $vo->setLabel("Virtual Organization that this contact belongs");
            $vo->setRequired(true);
            $vo->addMultiOption(null, "(Please Select)");
            $vo->addMultiOption(-1, "(I don't know)"); //2 - CSC
            foreach($vos as $v) {
                $vo->addMultiOption($v->id, $v->name);
            }
            if(!user()->allows("ticket_admin")) {
            //if(in_array(role::$goc_admin, user()->roles)) {
                $vo->setValue(25); //MIS
            }
            $form->addElement($vo);
        }

        return $form;
    }

    protected function initSubmit($form)
    {
        //prepare footprint ticket
        $footprint = new Footprint;

        if($this->has_yourinfo) {
            $footprint->setName($form->getValue('name'));
            $footprint->setOfficePhone($form->getValue('phone'));
            $footprint->setEmail($form->getValue('email'));

            $void = $form->getValue('vo_id');
            if($void == -1) {
                $footprint->addMeta("Submitter doesn't know his/her SC.\n");
            } else {
                $vo_model = new VO();
                $info = $vo_model->get($void);
                if($info->footprints_id === null) {
                    $footprint->addMeta("Submitter's VO is ".$info->name. " but its footprints_id is not set in OIM. Please set it.");
                } else {
                    $footprint->setOriginatingVO($info->footprints_id);
                }
            }
        }

        return $footprint;
    }

    protected function dumprecord($rec)
    {
        $out = "";

        $vars = get_object_vars($rec);
        foreach($vars as $key=>$value) {
            $out .= "[$key]\n\t$value\n";
        }

        return $out;
    }
}
