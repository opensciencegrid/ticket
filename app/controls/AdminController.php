<?

class AdminController extends Zend_Controller_Action 
{ 
    public function init()
    {
        //don't use this - we are really doing 2 things here..
        //1) rendering the real admin page via browser
        //2) accepting request from cron from localhost
    }

    public function indexAction()
    {
        $this->view->submenu_selected = "admin";

        if(!in_array(role::$goc_admin, user()->roles)) {
            $this->render("error/404", null, true);
            return;
        }
    }

    private function accesscheck()
    {
        //make sure the request originated from localhost
        if($_SERVER["REMOTE_ADDR"] != $_SERVER["SERVER_ADDR"]) {
            //pretend that this page doesn't exist
            $this->getResponse()->setRawHeader('HTTP/1.1 404 Not Found');
            echo "local access only";
            exit;
        }
    }
    public function logrotateAction()
    {
        $this->accesscheck();

        dlog("Writing config file for logrotate...");
        $root = getcwd()."/";
        $statepath = "/tmp/ticket.rotate.state";
        $config = "compress \n".
            $root.config()->logfile. " ". 
            $root.config()->error_logfile. " ". 
            $root.config()->audit_logfile." {\n".
            "   rotate 5\n".
            "   size=50M\n".
            "}";
        $confpath = "/tmp/ticket.rotate.conf";
        $fp = fopen($confpath, "w");
        fwrite($fp, $config);
        
        dlog("running logroate with following config\n$config");
        passthru("/usr/sbin/logrotate -s $statepath $confpath");
    }

    public function groupticketAction()
    {
        $this->accesscheck();

        $model = new Tickets();
        $tickets = $model->getrecent();
        header('content-type: text/xml');
        echo "<groups>";

        $grouped = array();
        while(sizeof($tickets) > 1) {
            $master = array_shift($tickets);
            if(in_array($master->mrid, $grouped)) {
                //already in other group..
                continue;
            }

            $master_desc = $this->grabdesc($master);
            $master_size = strlen($master_desc);
            $date = $master->mrupdatedate;
            echo "<group>";
            $this->outputgroup($master, $master_desc);
            foreach($tickets as $ticket) {
                if(!in_array($ticket->mrid, $grouped)) {
                    $ticket_desc = $this->grabdesc($ticket);
                    $ticket_size = strlen($ticket_desc);
                    similar_text($master_desc, $ticket_desc, $p);
                    $p = round($p, 2);
                    if($p > 40) {
                        $this->outputgroup($ticket, $ticket_desc);
                        $grouped[] = $ticket->mrid;
                    }
                }
            }
            echo "</group>";
        }
        echo "</groups>";

        $this->render("none", true);
    }

    private function outputgroup($ticket, $content) 
    {
        echo "<ticket>";
        echo "<id>$ticket->mrid</id>";
        echo "<title>".htmlentities($ticket->mrtitle)."</title>";
        echo "<status>".Footprint::parse($ticket->mrstatus)."</status>";
        echo "<dest>$ticket->mrdest</dest>";
        echo "<url>https://oim.grid.iu.edu/gocticket/viewer?id=$ticket->mrid</url>";
        echo "<desc><![CDATA[$content]]></desc>";
        echo "</ticket>";
    }

