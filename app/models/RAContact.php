<?

class RAContact
{
    public function fetchAll()
    {
        $sql = "select p.* from ra_contact f join contact p on f.contact_id = p.id";
        return db2()->fetchAll($sql);
    }
}

?>
