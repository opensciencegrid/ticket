<?
class Address
{
    public function get_resource_security()
    {
        $sql = "SELECT * from contact where id in (select contact_id from resource_contact where contact_type_id = 2 AND resource_id IN (SELECT id FROM resource WHERE disable IS FALSE)) and disable = 0 ";
        return db("oim")->fetchAll($sql);
    }
    public function get_vo_security()
    {
        $sql = "SELECT * from contact where id in (select contact_id from vo_contact where contact_type_id = 2 AND vo_id IN (SELECT id FROM vo WHERE disable IS FALSE)) and disable = 0 ";
        return db("oim")->fetchAll($sql);
    }
    public function get_sc_security()
    {
        $sql = "SELECT * from contact where id in (select contact_id from sc_contact where contact_type_id = 2 AND sc_id IN (SELECT id FROM sc WHERE disable IS FALSE)) and disable = 0 ";
        return db("oim")->fetchAll($sql);
    }
}

