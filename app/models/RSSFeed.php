<?

class RSSFeed
{
    public function insert($subject, $ticket_id, $body)
    {
        $ticket_id = (int)$ticket_id;

        /////////////////////////////////////////////////////////////
        // Insert to rsvextra.rss_article (for backup purpose)
        //Zend_DB takes care of quoting..
        $row = array(
            'title' => $subject,
            'ticket' => $ticket_id,
            'date' => time(),
            'body' => $body
        );
        db()->insert("rsvextra.rss_article", $row);

        /////////////////////////////////////////////////////////////
        // Insert to osggoc.blogger.com
        $blogid = config()->blogger_blogid;
        $service = "blogger";
        $client = Zend_Gdata_ClientLogin::getHttpClient(config()->blogger_user, config()->blogger_pass, $service, null,
                Zend_Gdata_ClientLogin::DEFAULT_SOURCE, null, null, 
                Zend_Gdata_ClientLogin::CLIENTLOGIN_URI, 'GOOGLE');
        $gdClient = new Zend_Gdata($client); 

        $uri = "https://www.blogger.com/feeds/$blogid/posts/default";
        $entry = $gdClient->newEntry();
        $entry->title = $gdClient->newTitle(trim($subject));
        $entry->content = $gdClient->newContent($body);
        $entry->content->setType('text');
        $createdPost = $gdClient->insertEntry($entry, $uri);
        $idText = split('-', $createdPost->id->text);
        $newpostid = $idText[2];

        slog("Created new blogger post with post id of $newpostid");
    }
}
