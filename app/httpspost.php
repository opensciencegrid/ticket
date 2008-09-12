<?

//$host = "www.someserver.com"
//$path = "path/to/somewhere.php"
function https_post($host, $path, $values)
{
    $headers = '';
    $content = '';
    $flag = false;
    //$post_query = 'a=1&b=2'; // name-value pairs
    //$post_query = urlencode($post_query) . "\r\n";

    //convert values to paramdata
    $data = "";
    foreach($values as $key=>$value) {
        if($data != "") $data .= "&";
        $data .= $key."=".urlencode($value); 
    }

    $fp = fsockopen("ssl://".$host, '443');
    stream_set_timeout($fp, 2);
    if ($fp) {
        fputs($fp, "POST $path HTTP/1.0\r\n");
        fputs($fp, "Host: rsv-itb.grid.iu.edu\r\n");
        //fputs($fp, "Content-Type: application/x-www-form-urlencoded\r\n");
        fputs($fp, "Content-length: ". strlen($data) ."\r\n");
        fputs($fp, "\r\n");
        fputs($fp, $data);
        dlog($data);
        while (!feof($fp)) {
            $line = fgets($fp, 10240);
            if ($flag) {
                $content .= $line;
            } else {
                $headers .= $line;
                if (strlen(trim($line)) == 0) {
                    $flag = true;
                }
            }
        }
        fclose($fp);
    }
/*
    //prepare
    $method = "POST";
    $contenttype = "text/html";
    $post_query = 'a=1&b=2'; // name-value pairs
    $data= urlencode($post_query) . "\r\n";
    $data = "<something>";

    //send request
    $fp = fsockopen("ssl://".$sslhost, 443);
    fputs($fp, "$method $path HTTP/1.1\r\n");
    fputs($fp, "Host: $host\r\n");
    fputs($fp, "Content-type: $contenttype\r\n");
    fputs($fp, "Content-length: ".strlen($data)."\r\n");
    fputs($fp, "Connection: close\r\n");
    fputs($fp, $data);
    fputs($fp, "\r\n");

    //receive response
    $headers = '';
    $content = '';
    $flag = false;
    $counter = 0;
    while (!feof($fp)) {
        $line = fgets($fp, 10240);
        if ($flag) {
            $content .= $line;
        } else {
            $headers .= $line;
            if (strlen(trim($line)) == 0) {
                $flag = true;
            }
        }
        $counter++;
        if($counter > 100) break;
    }
    fclose($fp);
*/

    //return "\nheader:\n".$headers."content:\n".$content;
    return $content;
}
