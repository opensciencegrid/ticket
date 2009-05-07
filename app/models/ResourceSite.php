<?
class ResourceSite
{
    public function fetchSCID($resource_id)
    {
        $sql = "select sc_id from site where id = (select site_id from resource_group where id = (select resource_group_id from resource where id = $resource_id))";
        return db2()->fetchOne($sql);
    }
}

