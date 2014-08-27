<?
///////////////////////////////////////////////////////////////////////////////
//
// Common Configuration
//
///////////////////////////////////////////////////////////////////////////////

class common_config
{
    function __construct() {
        ///////////////////////////////////////////////////////////////////////
        //
        // Main configuration (Mostly common stuff)
        //

        //application version to use for version specific data structures, etc.
        $this->version = "1.80";
        //application to display or used in email
        $this->app_name = "GOC Ticket";
        //application name used in places such as session name
        $this->app_id = "gocticket";
        $this->copyright = "Copyright 2013 The Trustees of Indiana University - Developed for Open Science Grid";

        //banner to show on all pages
        $this->banner = null;

        //114 - OSG DEV
        //71 - production
        $this->project_id = "114"; 

        //server's current timezone offset in hours from UTC
        $this->timezone_offset = -4;

        //certificate & key file for signed email (make sure they are daemon readable)
        $this->signed_email_key = "/etc/grid-security/http/key.pem" ;
        $this->signed_email_cert = "/etc/grid-security/http/cert.pem" ;

        $this->group_xml_path = "/tmp/gocticket.groupticket.xml";

        //error handling
        $this->elog_email = false;
        $this->elog_email_address = "overrideme";
        //email address to send form submittion error to.
        $this->error_from = "GOC Ticket <goc@opensciencegrid.org>";
        $this->error_sms_to = array("goc-alert@googlegroups.com");

        $this->email_from = "Grid Operations Center <goc@opensciencegrid.org>";
        //we are now using GOC's user cert, so this has to come from goc@, otherwise signature verification will fail
        $this->email_from_security = "Grid Operations Center <goc@opensciencegrid.org>";


        //address to send a ticket update
        $this->ticket_update_address = "osg@tick.globalnoc.iu.edu";

        //define who gets SMS notification based on different priority
        $this->sms_notification = array(
            1=>/*critical*/     array("hayashis", "steige", "rquick", "kagross"),
            2=>/*high*/         array("hayashis"),
            3=>/*elevated*/     array("hayashis"),
            4=>/*normal*/       array()
        );

        $this->sms_address = array(
            "hayashis"=>"8126067104@txt.att.net", 
            "steige"=>  "steige@vtext.com", 
            "rquick"=>  "3176953276@txt.att.net",
            "kagross"=>  "8123226660@vtext.com"
        );

        $this->goc_staff = array("hayashis", "agopu", "steige", "thomlee", "rquick", "kagross", "echism", "cpipes", "adeximo");

        //Footprint team name
        $this->assignee_team = "OSG__bGOC__bSupport__bTeam";

        //database info
        $this->db_type = "Pdo_Mysql";
        $this->oim_db_params = array(
            array(
                'unix_socket'     => '/usr/local/rsv-gratia-collector-1.0/vdt-app-data/mysql5/var/mysql.sock',
                'host'     => "localhost",
                'username' => "ticket",
                'password' => "somepass",
                'dbname'   => "oimnew",
                'port'     => 49152
            )
        );

        //executes debuging code
        $this->debug = false;
        $this->logdir = "app/logs";
        $this->simulate = false;
        $this->logfile = "app/logs/log.txt";
        $this->error_logfile = "app/logs/error.txt";
        $this->audit_logfile = "app/logs/audit.txt";

        //blogger rss host settings
        $this->blogger_user = "user@somewhere.com";
        $this->blogger_pass = "password";
        $this->blogger_blogid = "11111111111111111";

        //log db profile (only available in debug mode)
        $this->profile_db = false;

        //forward http request to https
        $this->force_https = true;

        //locale
        //$this->date_format_full = "M j, Y h:i A e";
        $this->date_format_full = "M j, Y h:i A";
        $this->date_format = "M j, Y";

        //tag to put before META information on the description
        //if you change this, the ticket with previous tag will be displayed to everyone
        $this->metatag = "\n[META Information]";//need \n at the beginning to not pick up quoted meta info

        $this->fp_soap_location = "https://tick.globalnoc.iu.edu/MRcgi/MRWebServices.pl";
        $this->fp_soap_uri = "https://tick.globalnoc.iu.edu/MRWebServices";

        $this->editor_teams = array(
            array(0,1,3),
            array(2),
            array(4,5,6)
        );

        //list of teams to show under navigator / assignee
        $this->navigator_assignee_list = array(0,1,3,4,5,6);
        $this->navigator_refresh = 60; //seconds
        $this->role_prefix = "gocticket_";

        $this->botlist = array(
            "Teoma",
            "alexa",
            "froogle",
            "inktomi",
            "looksmart",
            "URL_Spider_SQL",
            "Firefly",
            "NationalDirectory",
            "Ask Jeeves",
            "TECNOSEEK",
            "InfoSeek",
            "WebFindBot",
            "girafabot",
            "crawler",
            "www.galaxy.com",
            "Googlebot",
            "Scooter",
            "Slurp",
            "appie",
            "FAST",
            "WebBug",
            "Spade",
            "ZyBorg",
            "rabaz"); 
        $this->closeticket_window = time()-3600*24*60;

        //list of URL that can access various TTX interface (right now only ggus)
        $this->ttx_clients = array(
            "tick-indy.globalnoc.iu.edu",
            "tick.globalnoc.iu.edu",
            "fp-dev.grnoc.iu.edu", 
            "soichi.dvrdns.org");

        //number of lines to show before hiding into "Show More" section
        $this->description_showlines = 35;

        //number of days to show for "closed withtin N days" section of myticket page
        $this->myticket_closed_days = 7;

        //when to start showing NAD in yellow flag (in hours)
        $this->nad_alert_hours = 24;

        //Google Search aPI key
        $this->google_search_api_key = "AIzaSyAIommV_PJUjC36Y9wwdndFwqzz4QLhpTs";
        $this->google_custom_search_cx = "016752695275174109936:9u1k_fz_bag";

        #this should be updated back to ticket1 once we installed incommon cert with tickte1 as SAN
        #until then, we will be channelling traffic through LVS to reach ticket1
        $this->chatjs_url = "https://ticket.grid.iu.edu:12346"; //url used by client's browser
        $this->local_chatjs_url = "https://ticket1.goc:12346"; //used to make localhost request from php server

        $this->attachment_dir = "/usr/local/attachments/project_".$this->project_id;
        $this->gocip = array(
            "129.79.53.", //GOC eth0 IPV4 VLAN
            "2001:18e8:2:6:", //GOC eth0 IPV6 VLAN
            "fd2f:6feb:37:" //GOC eth1 IPV6 VLAN
        );//GOC VLAN

        //event server
        $this->event_host = "event.grid.iu.edu";
        $this->event_user = "goc";
        $this->event_pass = "*******";
        $this->event_vhost = "/osg";
        $this->event_exchange = "ticket";

        $this->dn_override = array(); //use this in site config to temporarily override dn for testing

        //recapcha config
        $this->captcha_public_key = "override_with_global_key";
        $this->captcha_private_key = "override_with_global_key";

        $this->spam_keywords = array("viagra", "penis", "pills", "cheapest");

        $this->campusgrid_sc_id = 67;//used to assign campusgrid sc to all campus grid vo registrtion request ticket

        $this->solr_host = "http://localhost:8983/solr/collection1";

        $this->akismet_key = "override";

        $this->rss_posted_xml_dir = "/usr/local/ticket/bin/solrloader/posted";
    }

