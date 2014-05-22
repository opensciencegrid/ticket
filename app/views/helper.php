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
        $showbutton_style = "";
        $hidebutton_style = "";
        $detail_style = "detail";
        if($open_by_default) {
            $showbutton_style .= " hidden";
        } else {
            $hidebutton_style .= " hidden";
            $detail_style .= " hidden";
        }
        echo "<div id='show_$divid' class='$showbutton_style'>$show</div>";
        if($hide != "") { 
            echo "<div id='hide_$divid' class='$hidebutton_style'>$hide</div>";
        }
        echo "<div class='$detail_style' id='detail_$divid'>$content</div>";
        ?><script type='text/javascript'>
        $('#show_<?=$divid?>').click(function() {
            $('#detail_<?=$divid?>').slideDown("normal");
            $('#show_<?=$divid?>').hide();
            $('#hide_<?=$divid?>').show();
        });
        $('#hide_<?=$divid?>').click(function() {
            $('#detail_<?=$divid?>').slideUp();
            $('#hide_<?=$divid?>').hide();
            $('#show_<?=$divid?>').show();
        });
        </script><?
    }

    $content = ob_get_contents();
    ob_end_clean();
    return $content;
}

function agoCalculation($timestamp)
{
    $ago = time() - $timestamp;
    $str = humanDuration($ago);
    return $str;
}
function humanDuration($ago)
{
    if($ago < 60 * 2) return $ago." seconds";
    if($ago < 60*60 * 2) return floor($ago/60)." minutes";
    if($ago < 60*60*24 * 2) return floor($ago/(60*60))." hours";
    return floor($ago/(60*60*24))." days";
}

function checklist($id, $kv, $selected, $extrainfo)
{
    //output list
    $out = "";
    $out .= "<div class=\"list\" id=\"${id}__list\">";
    foreach($kv as $key=>$value) {
        $checked = "";
        $label_class = "";
        if(isset($selected[$key])) {
            $checked = "checked=checked";
            $label_class = "checked";
        }
        $name = "$id"."[$key]";

        //add some extra info .. if provided for this key
        if(isset($extrainfo[$key])) {
            list($extra, $url) = $extrainfo[$key];
            
            $out .= "<span class=\"pull-right\" style=\"line-height: 180%;\">";
            if($url !== null) {
                $out .= "<a target=\"${key}_${extra}\" href=\"$url\">$extra</a>&nbsp;";
            } else {
                $out .= $extra;
            }
            $out .= "</span>";
        }
        $out .= "<div class=\"item $label_class\" onclick=\"\$(this).toggleClass('checked'); var i = \$(this).find('input'); if(!i.hasClass('flip')) { if(!i.is(':checked')) i.attr('checked', 'checked'); else i.removeAttr('checked'); } else {i.removeClass('flip');}\">";

        $out .= "<input id=\"cl_$name\" type=\"checkbox\" name=\"$name\" value=\"on\" $checked onclick=\"$(this).addClass('flip'); return true;\"/>&nbsp;";
        $out .= "<label>$value</label>";

        $out .= "</div>";
    }
    $out .= "</div>";
    echo $out;
}


function fblist($id, $kv, $selected, $max_select=1000)//1000 is just some arbitrary number..
{
    $out = "";

    //output list editor
    $out .= "<div class=\"fblist_container\" id=\"${id}__list\">";
    $out .= "<div class=\"fblist\" style=\"position: relative;\" onclick=\"$(this).find('.autocomplete').focus(); return false;\" ondblclick=\"$(this).find('.autocomplete').autocomplete('search',''); return false;\">";

    //output script
    $script = "<script type='text/javascript'>$(function() {";
    $script .= "var ${id}__listdata = [";
    $first = true;
    $pre_selected ="";
    foreach($kv as $key=>$value) {
        $name = "$id"."[$key]";
        if(isset($selected[$key])) {
            $pre_selected .= "<div><i onclick=\"$(this).parent().siblings('.autocomplete').val('').show(); $(this).parent().remove();\" class=\"icon-remove\"></i> ".$value."<input type=\"hidden\" name=\"$name\"/ value=\"on\"></div>";
        }
        if(!$first) {
            $script .= ",\n";
        }
        $first = false;
        $script .= "{ id: \"$key\", label: \"$value\"}";
    }
    $script .= "];";
    if(count($selected) >= $max_select) {
        $script .= "$(\"#${id}__list input.autocomplete\").hide();";
    }
    $script .= <<<BLOCK
    $("#${id}__list input.autocomplete").autocomplete(${id}__listdata, 
    {
        source: ${id}__listdata,
        minLength: 0,
        select: function(event, ui) {
            $("#${id}__acnote").hide();
            $(this).before("<div><i class=\"icon-remove\" onclick=\"$(this).parent().siblings('.autocomplete').val('').show();$(this).parent().remove();\"></i> "+ui.item.label+"<input type=\"hidden\" name=\"${id}["+ui.item.id+"]\" value=\"on\"/></div>");
            if($(this).siblings("div").length >= $max_select) {
                $(this).hide();
            }
            $(this).val("");
            return false;
        }
    });
});
</script>
BLOCK;

    $out .= $pre_selected;
    $out .= "<input type='text' class='autocomplete ac_input' style='background-color: transparent;' onfocus='$(\"#${id}__acnote\").fadeIn(\"slow\");' onblur='$(\"#${id}__acnote\").fadeOut(\"slow\");'/>";
    $out .= $script;

    //display note
    $out .= "<p id=\"${id}__acnote\" class=\"hidden\" style=\"z-index: -1; position: absolute; color: #999; font-size: 9px; right: 5px; bottom: -5px; text-align: right; font-size: 10px;line-height: 100%;\">Double click to show all</p>";

    $out .= "</div>";//fblist
    $out .= "</div>";//fblist_container

    return $out;
}

function nadstyle($nad)
{
    $nad = strtotime($nad);
    if($nad < time()) {
        return "flag_red";
    } else if($nad < time() + 3600*config()->nad_alert_hours) {
        return "flag_yellow";
    }
    return "";
}

/*
function htmlsafe($str)
{
    return htmlspecialchars($str, ENT_NOQUOTES, "UTF-8");
}
*/
