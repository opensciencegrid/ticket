<?

class Resource
{
    public function fetchAll($grid_type = null)
    {
        $grid_type_where = "";
        if($grid_type !== null) {
            $resource_groups = "select resource_group_id from resource_group RG where osg_grid_type_id = $grid_type";
            $resource_ids = "select resource_id from resource_resource_group RRG where resource_group_id IN ($resource_groups)";
            $grid_type_where = "and resource_id IN ($resource_ids)";
        }

        $sql = "select * from resource r where active = 1 and disable = 0 $grid_type_where order by name";
        return db()->fetchAll($sql);
    }
    public function fetchName($resource_id)
    {
        $sql = "select name from resource where active = 1 and disable = 0 and resource_id = $resource_id";
        return db()->fetchOne($sql);
    }
    public function fetchID($resource_name)
    {
        $sql = "select resource_id from resource where name = '$resource_name'";
        return db()->fetchOne($sql);
    }
}

?>
