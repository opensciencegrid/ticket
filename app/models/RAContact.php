<?

class RAContact
{
    public function fetchAll()
    {
        $sql = "SELECT p.* FROM ra_contact f JOIN contact p ON (f.contact_id = p.id) WHERE p.disable IS FALSE";
        return db2()->fetchAll($sql);
    }
}

?>
