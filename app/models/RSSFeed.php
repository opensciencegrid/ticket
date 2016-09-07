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

	$client = new Google_Client();
  	$client->setAuthConfigFile('/usr/local/ticket/client_secrets.json');
	$client->addScope('https://www.googleapis.com/auth/blogger');
	$client->setApplicationName("OSG Blog");
	$service = new Google_Service_Blogger($client);
	if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
  		$client->setAccessToken($_SESSION['access_token']);
		 slog("oauth access token already set");
		 $mypost= new Google_Service_Blogger_Post();
                $mypost->setTitle($subject);
                $htmlbody = nl2br(str_replace('  ', ' &nbsp;', htmlspecialchars($body)));
                $mypost->setContent($htmlbody);
                
                $service->posts->insert($blogid, $mypost);
                slog("Created new blogger post ");
       } 
	else {
                slog("creating oauth access token in RSS Feed");
                $redirect_uri = 'https://' . $_SERVER['HTTP_HOST'] . '/oauth';
                header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
	}

    }
}
