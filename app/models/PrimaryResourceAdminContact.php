<?

class PrimaryResourceAdminContact
{
    public function fetch($resource_id)
    {
        $sql = "select * from contact where id = (SELECT contact_id from resource_contact where contact_type_id = 3 and contact_rank_id = 1 and resource_id = $resource_id) and disable = 0";
        return db("oim")->fetchRow($sql);
    }
}

?>
