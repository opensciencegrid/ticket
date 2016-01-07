<?

class RSSFeed
{
    public function insert($subject, $ticket_id, $body)
    {
        $ticket_id = (int)$ticket_id;

        /////////////////////////////////////////////////////////////
        //Backup the RSS content
        $row = array(
            'title' => $subject,
            'ticket' => $ticket_id,
            'date' => time(),
            'body' => $body
        );
        slog("RSS Feed to be sent to Blogger");
        slog(print_r($row, true));

        /////////////////////////////////////////////////////////////
        // Insert to osggoc.blogger.com
        $blogid = config()->blogger_blogid;
        $service = "blogger";
        //$client = Zend_Gdata_ClientLogin::getHttpClient(config()->blogger_user, config()->blogger_pass, $service, null,
         //       Zend_Gdata_ClientLogin::DEFAULT_SOURCE, null, null, 
          //      Zend_Gdata_ClientLogin::CLIENTLOGIN_URI, 'GOOGLE');
        //$gdClient = new Zend_Gdata($client); 
		$client_email = '1234567890-a1b2c3d4e5f6g7h8i@developer.gserviceaccount.com';
		$private_key = file_get_contents('MyProject.p12');
		$scopes = array('https://www.googleapis.com/auth/sqlservice.admin');
		$credentials = new Google_Auth_AssertionCredentials(
    		$client_email,
    		$scopes,
    		$private_key
		);

        $uri = "https://www.blogger.com/feeds/$blogid/posts/default";
        $entry = $gdClient->newEntry();
        $entry->title = $gdClient->newTitle(trim($subject));
        $entry->content = $gdClient->newContent($body);
        $entry->content->setType('text');
        $createdPost = $gdClient->insertEntry($entry, $uri);
        $idText = explode('-', $createdPost->id->text);
        $newpostid = $idText[2];

        slog("Created new blogger post with post id of $newpostid");
    }
}
