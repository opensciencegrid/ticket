<?php

class AttachmentController extends BaseController
{ 
    public function init() {
    }

    public function indexAction() { 
        $id = (int)$_REQUEST["id"];
        $dirty_filename = $_REQUEST["filename"];
        $filename = str_replace("/", "", $dirty_filename);//TODO - need more validation?

        //can user access this ticket?
        $model = new Tickets();
        $detail = $model->getDetail($id);
        if($detail === "") {
            $this->render("error/404", null, true);
            return;
        }
        if($detail->Ticket__uType == "Security_Notification") {
            if(user()->isGuest()) {
            $this->render("error/access", null, true);
                return;
            }
        } else if($detail->Ticket__uType == "Security") {
            if(!user()->allows("view_security_incident_ticket")) {
                $this->render("error/access", null, true);
                return;
            }
        }

        //is there such attachment?
        $pid = config()->project_id;
        $path = "/usr/local/attachments/project_$pid/ticket_$id/$filename";
        if(file_exists($path)) {
            $mime = mime_content_type($path);
            header("Content-type: $mime");
            header("Content-Disposition: attachment; filename=$filename");
            readfile($path);
            $this->render("none", null, true);
        } else {
            $this->render("error/404", null, true);
        }

    }
} 
