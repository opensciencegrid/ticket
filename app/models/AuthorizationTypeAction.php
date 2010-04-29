<?

class AuthorizationTypeAction
{
    public function fetchAllByAuthTypeID($authid)
    {
        $sql = "select * from authorization_type_action where authorization_type_id = $authid";
        return db("oim")->fetchAll($sql);
    }
}
