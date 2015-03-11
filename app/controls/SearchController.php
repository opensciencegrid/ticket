<?

class SearchController extends Zend_Controller_Action 
{ 
    public function init()
    {
        $this->view->submenu_selected = "search";
    }    

    public function indexAction()
    {
        $facet_fields = array(
            "status"=>array("name"=>"Status", "type"=>"string"),
            "ticket_type"=>array("name"=>"Ticket Type", "type"=>"string"),
            "priority"=>array("name"=>"Priority"),
            "assignees"=>array("name"=>"Assignees", "type"=>"string"),
            "SUPPORTING_SC_ID"=>array("name"=>"Support Center"),
            "ASSOCIATED_VO_ID"=>array("name"=>"Virtual Organization"),
            "ASSOCIATED_RG_ID"=>array("name"=>"Resource Group"),
            "SUBMITTER_NAME"=>array("name"=>"Submitter", "type"=>"string")
        );
        $this->view->facet_fields = $facet_fields;

        $url = config()->solr_host."/select?wt=json";
        $q = $this->clean($_REQUEST["q"]);
        if($_REQUEST["q"] != "") {
            $url .= "&q=".urlencode("{!lucene q.op=AND}".$q);//. " -ticket_type:(Host Certificate Request)");
        } else {
            //all tickets
            $this->view->menu_selected = "view";
            $this->view->submenu_selected = "alltickets";
            $url .= "&q=*";
        }

        //hide security related tickets for some users
        if(!user()->allows("view_security_incident_ticket")) {
            $url .= urlencode(" -ticket_type:(Security)");
        }
        if(user()->isGuest()) {
            $url .= urlencode(" -ticket_type:(Security_Notification)");
        }

        //if not specified solr will sort result by score 
        if(isset($_REQUEST["sort"])) {
            switch($_REQUEST["sort"]) {
            case "id": $url .= "&sort=_docid_%20desc";break;
            }
        }

        //apply facet query
        $fq = "";
        foreach($facet_fields as $key=>$prop) {
            if(isset($_REQUEST[$key]) && $_REQUEST[$key] != "") {
                if(@$prop["type"] == "string") {
                    $fq .= " +$key:\"".$_REQUEST[$key]."\"";
                } else {
                    //int by default
                    $fq .= " +$key:".$_REQUEST[$key];
                }
            }
        }
        if($fq != "") {
            $url .= "&fq=".urlencode($fq); //need to urlencode since it contains space + , etc..
        }
        $url .= "&fl=id,title,descriptions,status";

        //do search
        $start = 0;
        if(isset($_REQUEST["s"])) {
            $start = (int)$_REQUEST["s"];
            $url .= "&start=$start";
        }
        $this->view->page_items = 25; //TODO - move to config?
        $url .= "&rows=".$this->view->page_items;

        if(config()->debug) {
            message("debug", $url);
        }
        $ret_json = file_get_contents($url);
        $this->view->result = json_decode($ret_json);
        $this->view->query = $q; //pass back to form

        //paging
        $this->view->page_current = (int)($start/$this->view->page_items);
        $this->view->page_num = ceil($this->view->result->response->numFound / $this->view->page_items);

        //load oim stuff
        $scmodel = new SC();
        $vomodel = new VO();
        $rgmodel = new ResourceGroup();

        //do facet search
        $this->view->facets = array();
        $this->view->faceted = array();
        foreach($facet_fields as $key=>$prop) {
            if(!isset($_REQUEST[$key])) {
                $f_url = $url."&rows=0&facet=true&facet.mincount=1&facet.field=$key";
                $ret_json = file_get_contents($f_url);
                $ret = json_decode($ret_json);
                $recs = $ret->facet_counts->facet_fields->$key;
                $frecs = array();
                for($i = 0;$i < count($recs);$i+=2) {
                    $frecs[$recs[$i]] = array("count"=>$recs[$i+1], "label"=>$recs[$i]);
                }

                //update label for special columns
                switch($key) {
                case "SUPPORTING_SC_ID":
                    foreach($frecs as $id=>&$frec) {
                        $sc = $scmodel->get($id);
                        if(!isset($sc->name)) {
                            $frec["label"] = "(unknown sc:$id)";
                        } else {
                            $frec["label"] = $sc->name;
                        }
                    }
                    break;
                case "ASSOCIATED_VO_ID":
                    foreach($frecs as $id=>&$frec) {
                        $vo = $vomodel->get($id);
                        if(!isset($vo->name)) {
                            $frec["label"] = "(unknown vo:$id)";
                        } else {
                            $frec["label"] = $vo->name;
                        }
                    }
                    break;
                case "ASSOCIATED_RG_ID":
                    foreach($frecs as $id=>&$frec) {
                        $rg = $rgmodel->fetchByID($id);
                        if(!isset($rg->name)) {
                            $frec["label"] = "(unknown rg:$id)";
                        } else {
                            $frec["label"] = $rg->name;
                        }
                    }
                    break;
                }
                
                $this->view->facets[$key] = $frecs;
            } else {
                $label = $_REQUEST[$key];
                switch($key) {
                case "SUPPORTING_SC_ID":
                    $sc = $scmodel->get($label);
                    if(is_null($sc) || !isset($sc->name)) {
                        slog("ASSOCIATED_SC_ID set to invalid label:$label");
                        $label = "SCID:$label";
                    } else {
                        $label = $sc->name;
                    }
                    break;
                case "ASSOCIATED_VO_ID":
                    $vo = $vomodel->get($label);
                    if(is_null($vo) || !isset($vo->name)) {
                        slog("ASSOCIATED_VO_ID set to invalid label:$label");
                        $label = "VOID:$label";
                    } else {
                        $label = $vo->name;
                    }
                    break;
                case "ASSOCIATED_RG_ID":
                    $rg = $rgmodel->fetchByID($label);
                    if(is_null($rg) || !isset($rg->name)) {
                        slog("ASSOCIATED_RG_ID set to invalid label:$label");
                        $label = "RGID:$label";
                    } else {
                        $label = $rg->name;
                    }
                    break;
                }
                $this->view->faceted[$key] = array("value"=>$_REQUEST[$key], "label"=>$label);
            }
        }

        /*
        if(!isset($_REQUEST["priority"])) {
            $f_url = $url."&rows=0&facet=true&facet.mincount=1&facet.field=priority";
            $ret_json = file_get_contents($f_url);
            $ret = json_decode($ret_json);
            $recs = $ret->facet_counts->facet_fields->priority;
            $frecs = array();
            for($i = 0;$i < count($recs);$i+=2) {
                $frecs[$recs[$i]] = $recs[$i+1];
            }
            $this->view->facets["priority"] = $frecs;
        }

        if(!isset($_REQUEST["assignees"])) {
            $f_url = $url."&rows=0&facet=true&facet.mincount=1&facet.field=assignees";
            $ret_json = file_get_contents($f_url);
            $ret = json_decode($ret_json);
            $recs = $ret->facet_counts->facet_fields->assignees;
            $frecs = array();
            for($i = 0;$i < count($recs);$i+=2) {
                $frecs[$recs[$i]] = $recs[$i+1];
            }
            $this->view->facets["assignees"] = $frecs;
        }
        */

        if(isset($_REQUEST["json"])) {
            $this->render("none", null, true);
            echo json_encode($this->view->result);
        }
    }

