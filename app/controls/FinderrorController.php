<?php

class FinderrorController extends Zend_Controller_Action 
{ 
    public function init()
    {
        if(!islocal()) {
            user()->check("admin");
        }

        $this->view->page_title = "Footprints Error";
        $this->view->menu_selected = "user";
        $this->view->submenu_selected = "finderror";

        $this->schema_model = new Schema();
        $model = new VO();
        $this->oim_vos = $model->fetchAll();
        $this->berror = false;
        $this->view->error_sc = array();
        $this->view->error_email = array();
    }

    public function emailerrorAction()
    {
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
        $this->analyze_sc();
        $this->analyze_scemail();
        $this->analyze_ticket_assignment();
        $this->analyze_resource_sc_link();
    }

    public function analyze_sc()
    { 
        $teams = $this->schema_model->getteams();
        //find suppor centers
        $fp_scs = array();
        foreach($teams as $team) {
             if($team->team == "OSG__bSupport__bCenters") {
                $fp_scs = explode(",", $team->members);
                break;
            }
        }

        //find TX team
        foreach($teams as $team) {
             if($team->team == "Ticket__bExchange") {
                $fp_scs = array_merge($fp_scs, explode(",", $team->members));
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
                $members = array_merge($members, explode(",", $team_entry->members));
            }
        }

        //pull all open tickets
        $model = new Tickets();
        $tickets = $model->getopen();

        //find tickets with no $members assignees
        $this->view->na_assignments = array();
        foreach($tickets as $ticket) {
            $found = false;
            foreach(explode(" ",$ticket->mrassignees) as $ass) {
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
                if(empty($sc)) {
                    $note .= "Support Center specified has been disabled. Please set active SC instead.";
                    $this->berror = true;
                } else {
                    $sc_name = $sc->footprints_id;
                }
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
