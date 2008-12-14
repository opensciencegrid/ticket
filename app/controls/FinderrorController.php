<?

class FinderrorController extends Zend_Controller_Action 
{ 
    public function init()
    {
        if(user()->getPersonID() === null) {
            $this->render("error/404", null, true);
            return;
        }

        $this->schema_model = new Schema();
        $model = new VO();
        $this->oim_vos = $model->fetchAll();

        $this->view->error_origvos = array();
        $this->view->error_destvos = array();
    }

    public function indexAction() 
    { 
        $this->analyze_vo_originating();
        $this->analyze_vo_destination();
        $this->analyze_sc();
        $this->analyze_scemail();
        $this->analyze_ticket_assignment();
    }

    public function analyze_vo_originating()
    { 
        $orig_vos = $this->schema_model->getoriginatingvos();

        //find FP only
        foreach($orig_vos as $orig_vo) {
            $orig_vo2 = Footprint::parse($orig_vo);
            //find it in the oim_vo
            $found = false;
            foreach($this->oim_vos as $oim_vo) {
                if($oim_vo->footprints_id == $orig_vo2) {
                    $found = true;
                    $this->view->error_origvos[] = array("", $orig_vo2, $oim_vo->short_name."(".$oim_vo->footprints_id.")");
                    break;
                }
            }
            if(!$found) {
                if($orig_vo2 == "other") continue;
                $this->view->error_origvos[] = array("only in fp", $orig_vo2, "");
            }
        } 
        //find oim only
        foreach($this->oim_vos as $oim_vo) {
            //find it in the oim_vo
            $found = false;
            foreach($orig_vos as $orig_vo) {
                $orig_vo2 = Footprint::parse($orig_vo);
                if($oim_vo->footprints_id == $orig_vo2) {
                    $found = true;
                    break;
                }
            }
            if(!$found) {
                $this->view->error_origvos[] = array("only in oim", "", $oim_vo->short_name."(".$oim_vo->footprints_id.")");
            }
        } 
    }

    public function analyze_vo_destination()
    { 
        $dest_vos = $this->schema_model->getdestinationvos();

        //find FP only
        foreach($dest_vos as $dest_vo) {
            $dest_vo2 = Footprint::parse($dest_vo);
            //find it in the oim_vo
            $found = false;
            foreach($this->oim_vos as $oim_vo) {
                if($oim_vo->footprints_id == $dest_vo2) {
                    $found = true;
                    $this->view->error_destvos[] = array("", $dest_vo2, $oim_vo->short_name."(".$oim_vo->footprints_id.")");
                    break;
                }
            }
            if(!$found) {
                if($dest_vo2 == "other") continue;
                $this->view->error_destvos[] = array("only in fp", $dest_vo2, "");
            }
        } 
        //find oim only
        foreach($this->oim_vos as $oim_vo) {
            //find it in the oim_vo
            $found = false;
            foreach($dest_vos as $dest_vo) {
                $dest_vo2 = Footprint::parse($dest_vo);
                if($oim_vo->footprints_id == $dest_vo2) {
                    $found = true;
                    break;
                }
            }
            if(!$found) {
                $this->view->error_destvos[] = array("only in oim", "", $oim_vo->short_name."(".$oim_vo->footprints_id.")");
            }
        } 
    }

    public function analyze_sc()
    { 
        $this->view->error_sc = array();
        $teams = $this->schema_model->getteams();
        //find suppor centers
        $fp_scs = array();
        foreach($teams as $team) {
            if($team->team == "OSG__bSupport__bCenters") {
                $fp_scs = split(",", $team->members);
                break;
            }
        }
        $model = new SC();
        $oim_scs = $model->fetchAll();
        foreach($fp_scs as $fp_sc) {
            $found = false;
            foreach($oim_scs as $oim_sc) {
                if($oim_sc->footprints_id == $fp_sc) {
                    //found match
                    $this->view->error_sc[] = array("", $fp_sc, $oim_sc->short_name."(".$oim_sc->footprints_id.")");
                    $found = true;
                    break;
                }
            }
            if(!$found) {
                $this->view->error_sc[] = array("only in fp", $fp_sc, "");
            }
        } 
        foreach($oim_scs as $oim_sc) {
            $found = false;
            foreach($fp_scs as $fp_sc) {
                if($oim_sc->footprints_id== $fp_sc) {
                    $found = true;
                    break;
                }
            }
            if(!$found) {
                $this->view->error_sc[] = array("only in oim", "", $oim_sc->footprints_id);
            }
        }
    }
    
    public function analyze_scemail()
    { 
        $this->view->error_email = array();

        $model = new SC();
        $oim_scs = $model->fetchAll();
        $emails = $this->schema_model->getemail();
        $model = new PrimarySCContact;
        foreach($oim_scs as $oim_sc) {
            //pull primary admin
            $admin = $model->fetch($oim_sc->sc_id);
            $op_contact_email = $admin->primary_email;
            
            $found = false;
            foreach($emails as $email) {
                if($email->user == $oim_sc->footprints_id) {
                    $found = true;
                    if(strcasecmp($op_contact_email, $email->email) == 0) {
                        //found email match
                        $this->view->error_email[] = array($oim_sc->footprints_id, $op_contact_email, $email->user, $email->email, "");
                    } else {
                        $this->view->error_email[] = array($oim_sc->footprints_id, $op_contact_email, $email->user, $email->email, "* Email address doesn't match");
                    }
                }
            }
            if(!$found) {
                $this->view->error_email[] = array($oim_sc->footprints_id, $op_contact_email, "", "", "* No such user ID in FP.");
            }
        }
    }
    public function analyze_ticket_assignment()
    {
        $teams = $this->schema_model->getteams();
        $members = array();
        foreach($teams as $team_entry) {
            $team = Footprint::parse($team_entry->team);
            if($team == "OSG GOC Support Team" || $team == "OSG Operations Infrastructure") {
                $members = array_merge($members, split(",", $team_entry->members));
            }
        }

        //pull all open tickets
        $model = new Tickets();
        $tickets = $model->getopen();

        //find tickets with no $members assignees
        $this->view->na_assignments = array();
        foreach($tickets as $ticket) {
            $found = false;
            foreach(split(" ",$ticket->mrassignees) as $ass) {
                if(in_array($ass, $members)) {
                    $found = true;
                    break;
                }
            }
            if(!$found) {
                $id = $ticket->mrid;
                $this->view->na_assignments[] = $ticket;
            }
        }
    }
} 
