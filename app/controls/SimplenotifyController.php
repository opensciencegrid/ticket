<?

class SimplenotifyController extends BaseController
{
    public function init()
    {
        user()->check("notify");

        $this->view->page_title = "Security Notification";
        $this->view->menu_selected = "user";
        $this->view->submenu_selected = "simplenotify";
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
            $this->view->detail = "<h2>Process Detail</h2><br/>";

            //construct security email object
            $e = new Email();
            if($form->getValue('rsecurity')) {
                $e->addResourceSecurityAddresses();
            }
            if($form->getValue('vsecurity')) {
                $e->addVOSecurityAddresses();
            }
            if($form->getValue('ssecurity')) {
                $e->addSCSecurityAddresses();
            }
            if($form->getValue('support')) {
                $e->addSupportAddresses();
            }
            if($form->getValue('general')) {
                $e->addAddress("osg-general@opensciencegrid.org");
            }
            if($form->getValue('operations')) {
                $e->addAddress("osg-operations@opensciencegrid.org");
            }
            if($form->getValue('sites')) {
                $e->addAddress("osg-sites@opensciencegrid.org");
            }
            if($form->getValue('vulnerability')) {
                $e->addAddress("OSG-SECURITY-SOFTW-VULNERABILITY@opensciencegrid.org");
            }
            //process CC
             if($_REQUEST["cc"]) {
             	$ccs = $_REQUEST["cc"];
            	foreach($ccs as $cc) {
            		$cc = trim($cc);
            		if($cc != "") {
            			$e->addBCC($cc);
				
            		}
            	}
            }

            $e->setFrom(config()->email_from_security);
            $e->setSubject($form->getValue("subject"));
            $e->setBody($form->getValue('body'));
            $e->setTo('help@opensciencegrid.org');

            if($form->getValue('sign')) {
	        $e->setSign();
	        
	       
	    }

            /*
            //the last minutes debug override
            if(config()->debug) {
                $e->setTo("hayashis@indiana.edu");
                $e->addAddress("hayashis@indiana.edu");
            }
            */

            try
            {
                if(config()->simulate) {
                    echo "<h2>Simulation</h2>";
                    echo $e->dump();
                    $this->render("none", null, true);
                } else {
                    $e->send();

                    $this->view->detail .= "<br/><h3>Following Email has been sent</h3><br/>";
                    $this->view->detail .= $e->dump();
                    $this->render("processed", null, true);
                }
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
        $form = $this->initForm("simplenotify", false);

        $e = new Zend_Form_Element_Checkbox('rsecurity');
        $form->addElement($e);
        $e = new Zend_Form_Element_Checkbox('vsecurity');
        $form->addElement($e);
        $e = new Zend_Form_Element_Checkbox('ssecurity');
        $form->addElement($e);
        $e = new Zend_Form_Element_Checkbox('support');
        $form->addElement($e);
        $e = new Zend_Form_Element_Checkbox('general');
        $form->addElement($e);
        $e = new Zend_Form_Element_Checkbox('sites');
        $form->addElement($e);
        $e = new Zend_Form_Element_Checkbox('vulnerability');
        $form->addElement($e);
        $e = new Zend_Form_Element_Checkbox('operations');
        $form->addElement($e);

        $e = new Zend_Form_Element_Text('subject');
        $e->setLabel("Subject");
        $e->setRequired(true);
        $form->addElement($e);

        $detail = new Zend_Form_Element_Textarea('body');
        //although the FP's db should allow max size of 16M for MEDIUMTEXT
        //the large text could cause submission error sometime.
        //restricting it to some small amount for now (1M)
        $detail->addValidator(new Zend_Validate_StringLength(1, 1024*1024*1)); 
        $detail->setLabel("Content");
        $detail->setRequired(true);
        $form->addElement($detail);

        $e = new Zend_Form_Element_Checkbox('sign');
        $form->addElement($e);
        


        $submit = new Zend_Form_Element_Submit('submit_button');
        $submit->setLabel("   Submit   ");
        $form->addElement($submit);

        return $form;
    }
} 

