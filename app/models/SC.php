<?

class SC
{
    public function sql()
    {
        return "select * from supportcenter where active = 1 and disable = 0";
    }
    public function fetchAll()
    {
        $sql = $this->sql()." order by short_name";
        $vos = db()->fetchAll($sql);
        return $vos;
    }
    public function get($scid) 
    {
        $sql = $this->sql()." and sc_id = $scid";
        return db()->fetchRow($sql);
    }
    
}

?>
