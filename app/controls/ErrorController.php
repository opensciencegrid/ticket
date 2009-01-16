<?php

class ErrorController extends Zend_Controller_Action 
{ 
    public function errorAction()
    { 
        $errors = $this->_getParam('error_handler');

        switch ($errors->type) {
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
                // 404 error -- controller or action not found
                $this->getResponse()->setRawHeader('HTTP/1.1 404 Not Found');
                $this->render('404');
                break;
            default:
                //application error !!
                $exception = $errors->exception;
                $log = "";
                $log .= "Error Message ---------------------------------------\n";
                $log .= $exception->getMessage()."\n\n";

                $log .= "Stack Trace -----------------------------------------\n";
                $log .= $exception->getTraceAsString()."\n\n";

                $log .= dump_db_profile()."\n\n";

                $log .= "Server Parameter ------------------------------------\n";
                $log .= print_r($_SERVER, true)."\n\n";

                if(config()->debug) {
                    $this->view->content = "<pre>".$log."</pre>";
                } else {
                    $this->view->content = "Encountered an application error.\n\n";
                    if(config()->elog_email) {
                        $user = $_ENV["USER"];
                        mail(config()->elog_email_address, "[myosg] error has occurerd", $log, "From: $user");
                        $this->view->content .= "Detail of this error has been sent to the development team for further analysis.";
                    }
                }

                elog($log);
                break;
        }
    } 
} 