    private function grabdesc($ticket)
    {
        $desc_all = $ticket->mralldescriptions;

        //use the first description
        $desc_sections = preg_split("/<! [^>]* >/", $desc_all, 2, PREG_SPLIT_NO_EMPTY);
        $desc = trim($desc_sections[0]);
        $desc = html_entity_decode($desc, ENT_QUOTES);

        //truncate if it's too long
        $desc = substr($desc, 0, 1000);

        //remove standard GOC signature
        $sig = "OSG Grid Operations Center
goc@opensciencegrid.org, 317-278-9699
Visit the OSG Support Page:
http://www.opensciencegrid.org/ops
RSS: http://www.grid.iu.edu/news";
        $desc = str_replace($sig, "", $desc);

        //remove Thank you note..
        $sig = "/\nThank you(.|\n)*/i";
        $desc = preg_replace($sig, "", $desc);
        $sig = "/\nThanks(.|\n)*/i";
        $desc = preg_replace($sig, "", $desc);

        //remove meta info
        $sig = "/\n\[META Information for OSG GOC Support Staff\](.|\n)*/";
        $desc = preg_replace($sig, "", $desc);

        return $desc;
    }    

/*
    public function syncAction()
    {
        $names = array(
        "Marc Baarmand" => "baarmand@fit.edu",
        "Mats Rynge" => "rynge@renci.org",
        "Eva Halkiadakis" => "evah@fnal.gov",
        "Edmund Berry" => "eberry@Princeton.edu",
        "Peter Loch" => "loch@physics.arizona.edu",
        "Dave Christian" => "dcc@fnal.gov",
        "Tiberiu Stef-Praun" => "tiberius@ci.uchicago.edu",
        "Don Holmgren" => "djholm@fnal.gov",
        "Guenakh Mitselmakher" => "mitselmakher@phys.ufl.edu",
        "Rob Quick" => "rquick@iupui.edu",
        "Dick Greenwood" => "greenw@phys.latech.edu",
        "Sridhar Gullapalli" => "sridhar@isi.edu",
        "Roy Williams" => "roy@cacr.caltech.edu",
        "Doug Olson" => "dlolson@lbl.gov",
        "Kent Blackburn" => "kent@ligo.caltech.edu",
        "Alan Sill" => "Alan.Sill@ttu.edu",
        "John Whelan" => "jtwhelan@loyno.edu",
        "Doug Benjamin" => "benjamin@phy.duke.edu",
        "Preston Smith" => "psmith@physics.purdue.edu",
        "Scott Koranda" => "skoranda@uwm.edu",
        "Patrick Brady" => "patrick@gravity.phys.uwm.edu",
        "Jim Williams" => "william@indiana.edu",
        "Ian Foster"=> "foster@mcs.anl.gov",
        "Larry Price"=> "lprice@anl.gov",
        "Jim Shank"=> "shank@bu.edu",
        "Torre Wenaus"=> "torre@wenaus.com",
        "Julian Bunn"=> "julian@cacr.caltech.edu",
        "Harvey Newman"=> "newman@hep.caltech.edu",
        "Ruth Pordes"=> "ruth@fnal.gov",
        "Rick Thies"=> "ret@fnal.gov",
        "Keith Baker"=> "baker@jlab.org",
        "John Huth"=> "huth@physics.harvard.edu",
        "Valerie Taylor"=> "taylor@cs.tamu.edu",
        "Tim Olson"=> "tim_olson@skc.edu",
        "Reagan Moore"=> "moore@sdsc.edu",
        "Richard Mount"=> "richard.mount@slac.stanford.edu",
        "Alex Szalay"=> "szalay@jhu.edu",
        "Paul Avery"=> "avery@phys.ufl.edu",
        "Bockjoo Kim"=> "bockjoo@phys.ufl.edu",
        "Laura Pearlman"=> "laura@isi.edu",
        "Soumya D. Mohanty"=> "mohanty@phys.utb.edu",
        "Miron Livny"=> "miron@cs.wisc.edu",
        "Keith Riles"=> "kriles@umich.edu",
        "Rob Gardner"=> "rwg@hep.uchicago.edu",
        "Robert Gardner"=> "rwg@hep.uchicago.edu",
        "Albert Lazzarini"=> "lazz@ligo.caltech.edu",
        "Lothar Bauerdick"=> "bauerdick@fnal.gov",
        "Ewa Deelman"=> "deelman@isi.edu",
        "Richard Cavanaugh"=> "cavanaug@phys.ufl.edu",
        "Paul Sheldon"=> "paul.sheldon@vanderbilt.edu",
        "Jens Voeckler"=> "voeckler@cs.uchicago.edu",
        "Shawn McKee"=> "smckee@umich.edu",
        "Horst Severini"=> "hs@nhn.ou.edu",
        "Gaurang Mehta"=> "gmehta@isi.edu",
        "Alain Roy"=> "roy@cs.wisc.edu",
        "Ian Fisk"=> "ifisk@fnal.gov",
        "Gabriela Gonzalez"=> "gonzalez@lsu.edu",
        "Bernard Whiting"=> "bernard@phys.ufl.edu",
        "Jon Smillie"=> "Jon.Smillie@anu.edu.au",
        "Jorge Rodriguez"=> "jorge@phys.ufl.edu",
        "Saul Youssef"=> "youssef@bu.edu",
        "Kaushik De"=> "kaushik@uta.edu",
        "Gyorgy Fekete"=> "gfekete@pha.jhu.edu",
        "Nelson Christensen"=>"nchriste@carleton.edu",
        "David Strom"=>"strom@physics.uoregon.edu",
        "Terrence Martin"=>"tmartin@physics.ucsd.edu",
        "Shaowen Wang"=>"shaowen@uiuc.edu",
        "Steve Gallo"=>"smgallo@ccr.buffalo.edu",
        "Sebastien Goasguen"=>"sebgoa@clemson.edu",
        "Alina Bejan"=>"abejan@ci.uchicago.edu"
        );    
        //check email address
        echo "Email mis-match";
        foreach($names as $name=>$email) {
            list($first_name, $middle_name, $last_name) = split(" ", $name);
            if($last_name === null) {
                $last_name = $middle_name;
                $middle_name = "";
            }
            $sql = "select * from oim.person where first_name = '$first_name' and last_name = '$last_name' and primary_email = '$email'";
            $rec = db()->fetchAll($sql);
            if(count($rec) == 0) {     
                echo "<h2>$first_name $last_name</h2>";
                echo "<blockquote>";
                echo "<b>RA script address</b><br/>$email<br/>";
                $sql = "select primary_email from oim.person where first_name = '$first_name' and last_name = '$last_name'";
                $rec = db()->fetchOne($sql);
                echo "<b>OIM primary_email</b><br/>$rec<br/>";
                echo "</blockquote>";
            }
        }

        $this->render("none", true);
    }
*/
} 
