<?
function cmp_time($a, $b) {
    //error_log($a);
    return ($a["time"] < $b["time"]);
}

class RssController extends Zend_Controller_Action 
{
    //make sure to setup cron to remove old posts
    //0 0 * * * root find /home/hayashis/dev/ticket/bin/solrloader/posted -name *.xml -mtime +30 -exec /bin/rm -f {} \;
    public function init() {
        header('Content-type: text/xml');
        //$this->_helper->viewRenderer->setNoRender();
    }

    public function indexAction()
    {
        //load recently updated ticket contents (from solrloader)
        $dir = config()->rss_posted_xml_dir;
        $xmlpaths = scandir($dir);
        //$now = time();
        //parse the xml
        $items = array();
        foreach($xmlpaths as $xmlpath) {
            if($xmlpath[0] == ".") continue;
            $mtime = filemtime($dir."/".$xmlpath);

            /* -- cron should remove such updates?
            //ignore ticket posted more than 30 days
            $age = $now - $mtime;
            if($age > 3600*24*30) continue;
            */

            //echo "<p>$xmlpath $age $now</p>";
            $ticket_xml = file_get_contents("$dir/$xmlpath");
            $doc = simplexml_load_string($ticket_xml);
            $item = array("time"=>$mtime);
            foreach($doc->doc->field as $field) {
                $attrs = $field->attributes();
                $value = (string)$field;
                $key = (string)$attrs->name;
                //error_log($key);
                switch($key) {
                case "assignees":
                case "ccs":
                    $item[$key][] = $value;
                    break;
                case "descriptions":
                    //only keep the first description?
                    if(!isset($item["description"])) {
                        $item["description"] = $value;
                    }

                    break;
                default:
                    //print $key. " ".$value."\n";
                    $item[$key] = $value; 
                    break;
                }
            }

            //apply filtering requested by the user (right now.. just assignee)
            //conditions will be "AND"-ed, but I think we want "OR" instead?
            if(isset($_REQUEST["assignee"])) {
		if(!isset($item["assignees"])) {
			error_log("ticket doesn't seem to have any assignees..");
			error_log($ticket_xml);
			continue;
		}
                if(!in_array($_REQUEST["assignee"],$item["assignees"])) {
                    continue;
                }
            }
            if(isset($_REQUEST["cc"]) && isset($item["ccs"])) {
                if(!in_array($_REQUEST["cc"],$item["ccs"])) {
                    continue;
                }
            }

            $items[] = $item;
        }

        //sort items by update time
        usort($items, "cmp_time");

        $this->view->items = $items;
    }
} 

