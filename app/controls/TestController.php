<?

require_once("app/httpspost.php");

class TestController extends Zend_Controller_Action 
{ 
    public function indexAction() 
    { 
        echo "sending ping:";
        echo https_post("rsv-itb.grid.iu.edu", "/footprint/test/pong", array("msg"=>"hello world. david o'neal. \"<>good"));
        $this->render("none", null, true);
    }
    public function pongAction()
    {
        echo "you have said: ".$_REQUEST["msg"];
        $this->render("none", null, true);
    }
} 
