<?

class Person
{
    public function fetchPerson($person_id)
    {
        $sql = "SELECT * from oim.person where person_id = $person_id";
        return db()->fetchRow($sql);
    }
}

?>
