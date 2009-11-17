<?

class GOC
{
    public function logAccess()
    {

        $data = array(
            'application'      => config()->app_id,
            'server' => $_SERVER["SERVER_NAME"],
            'detail'      => $_SERVER["REQUEST_URI"],
            'ip'      => $_SERVER["REMOTE_ADDR"],
            'dn'      => user()->dn,
            'timestamp'      => time()
        );

        gocdb()->insert('access', $data);
    }
}

?>
