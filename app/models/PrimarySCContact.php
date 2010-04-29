<?

class PrimarySCContact
{
    public function fetch($sc_id)
    {
        $sql = "SELECT * FROM contact WHERE id = (SELECT contact_id FROM sc_contact WHERE contact_type_id = 4 AND contact_rank_id = 1 AND sc_id = $sc_id) AND disable IS FALSE";
        return db("oim")->fetchRow($sql);
    }
}

?>
