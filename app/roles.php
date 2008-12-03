<?

class authtype
{
    //auth_type_id - must match oim.authorization_type
    public static $auth_guest = 0;
    public static $auth_end_user = 1;
    public static $auth_osg_staff = 2;
    public static $auth_osg_security = 3;
    public static $auth_osg_goc = 4;
}

class role
{
    //role to debug authorization issues
    public static $test = 0;

    //view admin email address and able to open OS email client.
    public static $goc_admin = 1;
}
