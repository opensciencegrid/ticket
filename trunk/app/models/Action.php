<?

class Action
{
    public function fetchAll()
    {
        $sql = "select * from action";
        return db2()->fetchAll($sql);
    }
}
