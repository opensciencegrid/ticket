<?

$g_db2 = null;
function connect_db2()
{
    global $g_db2;

    $db = Zend_Db::factory(config()->db_type, config()->db_params);
    $db->setFetchMode(Zend_Db::FETCH_OBJ);

    //profile db via firebug
    if(config()->debug) {
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

