<?

class Override
{
    public function get()
    {
        $override = array();

        $sql = "select * from gocticket.assignment_override";
        foreach(db("data")->fetchAll($sql) as $row) {
            $override[$row->from] = $row->to;
        }
        return $override;
    }

    public function set($overrides) 
    {
        //clear all records
        $sql = "truncate table gocticket.assignment_override";
        db("data")->exec($sql);
        
        //insert all
        if(count($overrides) > 0) {
            $sql = "INSERT INTO `gocticket`.`assignment_override` (`from` ,`to`) VALUES ";
            $first = true;
            foreach($overrides as $from=>$to) {
                if(!$first) {
                    $sql .= ",";
                } else {
                    $first = false;
                }
                $sql .= " ('$from', '$to')";
            }
            $sql .= ";";
            db("data")->exec($sql);
        }
    }

    public function apply($id) {
        $overrides = $this->get();
        if(isset($overrides[$id])) {
            return $overrides[$id];
        }
        //no override for this id
        return $id;
    }
}

?>
