<?

class SC
{
    public function sql()
    {
        return "select * from sc where active = 1 and disable = 0";
    }
    public function fetchAll()
    {
        $sql = $this->sql()." order by name";
        $scs = db("oim")->fetchAll($sql);
        return $scs;
    }
    public function get($scid) 
    {
        $sql = $this->sql()." and id = $scid";
        return db("oim")->fetchRow($sql);
    }
    
}

?>
