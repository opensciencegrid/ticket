<?
class PrimaryAddress
{
    public function get_sc()
    {
        $sql = "select * from contact where id in (
  select `SCC`.`contact_id` from `sc_contact` `SCC`
  join `sc` `SC` on `SCC`.`sc_id` = `SC`.`id`
  where
    `SCC`.`contact_rank_id` = 1 AND
    `SCC`.`contact_type_id` = 7 AND
    contact.disable IS FALSE AND
    `SC`.`active` IS TRUE AND
    `SC`.`disable` IS FALSE
)";
        return db2()->fetchAll($sql);
    }

}

