(function($) {
    $(document).ready(function() {
        $('.inline-edit-col input[name=slug]').parents('label').hide();

        // Adding color picker
        $('.color-picker').wpColorPicker();

        // Show/hide color pickers
        $('select[name="theme"]').change(function() {
            if ($('select[name="theme"]').val() === 'default') {
                $('#af-options .custom-color-selector').show();
            } else {
                $('#af-options .custom-color-selector').hide();
            }
        });

        // Create/edit forum dialog.
        $('.forum-editor-link').click(function() {
            var forum_id            = $(this).attr('data-value-id');
            var forum_category      = $(this).attr('data-value-category');
            var forum_parent_forum  = $(this).attr('data-value-parent-forum');
            var forum_name          = '';
            var forum_description   = '';
            var forum_closed        = '';
            var forum_order         = '1';

            if (forum_id !== 'new') {
                forum_name          = $('#forum_'+forum_id+'_name').val();
                forum_description   = $('#forum_'+forum_id+'_description').val();
                forum_closed        = $('#forum_'+forum_id+'_closed').val();
                forum_order         = $('#forum_'+forum_id+'_order').val();
            }

            $('#forum-editor input[name=forum_id]').val(forum_id);
            $('#forum-editor input[name=forum_category]').val(forum_category);
            $('#forum-editor input[name=forum_parent_forum]').val(forum_parent_forum);
            $('#forum-editor input[name=forum_name]').val(forum_name);
            $('#forum-editor input[name=forum_description]').val(forum_description);

            if (forum_closed == 1) {
                $('#forum-editor input[name=forum_closed]').prop('checked', true);
            } else {
                $('#forum-editor input[name=forum_closed]').prop('checked', false);
            }

            $('#forum-editor input[name=forum_order]').val(forum_order);
        });

        // Delete forum dialog.
        $('.forum-delete-link').click(function() {
            var forum_id            = $(this).attr('data-value-id');
            var forum_category      = $(this).attr('data-value-category');

            $('#forum-delete input[name=forum-id]').val(forum_id);
            $('#forum-delete input[name=forum-category]').val(forum_category);
        });

        $('#af-forums .button-cancel').click(function() {
            tb_remove();
        })
    });
})(jQuery);
