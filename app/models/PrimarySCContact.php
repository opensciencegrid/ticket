<?

class PrimarySCContact
{
    public function fetch($sc_id)
    {
        $sql = "SELECT * FROM oim.sc_contact r join oim.person p on r.person_id = p.person_id where type_id = 4 and rank_id = 1 and sc_id = $sc_id";
        return db()->fetchRow($sql);
    }
}

?>
