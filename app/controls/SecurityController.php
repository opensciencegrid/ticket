<?

include("lib/MyFormDecoratorCaptcha.php");

class SecurityController extends BaseController
{ 
    public function init()
    {
        $this->view->submenu_selected = "open";
    }
    public function indexAction() 
    { 
        $this->view->form = $this->getForm();
        $this->render();
    }

    public function submitAction()
    {
        $form = $this->getForm();
        if($form->isValid($_POST)) {
            $footprint = $this->initSubmit($form);
            $footprint->addDescription($form->getValue('detail'));
            $footprint->setPriority(1); //set it to critical
            $footprint->addAssignee("rquick", true);//security ticket is assigned to rob
            $footprint->setTicketType("Security");
            $footprint->setTitle("Security Issue");

            try 
            {
                $mrid = $footprint->submit();
                $this->view->mrid = $mrid;
                $this->render("success", null, true);
            } catch(exception $e) {
                $this->sendErrorEmail($e);
                $this->render("failed", null, true);
            }
        } else {
            $this->view->errors = "Please correct following issues.";
            $this->view->form = $form;
            $this->render("index");
        }
    }

    private function getForm()
    {
        $form = $this->initForm("security");

        $detail = new Zend_Form_Element_Textarea('detail');
        $detail->setRequired(true);
        $form->addElement($detail);

        //output captcha element
        $validatorNotEmpty = new Zend_Validate_NotEmpty();
        $validatorNotEmpty->setMessage('This field is required, you cannot leave it empty');
        $captcha = new Zend_Form_Element_Text('captcha');
        $captcha->setLabel('Type the characters you see in the picture above')
            ->addValidator(new Zend_Validate_Identical($this->getCaptchaCode()))
            ->addValidator($validatorNotEmpty, true)->setRequired(true);
        $captchaDecorator = new My_Form_Decorator_Captcha();
        $captchaDecorator->setTag('div');
        $captcha->addDecorator($captchaDecorator);
        $form->addElement($captcha);

        $submit = new Zend_Form_Element_Submit('submit_button');
        $submit->setLabel("Submit");
        $form->addElement($submit);

        return $form;
    }
} 
