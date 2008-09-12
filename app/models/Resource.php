<?

class Resource
{
    public function fetchAll()
    {
        $sql = "select * from resource where active = 1 and disable = 0 order by name";
        $vos = db()->fetchAll($sql);
        return $vos;
    }
    public function fetchName($resource_id)
    {
        $sql = "select name from resource where active = 1 and disable = 0 and resource_id = $resource_id";
        return db()->fetchOne($sql);
    }
}

?>
