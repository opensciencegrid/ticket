<?php

//zend viewer http://devzone.zend.com/article/3412
class Zend_View_Helper_Alerts extends Zend_View_Helper_Abstract {

    //flush all messages pending to be displayed
    public function alerts()
    {
        $out = "";

        if(config()->banner) {
            message("success", config()->banner, true);
        }
        if(config()->simulate) {
            message("warning", "Simulation Mode - No email / ticket will be actually created");
        }
        if(config()->role_prefix == "itbticket_") {
            message("warning", "This is the ITB ticket system used by the OSG VTB/ITB teams. These tickets are NOT be handled by the GOC. If you are reporting a production issue please <a href=\"https://ticket.opensciencegrid.org/goc\">go here</a>", true);
        }


        $message = new Zend_Session_Namespace('message');
        if(isset($message->alerts)) {
            foreach($message->alerts as $alert) {
                $type = $alert["type"];

                $out .= "<div class=\"alert alert-$type\">";
                $out .= "<a class=\"close\" href=\"#\" data-dismiss=\"alert\">&times;</a>";
                $out .= $alert["html"]."</div>";
            }
        }
        $message->alerts = array();
        return $out;
    }
}

