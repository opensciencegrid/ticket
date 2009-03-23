<?

function status2style($status)
{
    switch($status)
    {
        case "Open": return "ticketid_open";
        case "Engineering": return "ticketid_engineering";
        case "Customer": return "ticketid_customer";
        case "Network Administration": return "ticketid_networkadministration";
        case "Support Agency": return "ticketid_supportagency";
        case "Vendor": return "ticketid_vendor";
        case "Resolved": return "ticketid_resolved";
        case "Closed": return "ticketid_closed";
    }
}

//returns a unique id number for div element (only valid for each session - don't store!)
function getuid()
{
    $uid = new Zend_Session_Namespace('uid');

    if(isset($uid->next)) {
        $next_uid = $uid->next;
        $uid->next = $next_uid + 1;
        return $next_uid+rand(); //add random number to avoid case when 2 different sessions are used
    } else {
        $uid->next = 1000; //let's start from 1000
        return $uid->next;
    }
}

function outputToggle($show, $hide, $content, $open_by_default = false)
{
    $divid = getuid();
    ob_start();

    if(true) {
        $showbutton_style = "button";
        $hidebutton_style = "button";
        $detail_style = "detail";
        if($open_by_default) {
            $showbutton_style .= " hidden";
        } else {
            $hidebutton_style .= " hidden";
            $detail_style .= " hidden";
        }
        ?>
        <div id='show_<?=$divid?>' class='<?=$showbutton_style?>'><img src='<?=base()?>/images/plusbutton.gif'/> <?=$show?></div>
        <? if($hide != "") { ?>
            <div id='hide_<?=$divid?>' class='<?=$hidebutton_style?>'><img src='<?=base()?>/images/minusbutton.gif'/> <?=$hide?></div>
        <? } ?>
        <div class='<?=$detail_style?>' id='detail_<?=$divid?>'><?=$content?></div>
        <script type='text/javascript'>
        $('#show_<?=$divid?>').click(function() {
            $('#detail_<?=$divid?>').slideDown("normal", function() {
/*
                if(uwa()) {
                    widget.callback('onUpdateBody');
                }
*/
            });
            $('#show_<?=$divid?>').hide();
            $('#hide_<?=$divid?>').show();
        });
        $('#hide_<?=$divid?>').click(function() {
            $('#detail_<?=$divid?>').slideUp();
            $('#hide_<?=$divid?>').hide();
            $('#show_<?=$divid?>').show();
        });
        </script>
        <?
    }

    $content = ob_get_contents();
    ob_end_clean();
    return $content;
}

function agoCalculation($timestamp)
{
    $ago = time() - $timestamp;
    return humanDuration($ago);
}
function humanDuration($ago)
{
    if($ago < 60 * 2) return $ago." seconds";
    if($ago < 60*60 * 2) return floor($ago/60)." minutes";
    if($ago < 60*60*24 * 2) return floor($ago/(60*60))." hours";
    return floor($ago/(60*60*24))." days";
}

function listSelector($selector_id, $possible, $already)
{
    $selector_id = str_replace(" ", "", $selector_id);

    $out = "";

    $out .= "<div id=\"$selector_id\" class=\"listselector\">";
    $out .= "<div id=\"${selector_id}_selected\" class=\"ls_selected list\" style=\"background-color: #fff\">";
    foreach($possible as $value=>$name) {
        $selected = "";
        $cls = "hidden";
        if(isset($already[$value])) {
            $selected = "checked=checked";
            $cls = "";
        }
        $out .= "<div class=\"$cls\"><input type=\"checkbox\" $selected name=\"${selector_id}[]\" value=\"$value\" onclick=\"move(this);\"/> $name</div>";
        
    }
    $out .= "</div>";

    $scrolled = "";
    if(count($possible) > 5) $scrolled = "scrolled_list";
    $out .= "<div id=\"${selector_id}_possible\" class=\"ls_possible list $scrolled\">";
    foreach($possible as $value=>$name) {
        $cls = "";
        if(isset($already[$value])) {
            $cls = "hidden";
        }
        $out .= "<div class=\"$cls\"><input type=\"checkbox\" posvalue=\"$value\" onclick=\"move(this);\"/> $name</div>";
    }
    $out .= "</div>";

    $out .= "<script type=\"text/javascript\">";
    $out .= "function move(node) {";
    $out .= "   var parents = $(node).parents('.listselector');";
    $out .= "   //add to target\n";
    $out .= "   if(node.checked) {";
    $out .= "       //move up\n";
    $out .= "       var value = $(node).attr('posvalue');";
    $out .= "       var i = parents.find('.ls_selected input[value=\"'+value+'\"]');";
    $out .= "       i.attr('checked', 'checked');";
    $out .= "       $(node).removeAttr('checked');";
    $out .= "       i.parent().show();";
    $out .= "       $(node).parent().hide();";
    $out .= "   } else {";
    $out .= "       //move down\n";
    $out .= "       var value = $(node).attr('value');";
    $out .= "       $(node).removeAttr('checked');";
    $out .= "       var i = parents.find('.ls_possible input[posvalue=\"'+value+'\"]');";
    $out .= "       i.parent().show();";
    $out .= "       $(node).parent().hide();";
    $out .= "   }";
    $out .= "}";
    $out .= "</script>";

    $out .= "</div>";

    return $out;
}

function nadstyle($nad)
{
    $nad = strtotime($nad);
    if($nad < time()) {
        return "red";
    } else if($nad + 3600*24*7 < time()) {
        return "yellow";
    }
    return "";
}



