<?

class FinderrorController extends Zend_Controller_Action 
{ 
    public function indexAction() 
    { 
        if(user()->getPersonID() === null) {
            $this->render("error/404", true, "error");
            return;
        }

        $schema_model = new Schema();

        $this->view->error_origvos = array();
        $this->view->error_destvos = array();

        /////////////////////////////////////////////////////////////////////////////////
        //analyze vo errors

        $model = new VO();
        $oim_vos = $model->fetchAll();

        ////////////////////////////////////////////////////////////////
        //originating

        $orig_vos = $schema_model->getoriginatingvos();

        //find FP only
        foreach($orig_vos as $orig_vo) {
            $orig_vo2 = Footprint::parse($orig_vo);
            //find it in the oim_vo
            $found = false;
            foreach($oim_vos as $oim_vo) {
                if($oim_vo->footprints_id == $orig_vo2) {
                    $found = true;
                    $this->view->error_origvos[] = array("", $orig_vo, $oim_vo->footprints_id."(".$oim_vo->short_name.")");
                    break;
                }
            }
            if(!$found) {
                if($orig_vo2 == "other") continue;
                $this->view->error_origvos[] = array("only in fp", $orig_vo2, "");
            }
        } 
        //find oim only
        foreach($oim_vos as $oim_vo) {
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
                $this->view->error_origvos[] = array("only in oim", "", $oim_vo->footprints_id."(".$oim_vo->short_name.")");
            }
        } 

        ////////////////////////////////////////////////////////////////
        //destination
        $dest_vos = $schema_model->getdestinationvos();

        //find FP only
        foreach($dest_vos as $dest_vo) {
            $dest_vo2 = Footprint::parse($dest_vo);
            //find it in the oim_vo
            $found = false;
            foreach($oim_vos as $oim_vo) {
                if($oim_vo->footprints_id == $dest_vo2) {
                    $found = true;
                    $this->view->error_destvos[] = array("", $dest_vo, $oim_vo->footprints_id."(".$oim_vo->short_name.")");
                    break;
                }
            }
            if(!$found) {
                if($dest_vo2 == "other") continue;
                $this->view->error_destvos[] = array("only in fp", $dest_vo2, "");
            }
        } 
        //find oim only
        foreach($oim_vos as $oim_vo) {
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
                $this->view->error_destvos[] = array("only in oim", "", $oim_vo->footprints_id."(".$oim_vo->short_name.")");
            }
        } 

        /////////////////////////////////////////////////////////////////////////////////
        //analyze sc errors
        $this->view->error_sc = array();
        $teams = $schema_model->getteams();
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
                    $this->view->error_sc[] = array("", $fp_sc, $oim_sc->footprints_id);
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

        /////////////////////////////////////////////////////////////////////////////////
        //analyze sc email addresses
        $this->view->error_email = array();

        $model = new SC();
        $oim_scs = $model->fetchAll();
        //var_dump($oim_scs); 

        $emails = $schema_model->getemail();
        //var_dump($emails); 

        $model = new PrimarySCContact;
        foreach($oim_scs as $oim_sc) {
            //pull primary admin
            $admin = $model->fetch($oim_sc->sc_id);
            $op_contact_email = $admin->primary_email;
            
            $found = false;
            foreach($emails as $email) {
                if($email->user == $oim_sc->footprints_id) {
                    $found = true;
                    if($op_contact_email == $email->email) {
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

        $this->render();
    }

    private function voerrors($fp_vos, $oim_vos)
    {
        $fp_only = array();
        $oim_only = array();
        
        var_dump($fp_vos);
        var_dump($oim_vos);
    }
} 
