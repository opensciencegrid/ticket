<?

class GOC
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

        db("data")->insert('access', $data);
    }
}

?>
