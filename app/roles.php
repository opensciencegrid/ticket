<?

class authtype
{
    //auth_type_id - must match oim.authorization_type
    public static $auth_guest = 0;
    public static $auth_end_user = 1;
    public static $auth_osg_staff = 2;
    public static $auth_osg_security = 3;
    public static $auth_osg_goc = 4;
    public static $auth_osg_gocticket_itb_editor = 5;
    public static $auth_osg_gocticket_editor = 6;
    public static $auth_osg_gocticket_storage_editor = 7;
}

class role
{
    public static $test = 0; //role to debug authorization issues
    public static $goc_admin = 1; //view admin email address and able to open OS email client.
    public static $see_security_ticket = 2;
    public static $security_admin = 3;
    public static $itb_admin = 4; //view admin email address and able to open OS email client.
}

//role / authtype matrix is in app/config.php
