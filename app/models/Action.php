<?

class Action
{
    public function fetchAll()
    {
        $sql = "select * from action";
        return db("oim")->fetchAll($sql);
    }
}
