<?php

//obsoleted by AttachmentController (but still used by goc-tx)
class Attachments {

    //still used by goc-tx through ViewerController::uploadattachmentAction
    //input - ticket id to load the attachments to
    //output - array containing information about the files uploaded
    public function upload($ticket_id) {
        $adapter = new Zend_File_Transfer_Adapter_Http();
        $ticket_dir = config()->attachment_dir."/ticket_$ticket_id";

        if (!file_exists($ticket_dir)) mkdir($ticket_dir);

        $adapter->setDestination($ticket_dir);
        //$adapter->addValidator('Extension', false, 'jpeg,jpg,png,gif,txt,log,pdf,sh'); //TODO - how should I configure this

        $files = $adapter->getFileInfo();
        $datas = array();
        foreach ($files as $file => $info) {
            $fileclass = new stdClass();

            $name = basename($adapter->getFileName($file));

            // file uploaded & is valid
            if (!$adapter->isUploaded($file)) continue; 
            if (!$adapter->isValid($file)) {
                $fileclass->error = "Unallowed file type";
                $datas[] = $fileclass;
                continue;
            }

            // receive the files into the user directory
            $adapter->receive($file); // this has to be on top
        
            //rename to clean name
            $clean_name = str_replace(array("#", "&"),"", $name);
            rename($adapter->getfileName($file), $ticket_dir."/".$clean_name);

            $fileclass->id = $name; //TODO - fow now, use file name as id
            $fileclass->name = $name;
            $fileclass->size = (int)$info["size"];
            $fileclass->type = $info["type"];
            $fileclass->thumbnail_url = fullbase()."/attachment/thumb?id=$ticket_id&file=".urlencode($clean_name);
            $fileclass->delete_url = fullbase()."/attachment/handler?id=$ticket_id&file=".urlencode($clean_name);
            //$fileclass->url = fullbase()."/attachments/project_".config()->project_id."/ticket_$ticket_id/$clean_name";
            $fileclass->url = fullbase()."/attachment/view?id=$ticket_id&file=$clean_name";
            $fileclass->delete_type = 'DELETE';
            $fileclass->error = $info["error"];

            $datas[] = $fileclass;
        }
        return $datas;
    }

    /*
    //returns physical path to the attachment storage
    public function getpath($ticket_id, $attachment_id) {
        $path = config()->attachment_dir."/ticket_$ticket_id/$attachment_id";
        if(!is_file($path) || $path[0] == ".") return null;
        return $path;
    }

    //remove the attachment
    public function remove($ticket_id, $attachment_id) {
        $path = $this->getpath($ticket_id, $attachment_id);
        $success = unlink($path);
        return $success;
    }
    */

    //still used by viewer/loadattachments which is then used by goc-tx
    public function listattachments($ticket_id) {
        $datas = array();
        $path = config()->attachment_dir."/ticket_$ticket_id";
        if(is_dir($path) && $dh = opendir($path)) {
            while (($name = readdir($dh)) !== false) {
                if($name[0] == ".") continue;

                $fileclass = new stdClass();

                // we stripped out the image thumbnail for our purpose, primarily for security reasons
                // you could add it back in here.
                $fileclass->id = $name; //TODO - fow now, use file name as id
                $fileclass->name = $name;
                $fileclass->size = filesize($path."/".$name);
                $fileclass->type = mime_content_type($path."/".$name);//php5.3 --finfo_file($finfo, $path.$name);
                $fileclass->thumbnail_url = fullbase()."/viewer/thumbnail?id=$ticket_id&attachment=".urlencode($name);
                $fileclass->delete_url = fullbase()."/viewer/deleteattachment?id=$ticket_id&attachment=".urlencode($name);
                //$fileclass->url = fullbase()."/attachments/project_".config()->project_id."/ticket_$ticket_id/$name";
                $fileclass->url = fullbase()."/attachment?id=$ticket_id&filename=$name";
                $fileclass->delete_type = 'DELETE';
                //$fileclass->error = 'null';

                $datas[] = $fileclass;
            }
            closedir($dh);

            return $datas;
        }
        return array();
    }
}
