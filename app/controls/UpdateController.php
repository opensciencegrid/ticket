<?

//this class handles various ajax request for ticket update
class UpdateController extends Zend_Controller_Action 
{ 
    //TODO - is this really used still - instead of ticke/update?
    public function statusAction()
    {
        //only goc users are allowed
        if(!user()->allows("update")) {
            $this->render("error/access", null, true);
            return;
        }
        $ticket_id = (int)$_REQUEST["id"];
        $dirty_status = $_REQUEST["status"];

        //validate status
        $good_status = Footprint::GetStatusList();
        if(!in_array($dirty_status, $good_status)) {
            throw new exception("Unknown status");
        }
        $status = $dirty_status;

        //update status
        $footprint = new Footprint;
        $footprint->setSubmitter(user()->person_fullname);
        $footprint->setSubmitterName(user()->getPersonName());
        $footprint->setID($ticket_id);
        $footprint->setStatus($status);
        $footprint->addDescription("Updating ticket status to $status");
        if(config()->simulate) {
            slog("This is a simulation - not status update actually submitted");
        } else {
            $mrid = $footprint->submit();
            if($mrid == $ticket_id) {
                slog("Status Updated successfully to $status");
            } else {
                slog("Update Failed");
            }
        }
        $this->render("none", null, true);
    }
} 
