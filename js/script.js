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

        // Disable submit-button after first submit
        jQuery.fn.preventDoubleSubmission = function() {
            $(this).on('submit', function(e) {
                var $form = $(this);

                if ($form.data('submitted') === true) {
                    e.preventDefault();
                } else {
                    $form.data('submitted', true);
                }
            });

            return this;
        };
        $('#forum-editor-form').preventDoubleSubmission();
    });
})(jQuery);
