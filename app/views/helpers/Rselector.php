<?php

//zend viewer http://devzone.zend.com/article/3412
class Zend_View_Helper_Rselector extends Zend_View_Helper_Abstract {

    public function rselector($r_id)
    {
        $out = "";
        $model = new Resource();
        $rs = $model->fetchAll();
        $out .= "<select name=\"metadata_r\" class=\"span12\">";
        $out .= "<option value=\"\">(not set)</option>";
        foreach($rs as $r) {
            $key=$r->id;
            $value=$r->name." (".$r->fqdn.")";
            $selected = "";
            if($r_id == $key) $selected = "selected=selected";
            $out .= "<option value=\"$key\" $selected>$value</option>";
        }
        $out .= "</select>";
        return $out;
    }
}

