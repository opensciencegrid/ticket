<?

class TestController extends Zend_Controller_Action 
{ 
    public function indexAction() 
    { 
        echo "testing something..";

        $model = new Resource();
        var_dump($model->getPrimaryOwnerVO(66));

        $this->render("none", null, true);
    }
} 
