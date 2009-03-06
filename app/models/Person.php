<?

class Person
{
    public function fetchPerson($person_id)
    {
        $sql = "SELECT * from oim.person where person_id = $person_id";
        return db()->fetchRow($sql);
    }
    public function fetchAll()
    {
        $sql = "SELECT * from oim.person order by first_name";
        return db()->fetchAll($sql);
    }
}

?>
