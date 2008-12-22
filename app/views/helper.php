<?

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

