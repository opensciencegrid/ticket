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
        $vos = db2()->fetchAll($sql);
        return $vos;
    }
    public function get($scid) 
    {
        $sql = $this->sql()." and id = $scid";
        return db2()->fetchRow($sql);
    }
    
}

?>
