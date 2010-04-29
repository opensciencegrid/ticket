<?
class ResourceSite
{
    public function fetchSCID($resource_id)
    {
        $sql = "SELECT sc_id FROM site WHERE id = (select site_id from resource_group where id = (select resource_group_id from resource where id = $resource_id))";
        return db("oim")->fetchOne($sql);
    }
}

