<?
class Data
{
    public function logAccess()
    {
        $data = array(
            'application'      => config()->app_id,
            'server' => php_uname('n'),
            'detail'      => $_SERVER["REQUEST_URI"],
            'ip'      => $_SERVER["REMOTE_ADDR"],
            'dn'      => user()->dn,
            'timestamp'      => time()
        );

        db("data")->insert('log.access', $data);
    }

    //returns one of following
    //1. string with non-0 size
    //2. string with 0 size (value is set to empty string)
    //3. null (value is set to null)
    //4. bool(false) v(value is not set at all)
    public function getMetadata($ticketid, $key)
    {
        $sql = "SELECT value FROM gocticket.metadata WHERE ticket_id = $ticketid and `key` = '$key' and project_id = ".config()->project_id; 
        return db("data")->fetchOne($sql);
    }
    public function getAllMetadata($ticketid)
    {
        $sql = "SELECT `key`, value FROM gocticket.metadata WHERE ticket_id = $ticketid and project_id = ".config()->project_id; 
        return db("data")->fetchAll($sql);
    }
    public function getAllMetadataForKey($ids, $key) {
        $sql = "SELECT ticket_id, value FROM gocticket.metadata WHERE ticket_id in (".implode(",", $ids).") and `key` = '$key' and project_id = ".config()->project_id; 
        return db("data")->fetchAll($sql);
    }

    public function setMetadata($ticketid, $key, $value)
    {
        $old_value = $this->getMetadata($ticketid, $key);

        if($value === null) {
            $value = "NULL";
        } else {
            $value = "'$value'";
        }

        if($old_value === false) {
            $sql = "INSERT INTO gocticket.metadata (ticket_id, `key`, value, project_id) VALUES ($ticketid, '$key', $value, ".config()->project_id.")";
        } else {
            $sql = "UPDATE gocticket.metadata SET value = $value WHERE ticket_id = $ticketid and `key` = '$key' and project_id = ".config()->project_id; 
        }
        slog("executing: ".$sql);
        db("data")->query($sql);
    }
}
