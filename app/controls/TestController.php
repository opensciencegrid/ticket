<?

class TestController extends Zend_Controller_Action 
{ 
    public function indexAction() 
    { 
        echo "testing something..";

        $model = new Schema();

        echo "<pre>";
        var_dump($model->doget("email"));
        echo "</pre>";

        $this->render("none", null, true);
    }
} 
