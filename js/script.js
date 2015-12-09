(function($) {
    $(document).ready(function() {
        $('a#add_file_link').click(function() {
            $('<input type="file" name="forumfile[]" /><br />').insertBefore(this);
        });
        $('.uploaded-file a.delete').click(function() {
            var filename= $(this).attr('filename');
            $('.files-to-delete').append('<input type="hidden" name="deletefile[]" value="'+filename+'" />');
            $(this).parent().remove();
        });
    });
})(jQuery);