    function lookupFPID($txid, $tid) 
    {
        switch($txid) {
        case "fp114_ggus1": return array("TX_FP114_GGUS1", "https://test.ggus.eu/ggus/?mode=ticket_info&ticket_id=$tid");
        case "fp114_ggus2": return array("TX_FP114_GGUS2", "https://ggus.eu/ws/ticket_info.php?ticket=$tid");
        case "fp114_rtgocdev": return array("TX_FP114_RTGOCDEV", "https://soichi6.grid.iu.edu/rt/Ticket/Display.html?id=$tid");
        case "fp114_rtbnlprod": return array("TX_FP114_RTBNLPROD", null);
        case "fp71_ggus1": return array("TX_FP71_GGUS1", "https://test.ggus.eu/ggus/?mode=ticket_info&ticket_id=$tid");
        case "fp71_ggus2": return array("TX_FP71_GGUS2", "https://ggus.eu/ws/ticket_info.php?ticket=$tid");
        case "fp71_rtgocdev": return array("TX_FP71_RTGOCDEV", "https://soichi6.grid.iu.edu/rt/Ticket/Display.html?id=$tid");
        case "fp71_rtbnlprod": return array("TX_FP71_RTBNLPROD", null);

        case "fp114_rtvdttest": return array("TX_FP114_RTVDTTEST", null);
        case "fp71_rtvdtprod": return array("TX_FP71_RTVDTPROD", null);

        case "fp114_fermitest": return array("TX_FP114_FERMITEST", "https://fermitrn.service-now.com/nav_to.do?uri=incident.do?sys_id=$tid");
        case "fp114_fermidev": return array("TX_FP114_FERMIDEV", "https://fermidev.service-now.com/nav_to.do?uri=incident.do?sys_id=$tid");
        case "fp71_fermi": return array("TX_FP71_FERMI", "https://fermi.service-now.com/nav_to.do?uri=incident.do?sys_id=$tid");

        //deprecated, by hit by Google
        case "fp114_rt1": return array("TX_FP114_RT1", null);

        case "fp114_xsedetest": return array("TX_FP114_XSEDETEST", "https://newtickets.xsede.org/Ticket/Display.html?id=$tid");
        case "fp71_xsede": return array("TX_FP71_XSEDE", "https://newtickets.xsede.org/Ticket/Display.html?id=$tid");
        case "fp114_osgjiratest": 
            $json = file_get_contents("https://goctx-test:goctx#3cqwe@jira.opensciencegrid.org/rest/api/2/issue/$tid?fields=summary");
            $ret = json_decode($json);
            return array("TX_FP114_OSGJIRATEST", "https://jira.opensciencegrid.org/browse/".$ret->key, $ret->key); 
        case "fp71_osgjira":
            $json = file_get_contents("https://goctx:goctx#7GiQOiL@jira.opensciencegrid.org/rest/api/2/issue/$tid?fields=summary");
            $ret = json_decode($json);
            return array("TX_FP71_OSGJIRA", "https://jira.opensciencegrid.org/browse/".$ret->key, $ret->key);
        }
        wlog("Unknown TXID passed to lookupFPID: $txid");
        return null;
    }
}

function noquery() {
    $last_pos = strrpos($_SERVER["REQUEST_URI"], "?");
    return substr($_SERVER["REQUEST_URI"], 0, $last_pos);
}
function base() {
    $last_pos = strrpos($_SERVER["SCRIPT_NAME"], "/");
    return substr($_SERVER["SCRIPT_NAME"], 0, $last_pos);
}
function fullbase($p = "http") {
    if($p == "http") {
        if(isset($_SERVER["HTTPS"])) $p = "https";
    }

    return $p."://".$_SERVER["SERVER_NAME"].base();
}
function fullurl() { 
    $s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : ""; $protocol = strleft(strtolower($_SERVER["SERVER_PROTOCOL"]), "/").$s; $port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]); return $protocol."://".$_SERVER['SERVER_NAME'].$port.$_SERVER['REQUEST_URI']; 
} 
function strleft($s1, $s2) { 
    return substr($s1, 0, strpos($s1, $s2)); 
}

/*
class validator
{
    public static $phone = array("//i");
    //public static $phone = array("/^[0-9\-+()\ ]*$/i");
}
*/

