#!/usr/bin/php
<?php

date_default_timezone_set("UTC");

function config() {
    return array(
        "fp_project"=>"71",//114 for dev
        "fp_user"=>"goc",
        "fp_pass"=>"5be1499a577add53307142asdaq2WSagsASDGa",
        "data_host"=>"data-itb.goc",
        "data_db"=>"gocticket",
        "data_user"=>"ticket",
        "data_pass"=>"qweasdzxc"
    );
}

function parse_fpstr($str)
{
    $str = str_replace("__u", "-", $str);
    $str = str_replace("__b", " ", $str);
    $str = str_replace("__f", "/", $str);
    $str = str_replace("__P", "(", $str);
    $str = str_replace("__p", ")", $str);
    return $str;
}

function load_fp($id, &$details) {
    $config = config();

    ///////////////////////////////////////////////////////////////////////////////////////////////////
    //load detail from footprints
    try {
        $client = new SoapClient(NULL, array(
            "location"=>"https://tick.globalnoc.iu.edu/MRcgi/MRWebServices.pl", "uri"=>"MRWebServices",
            "style"=>SOAP_RPC, "use" => SOAP_ENCODED)
        );
        
        $fp_details = $client->MRWebServices__getIssueDetails_goc($config["fp_user"],$config["fp_pass"],'',$config["fp_project"],$id);
        //var_dump($fp_details);

        $details["title"] = $fp_details->title;
        $details["status"] = parse_fpstr($fp_details->status);
        $details["priority"] = $fp_details->priority;//TODO - conver this to string
        $details["nextaction"] = $fp_details->ENG__bNext__bAction__bItem;
        
        //process assignee / ccs
        $assignees = explode(" ", $fp_details->assignees);
        $formatted_assignees = array();
        $formatted_ccs = array();
        foreach($assignees as $assignee) {
            $assignee = parse_fpstr($assignee);
            if(strpos($assignee, "CC:") === 0) {
                $formatted_ccs[] = substr($assignee, 3);
            } else {
                //TODO - convert to user friendly user names
                $formatted_assignees[] = $assignee;
            }
        }
        $details["assignees"] = $formatted_assignees;
        $details["ccs"] = $formatted_ccs;

        //process description
        $descs = array();
        foreach($fp_details->allDescriptions as $desc) {
            $time = $desc->stamp;//TODO - what should I do with thi?
            $descs[$time] = $desc->data;
        }
        $details["descriptions"] = $descs;

    } catch (SoapFault $exception) {
        print "ERROR! - Got a SOAP exception:<br>";
        echo $exception;             
    }
}

function load_meta($id, $db, &$details) {
    $config = config();
    $projid = $config["fp_project"];

    $result = mysql_query("SELECT * FROM metadata WHERE ticket_id = $id AND project_id = $projid");
    while($row = mysql_fetch_array($result)) {
        if($row["value"] === "null") continue;
        switch($row["key"]) {
        case "SUPPORTING_SC_ID": $details["SUPPORTING_SC_ID"] = (int)$row["value"]; break;
        case "ASSOCIATED_VO_ID": $details["ASSOCIATED_VO_ID"] = (int)$row["value"]; break;
        case "GGUS_TICKET_ID": $details["GGUS_TICKET_ID"] = (int)$row["value"]; break;
        case "ASSOCIATED_RG_ID": $details["ASSOCIATED_RG_ID"] = (int)$row["value"]; break;
        case "ASSOCIATED_R_ID": $details["ASSOCIATED_R_ID"] = (int)$row["value"]; break;
        case "SUBMITTER_NAME": $details["SUBMITTER_NAME"] = (int)$row["value"]; break;
        }
        /*
mysql> select distinct(`key`) from metadata;
+----------------------+
| key                  |
+----------------------+
| SUBMITTED_VIA        |
| SUBMITTER_NAME       |
| ASSOCIATED_R_ID      |
| ASSOCIATED_R_NAME    |
| ASSOCIATED_RG_ID     |
| ASSOCIATED_RG_NAME   |
| ASSOCIATED_VO_ID     |
| SUPPORTING_SC_ID     |
| SUBMITTER_DN         |
| GGUS_PROBLEM_TYPE    |
| GGUS_TICKET_ID       |
| GGUS_TICKET_TYPE     |
| SERVICE_ID           |
| SERVICENOW_TICKET_ID |
| SUPPORTING_SC_NAME   |
| ASSOCIATED_VO_NAME   |
+----------------------+
16 rows in set (0.27 sec)
        */
    }
}

function xmlentities($s)
{
    static $patterns = null;
    static $replacements = null;
    static $translation = null;

    //remove non ascii --
    $output = str_replace("\n","[NEWLINE]",$s);
    $output = preg_replace('/[^(\x20-\x7F)]*/','', $output);
    $output = str_replace("[NEWLINE]","\n",$output);
    return htmlentities($output);
}

//get list of ticket ids that are recently updated
//TODO - add ability specify "updated since" 
function list_updated($lastrun_timestamp) {
    $config = config();
    $ids = array();
    try {
        $recent_date = date("Y-m-d G:i:s", $lastrun_timestamp); //24 hours
        echo "loading tickets updated since $recent_date\n";
        $query = "select mrID from MASTER".$config["fp_project"]." WHERE mrupdatedate >= '$recent_date'";
        $client = new SoapClient(NULL, array(
            "location"=>"https://tick.globalnoc.iu.edu/MRcgi/MRWebServices.pl", "uri"=>"MRWebServices",
            "style"=>SOAP_RPC, "use" => SOAP_ENCODED)
        );
        $tickets = $client->MRWebServices__search_goc($config["fp_user"],$config["fp_pass"],'',$query);
        foreach($tickets as $ticket) {
            $ids[] = $ticket->mrid;
        }
        //print_r($ids);
    } catch (SoapFault $exception) {
        print "ERROR! - Got a SOAP exception:<br>";
        echo $exception;             
    }
    return $ids;
}

function connect_metadb() {
    $config = config();
    $db = mysql_connect($config["data_host"], $config["data_user"], $config["data_pass"]);
    mysql_select_db($config["data_db"], $db);
    return $db;
}
///////////////////////////////////////////////////////////////////////////////////////////////////
// Load ticket details

$db = connect_metadb();

$lastrun_timestamp = (int)file_get_contents("lastrun.txt");

$ids = list_updated($lastrun_timestamp);
//$ids = array(11912, 11913, 11915, 11926, 11971, 11976, 11977, 11987, 11995, 11997, 12003, 12015, 12026, 12031, 8817, 8889);
$total = count($ids);
$c = 0;
foreach($ids as $id) {
    $c++;
    $url = "https://ticket.opensciencegrid.org/goc/$id";
    $details = array("id"=>$id, "url"=>$url);
    echo "loading $id ($c of $total)\n";
    load_fp($id, $details);
    load_meta($id, $db, $details);
    
    //conver to solar post xml
    $post = "<add><doc>\n";
    foreach($details as $name=>$detail) {
        if(is_array($detail)) {
            foreach($detail as $value) {
                $value_xml = xmlentities($value);
                $post .= "<field name=\"$name\">$value_xml</field>\n";
            }
        } else {
            $detail_xml = xmlentities($detail);
            $post .= "<field name=\"$name\">$detail_xml</field>\n";
        }
    }
    $post .= "</doc></add>";
    
    //write to post directory
    file_put_contents("post/$id.xml", $post);
    //sleep(1);
}

file_put_contents("lastrun.txt", time());

exit(count($ids));

?>
