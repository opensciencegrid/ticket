<?

class PrimaryResourceAdminContact
{
    public function fetch($resource_id)
    {
        $sql = "select * from rsvextra.View_PrimaryResourceAdminContactsAll where resource_id = $resource_id";
        return db()->fetchRow($sql);
    }
}

?>
