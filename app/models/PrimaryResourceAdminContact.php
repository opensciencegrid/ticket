<?

class PrimaryResourceAdminContact
{
    public function fetch($resource_id)
    {
        $sql = "SELECT * FROM oim.resource_contact r join oim.person p on r.person_id = p.person_id where type_id = 3 and rank_id = 1 and resource_id = $resource_id";
        return db()->fetchRow($sql);
    }
}

?>
