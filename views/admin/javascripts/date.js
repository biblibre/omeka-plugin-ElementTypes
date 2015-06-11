jQuery(document).bind("omeka:elementformload", function() {
    jQuery("input[data-type='date']").each(function() {
        jQuery(this).datepicker({
            dateFormat: "yy-mm-dd",
        });
    });
});
