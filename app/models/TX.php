<?

class TX
{
    public function getLinks($id)
    {
        $links = array();

        $sql = "select * from sync where source_id = $id and tx_id like 'fp%'";
        foreach(txdb()->fetchAll($sql) as $row) {
            $txid = $row->tx_id;
            //$txid = substr($txid, strpos($txid, "_")+1);
            $links[$txid] = $row->dest_id;
        }
        return $links;
    }
}

?>
