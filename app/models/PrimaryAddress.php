<?
class PrimaryAddress
{
    public function get($table)
    {
        $sql = "SELECT primary_email FROM rsvextra.$table";
        return db()->fetchAll($sql);
    }
    public function get_resource_security()
    {
        return $this->get("View_PrimaryResourceSecurityContactsAll");
    }
    public function get_vo_security()
    {
        return $this->get("View_PrimaryVoSecurityContactsAll");
    }
    public function get_sc_security()
    {
        return $this->get("View_PrimaryScSecurityContactsAll");
    }
    public function get_sc()
    {
        return $this->get("View_PrimaryNotificationContactsSc");
    }
}

