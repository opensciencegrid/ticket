<?

class GridType
{
    public function fetchAll()
    {
        $sql = "select * from osg_grid_type order by name";
        return db2()->fetchAll($sql);
    }
}

