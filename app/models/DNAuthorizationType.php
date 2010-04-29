<?

class DNAuthorizationType
{
    public function fetchAllByDNID($dn_id)
    {
        $sql = "select * from dn_authorization_type where dn_id = $dn_id";
        return db("oim")->fetchAll($sql);
    }
}