    public function clean($dirty) {
        return trim(preg_replace('/[^a-zA-Z0-9_ +-\.]/', '', $dirty));
    }

    /*
    //search for ticket title or id
    public function titleidAction() {
        $q = $_REQUEST["q"];
        $url = config()->solr_host."/select?wt=json";
    }
    */

    public function autocompleteAction() {
        $q = $this->clean($_REQUEST["q"]);
        //$limit = (int)$_REQUEST["limit"];
        //$timestamp = $_REQUEST["timestamp"];//what for?

        $url = config()->solr_host."/suggest?q=".urlencode($q)."&wt=json"; //use /suggest

        $ret_json = file_get_contents($url);
        $ret = json_decode($ret_json);

        //process result
        $this->view->suggests = array();
        if(isset($ret->spellcheck)) {
            $c = count($ret->spellcheck->suggestions);
            if($c == 4) {
                $suggests = array_slice($ret->spellcheck->suggestions, -1); //pull last
                $sugs = $ret->spellcheck->suggestions[count($ret->spellcheck->suggestions)-3];//use last
                $found = $sugs->numFound;
                $start = $sugs->startOffset;
                $base = substr($q, 0, $start);
                foreach($sugs->suggestion as $id=>$sug) {
                    $this->view->suggests[] = $base.$sug;
                }
            }
        } else {
            elog("bad reply from $url");
            elog($ret_json);
        }

        /*
        foreach($recs as $rec) {
            if(!isset($rec->v2)) $rec->v2 = "";
            echo $rec->v1."\t".$rec->v2."\t".$rec->type."\n";
        }
        */
    }
} 
