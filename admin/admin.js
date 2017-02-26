(function($) {
    $(document).ready(function() {
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

        // Show/hide upload options.
        $('#allow_file_uploads').change(function() {
            if (this.checked) {
                $('#af-options .uploads-option').show();
            } else {
                $('#af-options .uploads-option').hide();
            }
        });

        // Create/edit category dialog.
        $('.category-editor-link').click(function() {
            resetEditor();

            var editor_title        = $(this).attr('data-value-editor-title');
            var category_id         = $(this).attr('data-value-id');
            var category_name       = '';
            var category_access     = 'everyone';
            var category_order      = '1';

            if (category_id !== 'new') {
                category_name       = $('#category_'+category_id+'_name').val();
                category_access     = $('#category_'+category_id+'_access').val();
                category_order      = $('#category_'+category_id+'_order').val();
            }

            $('#category-editor input[name=category_id]').val(category_id);
            $('#category-editor input[name=category_name]').val(category_name);

            $('#category-editor select[name=category_access] option').each(function() {
                if ($(this).val() == category_access) {
                    $(this).attr('selected', 'selected');
                }
            });

            $('#category-editor input[name=category_order]').val(category_order);

            setTitle(editor_title);

            // Show editor.
            $('#category-editor').show();
            $('#structure-editor').show();
            $('#category-editor input[name=category_name]').focus();
        });

        // Create/edit forum dialog.
        $('.forum-editor-link').click(function() {
            resetEditor();

            var editor_title        = $(this).attr('data-value-editor-title');
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

            setTitle(editor_title);

            // Show editor.
            $('#forum-editor').show();
            $('#structure-editor').show();
            $('#forum-editor input[name=forum_name]').focus();
        });

        // Delete category dialog.
        $('.category-delete-link').click(function() {
            resetEditor();

            var editor_title        = $(this).attr('data-value-editor-title');
            var category_id         = $(this).attr('data-value-id');

            $('#category-delete input[name=category-id]').val(category_id);

            setTitle(editor_title);

            $('#category-delete').show();
            $('#structure-editor').show();
        });

        // Delete forum dialog.
        $('.forum-delete-link').click(function() {
            resetEditor();

            var editor_title        = $(this).attr('data-value-editor-title');
            var forum_id            = $(this).attr('data-value-id');
            var forum_category      = $(this).attr('data-value-category');

            $('#forum-delete input[name=forum-id]').val(forum_id);
            $('#forum-delete input[name=forum-category]').val(forum_category);

            setTitle(editor_title);

            $('#forum-delete').show();
            $('#structure-editor').show();
        });

        $('#structure-editor .button-cancel').click(function() {
            resetEditor();
        })

        function setTitle(title) {
            $('#structure-editor h2 span').html(title);
        }

        function resetEditor() {
            $('#structure-editor').hide();
            $('#category-editor').hide();
            $('#category-delete').hide();
            $('#forum-editor').hide();
            $('#forum-delete').hide();
        }
    });
})(jQuery);
