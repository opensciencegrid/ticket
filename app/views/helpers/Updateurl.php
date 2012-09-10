<?php

//zend viewer http://devzone.zend.com/article/3412
class Zend_View_Helper_Updateurl extends Zend_View_Helper_Abstract {

    public function updateurl($params)
    {
        $purl = parse_url($_SERVER["REQUEST_URI"]);
        $path = $purl["path"];
        if(isset($purl["query"])) {
            parse_str($purl["query"], $query);
        } else {
            $query = array();
        }

        //update query with new params
        foreach($params as $key=>$value) {
            if(is_null($value)) {
                unset($query[$key]);
            } else {
                $query[$key] = $value;
            }
        }

        //construct new url
        $uri = $path."?";
        foreach($query as $key=>$value) {
            if(is_array($value)) {
                $uri .= $key."[]=".implode("&amp;".$key."[]=", array_map('urlencode', $value))."&";
            } else {-
                $uri .= "$key=$value&";
            }
        }
        return $uri;
    }
}
