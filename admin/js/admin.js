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

        // Show/hide profile options.
        $('#enable_profiles').change(function() {
            if (this.checked) {
                $('#af-options .profile-option').show();
            } else {
                $('#af-options .profile-option').hide();
            }
        });

        // Create/edit category dialog.
        $('.category-editor-link').click(function() {
            resetStructureEditor();

            var editor_title        = $(this).attr('data-value-editor-title');
            var category_id         = $(this).attr('data-value-id');
            var category_name       = '';
            var category_access     = 'everyone';
            var category_order      = '1';
            var category_usergroups = '';

            if (category_id !== 'new') {
                category_name       = $('#category_'+category_id+'_name').val();
                category_access     = $('#category_'+category_id+'_access').val();
                category_order      = $('#category_'+category_id+'_order').val();
                category_usergroups = $('#category_'+category_id+'_usergroups').val().split(',');
            }

            $('#category-editor input[name=category_id]').val(category_id);
            $('#category-editor input[name=category_name]').val(category_name);

            $('#category-editor select[name=category_access] option').each(function() {
                if ($(this).val() == category_access) {
                    $(this).prop('selected', true);
                }
            });

            $('#category-editor input[name=category_order]').val(category_order);

            $('#usergroups-editor input[type=checkbox]').each(function() {
                if (jQuery.inArray($(this).val(), category_usergroups) != -1) {
                    $(this).prop('checked', true);
                } else {
                    $(this).prop('checked', false);
                }
            });

            setStructureTitle(editor_title);

            // Show editor.
            $('#category-editor').show();
            $('#structure-editor-container').show();
            $('#category-editor input[name=category_name]').focus();
        });

        // Create/edit forum dialog.
        $('.forum-editor-link').click(function() {
            resetStructureEditor();

            var editor_title        = $(this).attr('data-value-editor-title');
            var forum_id            = $(this).attr('data-value-id');
            var forum_category      = $(this).attr('data-value-category');
            var forum_parent_forum  = $(this).attr('data-value-parent-forum');
            var forum_name          = '';
            var forum_description   = '';
            var forum_icon          = 'dashicons-editor-justify';
            var forum_closed        = '';
            var forum_order         = '1';

            if (forum_id !== 'new') {
                forum_name          = $('#forum_'+forum_id+'_name').val();
                forum_description   = $('#forum_'+forum_id+'_description').val();
                forum_icon          = $('#forum_'+forum_id+'_icon').val();
                forum_closed        = $('#forum_'+forum_id+'_closed').val();
                forum_order         = $('#forum_'+forum_id+'_order').val();
            }

            $('#forum-editor input[name=forum_id]').val(forum_id);
            $('#forum-editor input[name=forum_category]').val(forum_category);
            $('#forum-editor input[name=forum_parent_forum]').val(forum_parent_forum);
            $('#forum-editor input[name=forum_name]').val(forum_name);
            $('#forum-editor input[name=forum_description]').val(forum_description);
            $('#forum-editor input[name=forum_icon]').val(forum_icon);

            if (forum_closed == 1) {
                $('#forum-editor input[name=forum_closed]').prop('checked', true);
            } else {
                $('#forum-editor input[name=forum_closed]').prop('checked', false);
            }

            $('#forum-editor input[name=forum_order]').val(forum_order);

            setStructureTitle(editor_title);

            // Show editor.
            $('#forum-editor').show();
            $('#structure-editor-container').show();
            $('#forum-editor input[name=forum_name]').focus();
        });

        // Create/edit usergroup dialog.
        $('.usergroup-editor-link').click(function() {
            resetUserGroupEditor();

            var editor_title    = $(this).attr('data-value-editor-title');
            var usergroup_id    = $(this).attr('data-value-id');
            var usergroup_name  = '';
            var usergroup_color = '#444444';

            if (usergroup_id !== 'new') {
                usergroup_name  = $('#usergroup_'+usergroup_id+'_name').val();
                usergroup_color = $('#usergroup_'+usergroup_id+'_color').val();
            }

            $('#usergroup-editor input[name=usergroup_id]').val(usergroup_id);
            $('#usergroup-editor input[name=usergroup_name]').val(usergroup_name);
            $('#usergroup-editor input[name=usergroup_color]').val(usergroup_color);
            $('#usergroup-editor input[name=usergroup_color]').wpColorPicker('color', usergroup_color);

            setUserGroupTitle(editor_title);

            // Show editor.
            $('#usergroup-editor').show();
            $('#usergroup-editor-container').show();
            $('#usergroup-editor input[name=usergroup_name]').focus();
        });

        // Delete category dialog.
        $('.category-delete-link').click(function() {
            resetStructureEditor();

            var editor_title        = $(this).attr('data-value-editor-title');
            var category_id         = $(this).attr('data-value-id');

            $('#category-delete input[name=category-id]').val(category_id);

            setStructureTitle(editor_title);

            $('#category-delete').show();
            $('#structure-editor-container').show();
        });

        // Delete forum dialog.
        $('.forum-delete-link').click(function() {
            resetStructureEditor();

            var editor_title        = $(this).attr('data-value-editor-title');
            var forum_id            = $(this).attr('data-value-id');
            var forum_category      = $(this).attr('data-value-category');

            $('#forum-delete input[name=forum-id]').val(forum_id);
            $('#forum-delete input[name=forum-category]').val(forum_category);

            setStructureTitle(editor_title);

            $('#forum-delete').show();
            $('#structure-editor-container').show();
        });

        // Delete user group dialog.
        $('.usergroup-delete-link').click(function() {
            resetUserGroupEditor();

            var editor_title    = $(this).attr('data-value-editor-title');
            var usergroup_id    = $(this).attr('data-value-id');

            $('#usergroup-delete input[name=usergroup-id]').val(usergroup_id);

            setUserGroupTitle(editor_title);

            $('#usergroup-delete').show();
            $('#usergroup-editor-container').show();
        });

        $('#usergroup-editor-container .button-cancel').click(function() {
            resetUserGroupEditor();
        })

        $('#structure-editor-container .button-cancel').click(function() {
            resetStructureEditor();
        })

        function setStructureTitle(title) {
            $('#structure-editor-container h2').html(title);
        }

        function setUserGroupTitle(title) {
            $('#usergroup-editor-container h2').html(title);
        }

        function resetStructureEditor() {
            $('#structure-editor-container').hide();
            $('#category-editor').hide();
            $('#category-delete').hide();
            $('#forum-editor').hide();
            $('#forum-delete').hide();
        }

        function resetUserGroupEditor() {
            $('#usergroup-editor-container').hide();
            $('#usergroup-editor').hide();
            $('#usergroup-delete').hide();
        }
    });
})(jQuery);
