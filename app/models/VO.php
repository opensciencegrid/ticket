<?

class VO
{
    public function sql()
    {
        return "select * from vo where active = 1 and disable = 0";
    }
    public function fetchAll()
    {
        $sql = $this->sql()." order by name";
        $vos = db("oim")->fetchAll($sql);
        return $vos;
    }
    public function get($void) 
    {
        $sql = $this->sql()." and id = $void";
        return db("oim")->fetchRow($sql);
    }
    public function getfromsc($sc_id) {
        $sql = $this->sql()." and sc_id = $sc_id";
        return db("oim")->fetchAll($sql);
    }
}

?>
