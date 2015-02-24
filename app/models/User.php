<?

class AuthException extends Exception {
    public function __construct($message, $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}

//lookup person information
class User
{
    public function __construct($dn)
    {
        //action that this user can perform
        $this->action = array();

        $this->dn = $dn;
        $this->dn_id = null;
        $this->contact_id = null;
        $this->contact_name = "Guest";
        $this->contact_email = "";
        $this->contact_phone = "";
        //$this->timezone = "UTC";
        $this->disable = true;

        $this->guest = true;
        if($dn !== null) {
            $this->lookupDN($dn);
            if($this->dn_id !== null) {
                $this->guest = false;
                $this->lookupActions();
            }
        }
        /*
        if(config()->debug) {
            slog("Debug Dump of User Object");
            slog(print_r($this, true));
        }
        */
    }

    private function lookupActions()
    {
        $model = new DNAuthorizationType();
        $dnauthtypes = $model->fetchAllByDNID($this->dn_id);

        $matrix_model = new AuthorizationTypeAction();
        $action_model = new Action();
        $actions = $action_model->fetchAll();

        //for each auth types that the user is member for..
        foreach($dnauthtypes as $dnauthtype) {
            $aids = $matrix_model->fetchAllByAuthTypeID($dnauthtype->authorization_type_id);

            //for each action under that auth type..
            foreach($aids as $aid) {
                $action_id = (int)$aid->action_id;
                //don't enter same action twice.
                if(isset($this->action[$action_id])) continue;

                //search for the action
                foreach($actions as $action) {
                    if($action->id == $action_id) {
                        $this->action[$action_id] = $action->name;
                        break;
                    }
                }
            }
        }
    }

    private function lookupDN($dn)
    {
        //make sure user DN exists and active
        $sql = "select d.*, c.*, d.id as dn_id, d.disable as dn_disable from dn d left join contact c on (d.contact_id = c.id)
                    where dn_string = \"$dn\"";
        $row = db("oim")->fetchRow($sql);
        if($row) {
            $this->dn_id = $row->dn_id;
            $this->contact_id = $row->contact_id;
            $this->contact_name = $row->name;
            $this->contact_email = $row->primary_email;
            $this->contact_phone = $row->primary_phone;
            $this->timezone = $row->timezone;
            $this->disable = ($row->disable || $row->dn_disable);
        } else {
            slog("DN: $dn doesn't exist in oim");
        }
    }

    public function isGOCMachine() {
        $remote_ip = $_SERVER["REMOTE_ADDR"];
        foreach(config()->gocip as $prefix) {
            if(strpos($remote_ip, $prefix) === 0) {
                slog("Accessed from GOC machine: $remote_ip");
                return true;
            }
        }
        return false;
    }

    public function allows($action) {
        return in_array(config()->role_prefix.$action, $this->action);
    }
    public function check($action) {
        if(!$this->allows($action)) {
            throw new AuthException("You don't have $action permission to access this page.");
        }
    }

    public function isGuest() { return $this->guest; }
    public function isDisabled() { return $this->disable; }
    public function getPersonID() { return $this->contact_id; }
    public function getPersonName() { return $this->contact_name; }
    public function getPersonEmail() { return $this->contact_email; }
    public function getPersonPhone() { return $this->contact_phone; }
    public function getDN() { return $this->dn; }
    public function getDNID() { return $this->dn_id; }
    //public function getTimeZone() { return $this->timezone; }
}
