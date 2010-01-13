<?

class RAContact
{
    public function fetchAll()
    {
        $sql = "SELECT * FROM contact WHERE disable IS FALSE AND id IN (select contact_id from dn where dn_string like '/DC=org/DC=doegrids/OU=People%')";
        return db2()->fetchAll($sql);
    }
}

?>
