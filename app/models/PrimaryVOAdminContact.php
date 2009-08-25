<?

class PrimaryVOAdminContact
{
    public function fetch($vo_id)
    {
        $sql = "select * from contact where id = (SELECT contact_id from vo_contact where contact_type_id = 3 and contact_rank_id = 1 and vo_id = $vo_id) and disable = 0";
        return db2()->fetchRow($sql);
    }
}

?>
