<?
class PrimaryAddress
{
    public function get_resource_security()
    {
        $sql = "SELECT * from contact where id in (select contact_id from resource_contact where contact_type_id = 2 and contact_rank_id = 1) and disable = 0 ";
        return db2()->fetchAll($sql);
    }
    public function get_vo_security()
    {
        $sql = "SELECT * from contact where id in (select contact_id from vo_contact where contact_type_id = 2 and contact_rank_id = 1) and disable = 0 ";
        return db2()->fetchAll($sql);
    }
    public function get_sc_security()
    {
        $sql = "SELECT * from contact where id in (select contact_id from sc_contact where contact_type_id = 2 and contact_rank_id = 1) and disable = 0 ";
        return db2()->fetchAll($sql);
    }
    public function get_sc()
    {
        $sql = "select * from contact where id in (
  select `SCC`.`contact_id` from `sc_contact` `SCC`
  join `sc` `SC` on `SCC`.`sc_id` = `SC`.`id`
  where
    `SCC`.`contact_rank_id` = 1 and
    `SCC`.`contact_type_id` = 7 and
    `SC`.`active` = 1 and
    `SC`.`disable` <> 1
)";
        return db2()->fetchAll($sql);
    }

}

