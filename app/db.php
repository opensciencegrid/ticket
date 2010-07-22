<?

$g_db = array();

function connect($name, $db_type, $params) {
    global $g_db;

    //try to connect one of connection parameter that works..
    $exceptions = array();
    foreach($params as $param) {
        try {
            $db = Zend_Db::factory($db_type, $param);
            $db->setFetchMode(Zend_Db::FETCH_OBJ);
            $db->getConnection();

            //profile db via firebug
            if(config()->debug) {
                $profiler = new Zend_Db_Profiler_Firebug('All DB Queries');
                $profiler->setEnabled(true);
                $db->setProfiler($profiler);
            }

            //slog("success $name");
            $g_db[$name] = $db;
            return;
        } catch (Zend_Db_Adapter_Exception $e) {
            // perhaps a failed login credential, or perhaps the RDBMS is not running
            $host = $param["host"];
            wlog("Couldn't connect to $name($host) (trying another connection - if available):: ".$e->getMessage());
            $exceptions[] = $e;
        } catch (Zend_Exception $e) {
            // perhaps factory() failed to load the specified Adapter class
            $host = $param["host"];
            wlog("Couldn't connect to $name($host) (trying another connection - if available):: ".$e->getMessage());
            $exceptions[] = $e;
        }
    }
    $msg = "";
    foreach($exceptions as $e) {
        $msg .= $e->getMessage()."\n";
    }
    throw new Exception("Failed to connect to $name");
}

function db($name) { 
    global $g_db; 
    if(!isset($g_db[$name])) { 
        connect($name, config()->db_type, config()->db_params[$name]);
    }
    return $g_db[$name]; 
}

/*
function gocdb() { 
    global $g_db; 
    $name = "data";
    if(!isset($g_db[$name])) { 
        connect($name, config()->db_type, config()->data_db_params);
    }
    return $g_db[$name]; 
}

function txdb() { 
    global $g_db; 
    $name = "tx";
    if(!isset($g_db[$name])) { 
        connect($name, config()->db_type, config()->tx_db_params);
    }
    return $g_db[$name]; 
}
*/
