<?

/*
$g_db = null;
function connect_db()
{
    global $g_db;

    $db = Zend_Db::factory(config()->db_type, array(
        'host'     => config()->db_host,
        'username' => config()->db_username,
        'password' => config()->db_password,
        'dbname'   => config()->db_schema,
        'port'     => config()->db_port
    ));

    $db->setFetchMode(Zend_Db::FETCH_OBJ);

    //profile db via firebug
    if(config()->debug) {
        //$db->getProfiler()->setEnabled(true);
        $profiler = new Zend_Db_Profiler_Firebug('All DB Queries');
        $profiler->setEnabled(true);
        $db->setProfiler($profiler);
    }

    $g_db = $db;
}

function db() { 
    global $g_db; 
    if($g_db == null) {
        connect_db();
    }
    return $g_db; 
}
*/

$g_db2 = null;
function connect_db2()
{
    global $g_db2;

    $db = Zend_Db::factory(config()->db_type, array(
        'host'     => config()->db_host,
        'username' => config()->db_username,
        'password' => config()->db_password,
        'dbname'   => config()->db_schema2,
        'port'     => config()->db_port
    ));

    $db->setFetchMode(Zend_Db::FETCH_OBJ);

    //profile db via firebug
    if(config()->debug) {
        //$db->getProfiler()->setEnabled(true);
        $profiler = new Zend_Db_Profiler_Firebug('All DB Queries');
        $profiler->setEnabled(true);
        $db->setProfiler($profiler);
    }

    $g_db2 = $db;
}

function db2() { 
    global $g_db2; 
    if($g_db2 == null) {
        connect_db2();
    }
    return $g_db2; 
}

