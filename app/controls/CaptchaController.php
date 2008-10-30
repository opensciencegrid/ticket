<?php
 
class CaptchaController extends Zend_Controller_Action 
{
    public function init()
    {   
        //$this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();            
    }
 
    public function getAction()
    {
        $session = new Zend_Session_Namespace('captcha');

        $width = 150;
        $height = 40; 
 
        $image = ImageCreate($width, $height);
 
        if (isset($session->registerCaptcha)) {
            $text = $session->registerCaptcha;
 
            if ($text)
            {
                $back = ImageColorAllocate($image, 255, 255, 255);
                imagefill($image, 0,0, $back);
                $black = ImageColorAllocate($image, 0, 0, 0);
                ImageString($image, 20, 50, 15, $text, $black);
            }            
        }
 
        $this->_response->setHeader('Content-Type', 'image/gif');        
        imagegif($image);        
    }
}
