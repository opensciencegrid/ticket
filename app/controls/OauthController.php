<?

class OauthController extends Zend_Controller_Action
{


    public function init() {
        header('Content-type: text/xml');

}
    public function indexAction()
   {
	slog("index action");
  }
}
