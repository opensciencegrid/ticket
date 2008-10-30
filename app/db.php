<?

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

    if(config()->debug) {
        $db->getProfiler()->setEnabled(true);
    }

    $g_db = $db;
}
function db() { global $g_db; return $g_db; }

function log_db_profile()
{
    $profiler = db()->getProfiler();

    $totalTime    = $profiler->getTotalElapsedSecs();
    $queryCount   = $profiler->getTotalNumQueries();
    $longestTime  = 0;
    $longestQuery = null;

    dlog('----------------------------------------------------------------------');
    dlog('DB PROFILE');
    dlog('Executed ' . $queryCount . ' queries in ' . $totalTime . ' seconds');

    $profiles = $profiler->getQueryProfiles();
    if(is_array($profiles)) {
        foreach ($profiles as $query) {
            if ($query->getElapsedSecs() > $longestTime) {
                $longestTime  = $query->getElapsedSecs();
                $longestQuery = $query->getQuery();
            }
            dlog("Executed Query: (in ".$query->getElapsedSecs().")");
            dlog($query->getQuery());
        }
    }

    dlog('----------------------------------------------------------------------');
}
