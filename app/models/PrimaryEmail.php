<?

class PrimaryEmail
{
    public function fetchAll($auth_type_id = 4)
    {
        $sql = "SELECT p.person_id, primary_email, first_name, last_name FROM oim.dn_auth_type d join oim.person p on d.person_id = p.person_id where auth_type_id = $auth_type_id";
        return db()->fetchAll($sql);
    }
}

?>
