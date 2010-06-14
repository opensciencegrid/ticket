<?

class ResourceGroup
{
    public function fetchByID($rgid)
    {
        $sql = "SELECT * FROM resource_group WHERE disable = 0 and id = $rgid";
        return db("oim")->fetchRow($sql);
    }
    }

?>
