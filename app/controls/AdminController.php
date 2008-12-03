<?

class AdminController extends Zend_Controller_Action 
{ 
    public function load()
    {
        //make sure the request originated from localhost
        if($_SERVER["REMOTE_ADDR"] != $_SERVER["SERVER_ADDR"]) {
            //pretend that this page doesn't exist
            $this->getResponse()->setRawHeader('HTTP/1.1 404 Not Found');
            echo "404";
            exit;
        }
    }

    public function logrotateAction()
    {
        $this->load();
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
/*
        //make sure person exists
        foreach($names as $name=>$email) {
            list($first_name, $middle_name, $last_name) = split(" ", $name);
            if($last_name === null) {
                $last_name = $middle_name;
                $middle_name = "";
            }
            $sql = "select * from oim.person where first_name = '$first_name' and last_name = '$last_name'";
            $rec = db()->fetchAll($sql);
            if(count($rec) == 0) {     
                echo "insert into oim.person (first_name, middle_name, last_name, primary_email, active, comment) values ('$first_name','$middle_name','$last_name','$email',1,'for sponsor');<br/>";
            }
        }
*/
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
                //echo "update oim.person set primary_email = '$email' where first_name = '$first_name' and last_name = '$last_name';<br/>";
                echo "<h2>$first_name $last_name</h2>";
                echo "<blockquote>";
                echo "<b>RA script address</b><br/>$email<br/>";
                $sql = "select primary_email from oim.person where first_name = '$first_name' and last_name = '$last_name'";
                $rec = db()->fetchOne($sql);
                echo "<b>OIM primary_email</b><br/>$rec<br/>";
                echo "</blockquote>";
            }
        }
/*
        //add it to facility_contact
        foreach($names as $name=>$email) {
            list($first_name, $middle_name, $last_name) = split(" ", $name);
            if($last_name === null) {
                $last_name = $middle_name;
                $middle_name = "";
            }
            //lookup person_id
            $sql = "select person_id from oim.person where first_name = '$first_name' and last_name = '$last_name'";
            $recs = db()->fetchAll($sql);
            $rec = $recs[0];
            $id = $rec->person_id;

            //check to see if the person is already in the facility_contact as sponsor
            $sql = "select * from oim.facility_contact where person_id = $id and type_id = 8";
            $rec = db()->fetchAll($sql);
            if(count($rec) == 0) {     
                echo "insert into oim.facility_contact (person_id, facility_id, type_id, rank_id, active) values ($id, 1, 8, 1, 1);<br/>";
            }
        }
*/
        $this->render("none", true);
    }
} 
