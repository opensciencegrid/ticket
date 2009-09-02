<?

class Resource
{
    public function fetchAll($grid_type = null)
    {
        $grid_type_where = "";
        if($grid_type !== null) {
            $resource_groups = "SELECT id FROM resource_group RG WHERE osg_grid_type_id = $grid_type AND disable IS FALSE";
            $resource_ids = "SELECT id FROM resource RRG WHERE resource_group_id IN ($resource_groups) AND disable IS FALSE";
            $grid_type_where = "AND id IN ($resource_ids)";
        }

        $sql = "SELECT *, r.id as resource_id FROM resource r WHERE active = 1 and disable = 0 $grid_type_where order by name";
        return db2()->fetchAll($sql);
    }
    public function fetchName($resource_id)
    {
        $sql = "SELECT name FROM resource WHERE active = 1 and disable IS FALSE and id = $resource_id";
        return db2()->fetchOne($sql);
    }
    public function fetchID($resource_name)
    {
        $sql = "SELECT id FROM resource WHERE name = '$resource_name'";
        return db2()->fetchOne($sql);
    }
    public function getPrimaryOwnerVO($resource_id) 
    {
        $sql = "SELECT R.id, R.name, vo.name as vo_name, vo.footprints_id,
MAX(v.percent) AS ownership_percent
FROM vo_resource_ownership v
  RIGHT JOIN resource R ON R.id=v.resource_id
  LEFT JOIN vo vo ON v.vo_id=vo.id
WHERE R.id = $resource_id
 AND vo.disable IS FALSE
GROUP BY R.name";
        return db2()->fetchRow($sql);
    }
}

?>
