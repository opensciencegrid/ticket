<?php

//zend viewer http://devzone.zend.com/article/3412
class Zend_View_Helper_Scselector extends Zend_View_Helper_Abstract {

    public function scselector($sc_id)
    {
        $out = "";
        $model = new SC();
        $scs = $model->fetchAll();
        $out .= "<select name=\"metadata_sc\" class=\"span12\">";
        $out .= "<option value=\"\">(not set)</option>";
        foreach($scs as $sc) {
            $key=$sc->id;
            $value=$sc->name;
            if($sc->external_assignment_id != null) {
                $value.=" (ex:".$sc->external_assignment_id.")";
            }
            $selected = "";
            if($sc_id == $key) $selected = "selected=selected";
            $out .= "<option value=\"$key\" $selected>$value</option>";
        }
        $out .= "</select>";
        return $out;
    }
}

