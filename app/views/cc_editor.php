<script type="text/javascript">
function addcc(node)
{
    var template = "<div class=\"cc\"><input type=\"text\" class=\"cc\" name=\"cc[]\"/><img onclick=\"$(this).parents('div.cc').remove();\" class=\"ac_input_remove\" src=\"<?=fullbase()?>/images/delete.png\"/></div>";
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
    $phone = trim($person->primary_phone);
    if($name == "") $name = $email;
    if($persons_json != "") $persons_json .= ",\n";
    $persons_json .= "{ name: \"$name\", email: \"$email\", phone: \"$phone\"}";
}
?>
var persons = [
    <?=$persons_json?>
];
$(document).ready(function() {
    setup_cc();
});
function setup_cc()
{
    $(".cc").autocomplete(persons, {
        mustMatch: false,
        matchContains: true,
        width: 500,
        formatItem: function(row, i, max) {
            return row.name + "<br/>Email: " + row.email;
        },
        formatMatch: function(row, i, max) {
            return row.name + " " + row.email;
        },
        formatResult: function(row) {
            return row.email;
        }
    });
}

</script>

<?
function cceditor($ccs) {
    if(isset($ccs)) {
        foreach($ccs as $cc) {
            echo "<div class=\"cc\"><input type=\"text\" class=\"cc\" name=\"cc[]\" value=\"$cc\"/><a class=\"ac_input_remove\" href=\"#\" onclick=\"$(this).parents('div.cc').remove();return false;\"><img src=\"".fullbase()."/images/delete.png\"/></a></div>";
        }
    }
}

