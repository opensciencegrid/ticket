$(document).ready(function() {
    $("form").submit(function() {
        var ok = true;
        $("textarea").each(function() {
            var max = 64000;
            var len = $(this).val().length;
            if(len > max) {
                alert("Your description is too long ("+len+" characters). It must be less than " + max + " characters.");
                ok = false;
            }
        });
        return ok;
    });
});
