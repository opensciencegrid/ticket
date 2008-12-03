<?

class Sponsor
{
    public function fetchAll()
    {
        $sql = "select p.* from facility_contact f join person p on f.person_id = p.person_id";
        return db()->fetchAll($sql);
    }
}

?>
