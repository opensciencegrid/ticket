<?php

//Used to let user download attachment.
class AttachmentController extends BaseController
{ 
    public function init() {
        //$this->attachment_dir = "/usr/local/attachments/project_$pid/ticket_$id/"; //must end with /
    }

    //proxy attachment download
    //GOC-TX still uses direct apache download as of 2013 (access controlled by Apache IP)
    public function viewAction() { 
        $id = (int)$_REQUEST["id"];

        $dirty_filename = $_REQUEST["file"];
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
        $path = $this->getpath($id, $filename);
        if(file_exists($path)) {
            $mime = mime_content_type($path);
            header("Content-type: $mime");
            header("Content-Disposition: filename=$filename");
            readfile($path);
            $this->render("none", null, true);
        } else {
            $this->render("error/404", null, true);
        }
    }

    public function handlerAction() {
        $id = (int)$_REQUEST["id"];

        //don't use getpath since it checks for directory existance
        //$path = $this->getpath($id, "");
        $path = config()->attachment_dir."/ticket_$id/";

        require_once("lib/UploadHandler.php");
        //thumbnail generation is disabled in UploadHandler
        $handler = new UploadHandler(array(
            "script_url"=> "attachment/handler?id=$id",
            "upload_dir"=> $path,
            "upload_url"=> "attachment/view?id=$id&file=",
            "image_versions"=>array("thumbnail"=>
                array("upload_url"=> "attachment/thumb?id=$id&file=")
            )
        ), true);
        $this->render("none", null, true);
    }

    //generate thumbnail from attachment - no much access control here -- since it's just for thumbnail images
    public function thumbAction() {
        $id = (int)$_REQUEST["id"];
        $dirty_file = $_REQUEST["file"];
        $file = basename($dirty_file);//not sure if this safe enough
        $path = $this->getpath($id, $file);

        require_once("app/thumbnail.php");
        $tg = new thumbnailGenerator;
        if(!$tg->generate($path, 100, 100)) {
            header("Content-Type: image/png");
            echo file_get_contents("images/unknown.png");
            slog("output default icon");
        }
        $this->render("none", null, true);
    }

    public function getpath($ticket_id, $attachment_name) {
        $path = config()->attachment_dir."/ticket_$ticket_id/$attachment_name";
        if((!is_file($path) && !is_dir($path)) || $path[0] == ".") return null;
        return $path;
    }

    public function listAction() {
        $ticket_id = (int)$_REQUEST["id"];
        $datas = array();
        $path = config()->attachment_dir."/ticket_$ticket_id";
        if(is_dir($path) && $dh = opendir($path)) {
            while (($name = readdir($dh)) !== false) {
                if($name[0] == ".") continue;

                $fileclass = new stdClass();
                $fileclass->id = $name; //TODO - fow now, use file name as id
                $fileclass->name = $name; 
                $fileclass->size = filesize($path."/".$name);
                $fileclass->thumbnail_url = fullbase()."/attachment/thumb?id=$ticket_id&file=".urlencode($name);
                $fileclass->delete_url = fullbase()."/attachment/handler?id=$ticket_id&file=".urlencode($name);
                $fileclass->url = fullbase()."/attachment/view?id=$ticket_id&file=$name";
                $fileclass->delete_type = 'DELETE';

                $datas[] = $fileclass;
            }
            closedir($dh);

            $datas;
            echo json_encode($datas);
        }
        $this->render("none", null, true);
    }

} 
