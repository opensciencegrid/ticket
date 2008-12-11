<?

class RSSFeed
{
    public function insert($subject, $ticket_id, $description, $body)
    {
        $ticket_id = (int)$ticket_id;

        //Zend_DB takes care of quoting..
        $row = array(
            'title' => $subject,
            'ticket' => $ticket_id,
            'description' => $description,
            'date' => time(),
            'body' => $body
        );
        return db()->insert("rsvextra.rss_article", $row);
    }
}
