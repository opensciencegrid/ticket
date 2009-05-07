<?

class Person
{
    public function fetchPerson($person_id)
    {
        $sql = "SELECT * from contact where id = $person_id";
        return db2()->fetchRow($sql);
    }
    public function fetchGOC()
    {
        $sql = "SELECT * from contact where id in (select contact_id from dn where id in (select dn_id from dn_authorization_type where authorization_type_id = 4))";
        return db2()->fetchAll($sql);
    }
    public function fetchAll()
    {
        $sql = "SELECT * from contact order by name";
        return db2()->fetchAll($sql);
    }
}

?>
