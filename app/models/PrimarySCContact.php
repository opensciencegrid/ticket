<?

class PrimarySCContact
{
    public function fetch($sc_id)
    {
      $sql = "SELECT * FROM contact WHERE id = (SELECT contact_id FROM sc_contact WHERE contact_type_id = 4 AND contact_rank_id = 1 AND sc_id = $sc_id) AND disable IS FALSE";
	//        //$sql = "SELECT * FROM oim.sc_contact r join oim.person p on r.person_id = p.person_id where type_id = 4 and rank_id = 1 and sc_id = $sc_id";
        return db2()->fetchRow($sql);
    }
}

?>
