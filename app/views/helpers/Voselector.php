<?php

//zend viewer http://devzone.zend.com/article/3412
class Zend_View_Helper_Voselector extends Zend_View_Helper_Abstract {

    //flush all messages pending to be displayed
    public function voselector($vo_id)
    {
        $out = "";
        $model = new VO();
        $vos = $model->fetchAll();
        $out .= "<select name=\"metadata_vo\" class=\"span12\">";
        $out .= "<option value=\"\">(not set)</option>";
        foreach($vos as $vo) {
            $key=$vo->id;
            $value=$vo->name;
            $selected = "";
            if($vo_id == $key) $selected = "selected=selected";
            $out .= "<option value=\"$key\" $selected>$value</option>";
        }
        $out .= "</select>";
        return $out;
    }
}

