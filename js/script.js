(function($) {
    $(document).ready(function() {
        $('a#add_file_link').click(function() {
            $('<input type="file" name="forumfile[]" /><br />').insertBefore(this);
        });
    });
})(jQuery);
