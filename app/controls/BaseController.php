<?

class BaseController extends Zend_Controller_Action 
{ 
    protected function sendErrorEmail($e)
    {
        $Name = "GOC Footprint Ticket Form"; //senders name
        $email = "hayashis@indiana.edu"; //senders e-mail adress
        $recipient = "IU-GOC-L@LISTSERV.INDIANA.EDU";
        $recipient = "hayashis@indiana.edu";
        $mail_body = "Dear Goc,\n\nGOC Ticket Form has received a ticket, but the submittion to Footprint has failed. Please fix the issue, and resubmit the issue on behalf of the user ASAP.\n\n";
        $mail_body .= "[Footprint says]\n";
        $mail_body .= print_r($e, true);

        $mail_body .= "\n[User has submitted following]\n";
        $mail_body .= print_r($_REQUEST, true);
        $subject = "[ticket_form]Submission Failed";
        $header = "From: ". $Name . " <" . $email . ">\r\n";
        mail($recipient, $subject, $mail_body, $header); //mail command :) 
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
}
