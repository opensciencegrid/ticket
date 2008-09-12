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
                $this->view->content = "Encountered an application error.\n\nDetail of this error has been sent to the development team for further analysis.";

                //send error log
                $exception = $errors->exception;
                $log = $exception->getMessage()."\n\n";
                $log  .= $exception->getTraceAsString();
                elog($log);

                break;
        }
    } 
} 
