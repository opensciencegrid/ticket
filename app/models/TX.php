<?

class TX
{
    public function getLinks($id)
    {
        $links = array();

        $sql = "select * from tx.sync where source_id = $id and tx_id like 'fp%'";
        foreach(db("data")->fetchAll($sql) as $row) {
            $txid = $row->tx_id;
            $links[$txid] = $row->dest_id;
        }
        return $links;
    }
}

?>
