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
        $out .= "<div class=\"$label_class\">";

        //add some extra info .. if provided for this key
        if(isset($extrainfo[$key])) {
            list($extra, $url) = $extrainfo[$key];
            
            $out .= "<span class=\"sidenote\">";
            if($url !== null) {
                $out .= "<a target=\"${key}_${extra}\" href=\"$url\">$extra</a>";
            } else {
                $out .= $extra;
            }
            $out .= "</span>";
        }

        $out .= "<input type=\"checkbox\" name=\"$name\" value=\"on\" $checked onclick=\"if(this.checked) {\$(this).parent().addClass('checked');} else {\$(this).parent().removeClass('checked');}\"/>&nbsp;";
        $out .= $value;

        $out .= "</div>";
    }
    $out .= "</div>";
    echo $out;
}


function fblist($id, $kv, $selected)
{
    $out = "";

    //output list editor
    $out .= "<div class=\"fblist_container\" id=\"${id}__list\"><div class=\"fblist\" style=\"position: relative;\" onclick=\"$(this).find('.autocomplete').focus(); return false;\">";

    //output script
    $delete_url = "images/delete.png";
    $script = "<script type='text/javascript'>$(document).ready(function() {";
    $script .= "var ${id}__listdata = [";
    $first = true;
    $pre_selected ="";
    foreach($kv as $key=>$value) {
        $name = "$id"."[$key]";
        if(isset($selected[$key])) {
            $pre_selected .= "<div><img onclick=\"$(this).parent().remove();\" src=\"$delete_url\"/>".$value."<input type=\"hidden\" name=\"$name\"/ value=\"on\"></div>";
        }
        if(!$first) {
            $script .= ",\n";
        }
        $first = false;
        $script .= "{ id: \"$key\", name: \"$value\", desc: \"\" }";
    }
    $script .= "];";
    $script .= <<<BLOCK
    $("#${id}__list input.autocomplete").autocomplete(${id}__listdata, {
        max: 9999999,
        minChars: 0,
        mustMatch: true,
        matchContains: true,
        width: 280,
        formatItem: function(item) {
            if(item.desc == "") return item.name; 
            return item.name + " (" + item.desc + ")";
        }
    }).result(function(event, item) {
        if(item != null) {
            $(this).val("");
            $(this).before("<div><img onclick=\"$(this).parent().remove();\" src=\"$delete_url\"/>"+item.name+"<input type=\"hidden\" name=\"${id}["+item.id+"]\" value=\"on\"/></div>");
        }
    });
});</script>
BLOCK;

    $out .= $pre_selected;
    $out .= "<input type='text' class='autocomplete' onfocus='$(\"#${id}__acnote\").fadeIn(\"slow\");' onblur='$(\"#${id}__acnote\").fadeOut(\"slow\");'/>";
    $out .= $script;

    $out .= "</div>";

    //display note
    $out .= "<p id=\"${id}__acnote\" class=\"hidden\" style=\"color: #999; font-size: 9px; text-align: right; font-size: 10px;line-height: 100%;\">Double click to show all</p>";

    $out .= "</div>";

    return $out;
}

function nadstyle($nad)
{
    $nad = strtotime($nad);
    if($nad < time()) {
        return "flag_red";
    } else if($nad < time() + 3600*24) {
        return "flag_yellow";
    }
    return "";
}

function htmlsafe($str)
{
    return htmlspecialchars($str, ENT_NOQUOTES, "UTF-8");
}

