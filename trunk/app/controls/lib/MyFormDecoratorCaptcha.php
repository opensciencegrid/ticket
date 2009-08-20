<?php
 
class My_Form_Decorator_Captcha extends Zend_Form_Decorator_Abstract
{
    protected $_placement = 'PREPEND';
 
    /**
     * HTML tag with which to surround image
     * @var string
     */
    protected $_tag;   
 
    /**
     * Set HTML tag with which to surround image
     * 
     * @param  string $tag 
     * @return My_Form_Decorator_Captcha
     */
    public function setTag($tag)
    {
        $this->_tag = (string) $tag;
        return $this;
    }
 
 
    /**
     * Get HTML tag, if any, with which to surround image
     * 
     * @return void
     */
    public function getTag()
    {
        if (null === $this->_tag) {
            $tag = $this->getOption('tag');
            if (null !== $tag) {
                $this->removeOption('tag');
                $this->setTag($tag);
            }
            return $tag;
        }
 
        return $this->_tag;
    }
 
 
    /**
     * Render a captcha image
     * 
     * @param  string $content 
     * @return string
     */
    public function render($content)
    {
        $element = $this->getElement();
        $view    = $element->getView();
        if (null === $view) {
            return $content;
        }
 
        $tag       = $this->getTag();
        $placement = $this->getPlacement();
        $separator = $this->getSeparator();
 
        $image = '<img src="'.base().'/captcha/get" alt="CAPTCHA challange" />'; 
 
        if (null !== $tag) {
            require_once 'Zend/Form/Decorator/HtmlTag.php';
            $decorator = new Zend_Form_Decorator_HtmlTag();
            $decorator->setOptions(array('tag' => $tag));
            $image = $decorator->render($image);
        }
 
        switch ($placement) {
            case self::PREPEND:
                return $image . $separator . $content;
            case self::APPEND:
            default:
                return $content . $separator . $image;
        }
    }
}
