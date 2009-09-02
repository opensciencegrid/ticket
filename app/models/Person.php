<?

class Person
{
    public function fetchPerson($person_id)
    {
        $sql = "SELECT * FROM contact WHERE id = $person_id";
        return db2()->fetchRow($sql);
    }
    public function fetchGOC()
    {
        $sql = "SELECT * FROM contact WHERE id IN (SELECT contact_id from dn WHERE id IN (SELECT dn_id FROM dn_authorization_type WHERE authorization_type_id = 4)) AND disable IS FALSE";
        return db2()->fetchAll($sql);
    }
    public function fetchAll()
    {
        $sql = "SELECT * FROM contact WHERE disable IS FALSE ORDER BY name";
        return db2()->fetchAll($sql);
    }
}

?>
