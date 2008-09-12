<?

class VO
{
    public function fetchAll()
    {
        $sql = "select * from virtualorganization where active = 1 and disable = 0 order by short_name";
        $vos = db()->fetchAll($sql);
        return $vos;
    }
}

?>
