<script type="text/javascript">
function addcc(node)
{
    var template = $("<div class=\"input-append cc\" style=\"width: 100%;\"><input class=\"span12\" type=\"text\" name=\"cc[]\"/><i onclick=\"$(this).parents('div.cc').remove();return false;\" class=\"icon-remove\"></i></div>");
    node.append(template);
    setup_cc();
}
<?
$model = new Person();
$persons = $model->fetchAll();
$persons_json = "";
foreach($persons as $person) 
{
    $name = trim($person->name);
    $email = trim($person->primary_email);
    $phone = trim($person->primary_phone); //needed by yourinfo
    if($name == "") $name = $email;
    if($persons_json != "") $persons_json .= ",\n";
    $persons_json .= "{ label: \"$name <$email>\", value: \"$email\", email: \"$email\", name: \"$name\", phone: \"$phone\"}";
}
?>
var persons = [
    <?=$persons_json?>
];
function setup_cc()
{
    $(".cc input").autocomplete({
        source: persons
    });/*.data("ui-autocomplete")._renderItem = function(ul, item) {
        return $("<li>").append("<a>"+item.name + " <span class='email'>&lt;"+item.email+"&gt;</span></a>").appendTo(ul);
    };*/
}
$(function() {
    setup_cc();
});

</script>

<?
function cceditor($ccs) {
    if(isset($ccs)) {
        foreach($ccs as $cc) {
            echo "<div class=\"cc\" style=\"width: 100%;\"><input type=\"text\" class=\"span12\" name=\"cc[]\" value=\"$cc\"/><i onclick=\"$(this).parents('div.cc').remove();return false;\" class=\"icon-remove\"></i></div>";
        }
    }
}

