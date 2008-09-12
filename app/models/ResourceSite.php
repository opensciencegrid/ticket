<?

class ResourceSite
{
    public function fetch($resource_id)
    {
        $sql = "select * from rsvextra.View_resourceSiteScPub where resource_id = $resource_id";
        return db()->fetchRow($sql);
    }
}

?>
