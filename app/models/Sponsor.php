<?

class Sponsor
{
    public function fetchAll()
    {
        $sql = "select p.* from facility_contact f join contact p on f.contact_id = p.id";
        return db2()->fetchAll($sql);
    }
}

?>
