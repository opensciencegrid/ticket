<?

class OpensearchController extends Zend_Controller_Action 
{ 
    public function idAction() 
    { 
        echo "<?xml version=\"1.0\"?>";
        ?>
<OpenSearchDescription xmlns="http://a9.com/-/spec/opensearch/1.1/" xmlns:moz="http://www.mozilla.org/2006/browser/search/">
<ShortName>GOC Ticket (by ID)</ShortName>
<Description>Open GOC Ticket by Ticket ID</Description>
<InputEncoding>inputEncoding</InputEncoding>
<Image width="16" height="16"><?=fullbase()?>/images/tea.png</Image>
<Url type="text/html" method="get" template="<?=fullbase()?>/viewer?id={searchTerms}"/>
</OpenSearchDescription>
    <?

        $this->render("none", null, true);
    }
} 
