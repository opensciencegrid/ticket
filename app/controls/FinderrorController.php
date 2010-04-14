<?

class FinderrorController extends Zend_Controller_Action 
{ 
    public function init()
    {
        $this->view->submenu_selected = "admin";

        $this->schema_model = new Schema();
        $model = new VO();
        $this->oim_vos = $model->fetchAll();

        $this->berror = false;

        $this->view->error_origvos = array();
        $this->view->error_destvos = array();
        $this->view->error_sc = array();
        $this->view->error_email = array();
    }

    public function emailerrorAction()
    {
        if(!user()->allows("admin") and !islocal()) {
            $this->render("error/access", null, true);
            return;
        }
        if(islocal()) {
            $this->indexAction();
            if($this->berror) {
                $msg = "Dear GOC,\n\nFinderror page is reporting some issues. Please fix.\n\n".fullbase()."/finderror";
                $emailto = config()->finderror_address;
                mail($emailto, "[gocticket] A friendly reminder for footprint issues", $msg, "From: ".config()->email_from);
                echo "Detected error - Sent error email to $emailto";
            } else {
                echo "No error detected - not sending error email";
            }
        } else {
            echo "localhost only";
        }
        $this->render("none", null, true);
    }

    public function indexAction() 
    { 
        if(!user()->allows("admin") and !islocal()) {
            $this->render("error/access", null, true);
            return;
        }
        $this->analyze_vo_originating();
        $this->analyze_vo_destination();
        $this->analyze_sc();
        $this->analyze_scemail();
        $this->analyze_ticket_assignment();
        $this->analyze_resource_sc_link();
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
                    $this->view->error_origvos[] = array("", $orig_vo2, $oim_vo->name."(".$oim_vo->footprints_id.")");
                    break;
                }
            }
            if(!$found) {
                if($orig_vo2 == "other") continue;
                $this->view->error_origvos[] = array("only in fp", $orig_vo2, "");
                $this->berror = true;
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
                $this->view->error_origvos[] = array("only in oim", "", $oim_vo->name."(".$oim_vo->footprints_id.")");
                $this->berror = true;
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
                    $this->view->error_destvos[] = array("", $dest_vo2, $oim_vo->name."(".$oim_vo->footprints_id.")");
                    break;
                }
            }
            if(!$found) {
                if($dest_vo2 == "other") continue;
                $this->view->error_destvos[] = array("only in fp", $dest_vo2, "");
                $this->berror = true;
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
                $this->view->error_destvos[] = array("only in oim", "", $oim_vo->name."(".$oim_vo->footprints_id.")");
                $this->berror = true;
            }
        } 
    }

    public function analyze_sc()
    { 
        $teams = $this->schema_model->getteams();
        //find suppor centers
        $fp_scs = array();
        foreach($teams as $team) {
             if($team->team == "OSG__bSupport__bCenters" || $team->team == "Ticket__bExchange") {
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
                    $this->view->error_sc[] = array("", $fp_sc, $oim_sc->name."(".$oim_sc->footprints_id.")");
                    $found = true;
                    break;
                }
            }
            if(!$found) {
                $this->view->error_sc[] = array("only in fp", $fp_sc, "");
                $this->berror = true;
            }
        } 
        foreach($oim_scs as $oim_sc) {
            $found = false;
            foreach($fp_scs as $fp_sc) {
                if($oim_sc->footprints_id == $fp_sc) {
                    $found = true;
                    break;
                }
            }
            if(!$found) {
                $this->view->error_sc[] = array("only in oim", "", $oim_sc->name."(".$oim_sc->footprints_id.")");
                $this->berror = true;
            }
        }
    }
    
    public function analyze_scemail()
    { 
        $model = new SC();
        $oim_scs = $model->fetchAll();
        $fpemails = $this->schema_model->getemail();
        $model = new PrimarySCContact;
        foreach($oim_scs as $oim_sc) {
            //pull primary admin
            $admin = $model->fetch($oim_sc->id);
            $op_contact_email = @$admin->primary_email;
            
            $user = $oim_sc->footprints_id;
            if(array_key_exists($user, $fpemails)) {
                $fpemail = $fpemails[$user];
                if(strcasecmp($op_contact_email, $fpemail) == 0) {
                    //found email match
                    $this->view->error_email[] = array($oim_sc->name, $op_contact_email, $user, $fpemail, "");
                } else {
                    $this->view->error_email[] = array($oim_sc->name, $op_contact_email, $user, $fpemail, "* Email address doesn't match");
                    $this->berror = true;
                }
            } else {
                $this->view->error_email[] = array($oim_sc->name, $op_contact_email, "", "", "* No such user ID in FP.");
                $this->berror = true;
            }
        }
    }
    public function analyze_ticket_assignment()
    {
        $teams = $this->schema_model->getteams();
        $this->view->teams = $teams;
        $members = array();
        foreach($teams as $team_entry) {
            $team = Footprint::parse($team_entry->team);
            if( $team == "OSG GOC Support Team" || 
                $team == "OSG Operations Infrastructure" || 
                $team == "OSG GOC Management" ||
                $team == "OSG Security Coordinators" ||
                $team == "OSG Storage Team") {
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
                $this->berror = true;
            }
        }
    }

    public function analyze_resource_sc_link()
    {
        //all active resources should have coresponding SC
        $model = new Resource();
        $resources = $model->fetchAll();

        $sc_model = new SC();
        $rs_model = new ResourceSite();

        $this->view->resource_sclink = array();
        foreach($resources as $r) {
            $note = "";
            $sc_id = $rs_model->fetchSCID($r->id);
            $name = $model->fetchName($r->id);
            $sc_name = null;
            if($sc_id !== false) {
                $sc = $sc_model->get($sc_id); 
                $sc_name = $sc->footprints_id;
            } else {
                $note .= "Failed to find this resource in rsvextra.View_resourceSiteScPub";
                $this->berror = true;
            }
            $this->view->resource_sclink[] = array(
                "resource_name"=>$name,
                "resource_id"=>$r->id,
                "sc_id"=>$sc_id,
                "sc_name"=>$sc_name,
                "note"=>$note
            );
        }
    }
} 
