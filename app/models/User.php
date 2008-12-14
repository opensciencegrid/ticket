<?

//lookup person information as well as role information
class User
{
    public function __construct($dn)
    {
        $this->roles = config()->auth_metrics[authtype::$auth_guest];
        $this->person_id = null;
        $this->person_fullname = "Guest";
        $this->person_firstname = "";
        $this->person_lastname = "";
        $this->person_email = "";
        $this->person_phone = "";
        $this->dn = $dn;

        $this->guest = true;
        if($dn !== null) {
            $this->lookupUserID($dn);
            if($this->person_id !== null) {
                $this->guest = false;
                $this->lookupRoles($this->person_id);
            }
        }
    }

    private function lookupUserID($dn)
    {
        //make sure user DN exists and active
        $sql = "select p.*
                    from oim.certificate_dn c left join oim.person p on
                        (c.person_id = p.person_id)
                    where 
                        c.active = 1 and 
                        c.disable = 0 and 
                        dn_string = '$dn'";
        $row = db()->fetchRow($sql);
        if($row) {
            $this->person_id = $row->person_id;
            $this->person_fullname = $row->first_name." ".$row->last_name;
            $this->person_firstname = $row->first_name;
            $this->person_lastname = $row->last_name;
            $this->person_email = $row->primary_email;
            $this->person_phone = $row->primary_phone;
        }
    } 

    private function lookupRoles($person_id)
    { 
        //lookup auth_types that are associated with this person
        $sql = "select
            d.auth_type_id as auth_type_id
            from
                oim.certificate_dn c left join oim.dn_auth_type d on
                    (c.dn_id = d.dn_id)
            where
                d.active = 1 and
                c.active = 1 and
                c.disable = 0 and
                c.person_id = $person_id";
        $auth_types = db()->fetchAll($sql);
        //and add roles to roles list
        foreach($auth_types as $auth_type) {
            //merge new role sets
            $roles = config()->auth_metrics[$auth_type->auth_type_id];
            //dlog("aypt type:".print_r($roles, true));
            foreach($roles as $role) {
                if(!in_array($role, $this->roles)) {
                    $this->roles[] = $role;
                    //dlog("User Roles: ".print_r($this->roles, true));
                }
            }
        }
    }

    public function getRoles()
    {
        return $this->roles;
    }
    public function hasRole($role)
    {
        return in_array($role, $this->roles);
    }
    public function isGuest() { return $this->guest; }
    public function getPersonID() { return $this->person_id; }
    public function getPersonFullName() { return $this->person_fullname; }
    public function getPersonFirstName() { return $this->person_firstname; }
    public function getPersonLastName() { return $this->person_lastname; }
    public function getPersonEmail() { return $this->person_email; }
    public function getPersonPhone() { return $this->person_phone; }
    public function getDN() { return $this->dn; }
}
