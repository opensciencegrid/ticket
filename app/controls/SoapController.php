<?


class SoapController extends Zend_Controller_Action
{
    public function indexAction()
    {
        ini_set("soap.wsdl_cache_enabled", "0"); // disabling WSDL cache
        $server = new SoapServer(fullbase()."/soap/wsdl");

        //authenticate user
        $userdn = $_SERVER["SSL_CLIENT_S_DN"];
        if($userdn != "/DC=org/DC=doegrids/OU=People/CN=Soichi Hayashi 461343") {
            $server->fault(1, "authentication error with userdn specified [$userdn]");
        } else {
            //ok. let's handle the request..
            $server->setClass('SoapAction');
            try {
                $server->handle(); 
            } catch (exception $e) {
                $server->fault(2, $e);
            }
        }
        $this->render("none", null, true);
    }

    public function wsdlAction()
    {
        header("Content-Type:", "text/xml");
        echo php2wsdl::generate("SoapAction", fullbase()."/soap");
        $this->render("none", null, true);
    }
}

class SoapAction
{
/*
    public function getQuote($symbol) 
    {
        return 100.5;
    }
    public function getServerParams()
    {
        return print_r($_SERVER, true);
    }
*/
    public function UpdateFromGGUSXML($xml)
    {
        require_once("lib/ggusticket.php");
        $footprint = ggus2footprint($xml);
        $mrid = $footprint->submit();
        $msg = "GGUS Ticket insert / update success - FP Ticket ID $mrid";
        slog($msg);
        return $msg;
    }
}

class php2wsdl
{
    static function generate($className, $url)
    {
        $messageMethods = '';
        $portTypeOperations = '';
        $bindingOperations = '';
        $class = new ReflectionClass($className);

        $methods = $class->getMethods();
        foreach($methods as $methodKey => $methodValue)
        {
            $messageMethodParts = '';
            $params = $methodValue->getParameters();
            foreach($params as $paramKey => $paramValue)
            {
                $messageMethodParts .= '    <part name="'.$paramValue->name.'" type="xsd:anyType"/>';
            }
            $messageMethods .= '  <message name="'.$methodValue->name.'Request">
            '.$messageMethodParts.'
            </message>
            <message name="'.$methodValue->name.'Response">
            <part name="Result" type="xsd:anyType"/>
            </message>
            ';
            $portTypeOperations .= '    <operation name="'.$methodValue->name.'">
            <input message="tns:'.$methodValue->name.'Request"/>
            <output message="tns:'.$methodValue->name.'Response"/>
            </operation>
            ';
            $bindingOperations .= '    <operation name="'.$methodValue->name.'">
            <soap:operation soapAction="urn:xmethods-delayed-quotes#'.$methodValue->name.'"/>
            <input>
            <soap:body use="encoded" namespace="urn:xmethods-delayed-quotes" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"/>
            </input>
            <output>
            <soap:body use="encoded" namespace="urn:xmethods-delayed-quotes" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"/>
            </output>
            </operation>
            ';
        }

        $s = '<?xml version ='."'".'1.0'."'".' encoding='."'".'UTF-8'."'".' ?>
        <definitions name="'.$className.'"
        targetNamespace="http://example.org/'.$className.'"
        xmlns:tns=" http://example.org/'.$className.' "
        xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/"
        xmlns:xsd="http://www.w3.org/2001/XMLSchema"
        xmlns:soapenc="http://schemas.xmlsoap.org/soap/encoding/"
        xmlns:wsdl="http://schemas.xmlsoap.org/wsdl/"
        xmlns="http://schemas.xmlsoap.org/wsdl/">

        '.$messageMethods.'
        <portType name="'.$className.'PortType">
        '.$portTypeOperations.'
        </portType>
        <binding name="'.$className.'Binding" type="tns:'.$className.'PortType">
        <soap:binding style="rpc" transport="http://schemas.xmlsoap.org/soap/http"/>
        '.$bindingOperations.'
        </binding>
        <service name="'.$className.'Service">
        <port name="'.$className.'Port" binding="'.$className.'Binding">
        <soap:address location="'.$url.'"/>
        </port>
        </service>
        </definitions>
        ';

        return $s;
    }
}

