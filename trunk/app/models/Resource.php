<?

class Resource
{
    public function fetchAll($grid_type = null)
    {
        $grid_type_where = "";
        if($grid_type !== null) {
            $resource_groups = "select id from resource_group RG where osg_grid_type_id = $grid_type";
            $resource_ids = "select id from resource RRG where resource_group_id IN ($resource_groups)";
            $grid_type_where = "and id IN ($resource_ids)";
        }

        $sql = "select *, r.id as resource_id from resource r where active = 1 and disable = 0 $grid_type_where order by name";
        return db2()->fetchAll($sql);
    }
    public function fetchName($resource_id)
    {
        $sql = "select name from resource where active = 1 and disable = 0 and id = $resource_id";
        return db2()->fetchOne($sql);
    }
    public function fetchID($resource_name)
    {
        $sql = "select id from resource where name = '$resource_name'";
        return db2()->fetchOne($sql);
    }
    public function getPrimaryOwnerVO($resource_id) 
    {
        $sql = "SELECT R.id, R.name, vo.name as vo_name, vo.footprints_id,
MAX(v.percent) AS ownership_percent
FROM vo_resource_ownership v
  RIGHT JOIN resource R ON R.id=v.resource_id
  LEFT JOIN vo vo ON v.vo_id=vo.id
where R.id = $resource_id
GROUP BY R.name";
        return db2()->fetchRow($sql);
    }
}

?>
