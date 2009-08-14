<?php

class SecurityEmail
{
    public function __construct()
    {
        $this->h_email = array();
        $this->bcc = "";
        $this->pa_model = new PrimaryAddress();
        $this->sign = false;
    }

    public function setFrom($val) { $this->from = $val; }
    public function setTo($val) { $this->to = $val; }
    public function setSubject($val) { $this->subject = $val; }
    public function setBody($val) { $this->body = $val; }
    public function setSign() { $this->sign = true; }

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
        $out = "";
        $out .= "<hr>To: ".$this->to."\n\n";
        $out .= "<hr>From: ".htmlentities($this->from)."\n\n";
        $out .= "<hr>Subject: ".$this->subject."\n\n";
        $out .= "<hr>BCC: ".$this->bcc."\n\n";
        $out .= "<hr>Body:<pre>".$this->body."</pre>\n\n";
        if($this->sign) {
            $out .= "<hr>Signed\n\n";
        } else {
            $out .= "<hr>Unsigned\n\n";
        }
        $out .= "<hr>\n\n";
        return $out;
    }

    public function send()
    {
        if($this->sign) {
            //TODO - need to put bcc in BCC section instead of To
            signedmail($this->to, $this->from, $this->subject, $this->body, "Bcc: ".$this->bcc."\r\n");
            slog("[submit] Notification email(signed) sent with following content --------------------------");
        } else {
            $header = "From: $this->from\r\n";
            $header .= "Bcc: $this->bcc\r\n";
            if(!mail($this->to, $this->subject, $this->body, $header)) {
                elog("Failed to send email");
                throw new exception("Failed to send unsigned email");
            }
            slog("[submit] Notification email(unsigned) sent with following content --------------------------");
        }
        slog(print_r($this, true));
    }
}
