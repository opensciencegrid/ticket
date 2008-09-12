<?

class GridType
{
    public function fetchAll()
    {
        $sql = "select * from osg_grid_type order by short_name";
        return db()->fetchAll($sql);
    }
}

?>
