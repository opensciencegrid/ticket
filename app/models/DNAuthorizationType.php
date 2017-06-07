<?

class DNAuthorizationType
{
    public function fetchAllByDNID($dn_id)
    {
        $sql = "select * from contact_authorization_type_index where contact_authorization_type_id  = $dn_id";
        return db("sso")->fetchAll($sql);
    }
}
