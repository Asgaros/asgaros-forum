(function($) {
    $(document).ready(function() {
        // Adds a new spoiler-button to the editor.
        $(document).on('tinymce-editor-setup', function(event, editor) {
            editor.settings.toolbar1 += ',spoiler';

            editor.addButton('spoiler', {
                icon: 'spoiler',
                tooltip: 'Spoiler',
                onclick: function() {
                    editor.insertContent("[spoiler][/spoiler]");
                }
            });
        });

        // Toggles a clicked spoiler.
        $('#af-wrapper .spoiler .spoiler-head').click(function() {
    		$(this).toggleClass('closed').toggleClass('opened').next().toggle();
    	});

        // Show editor inside another view.
        $('a.forum-editor-button').click(function(e) {
            e.preventDefault();

            // Hide new post/topic buttons.
            $('a.forum-editor-button').hide();

            $('#forum-editor-form').slideToggle(400, function() {
                // Focus subject line or editor.
                var focusElement = $('.editor-row-subject input');

                if (focusElement.length) {
                    focusElement[0].focus();
                } else {
                    // We need to focus the form first to ensure scrolling.
                    $('#forum-editor-form').focus();

                    // Focus the editor.
                    if (tinyMCE.activeEditor) {
                        tinyMCE.activeEditor.focus();
                    } else {
                        $('textarea[id="message"]').focus();
                    }
                }
            });
        });

        // Close editor.
        $('.editor-row a.cancel').click(function(e) {
            e.preventDefault();

            $('#forum-editor-form').slideToggle(400, function() {
                $('a.forum-editor-button').show();
                $('.editor-row-subject input').val('');

                if (tinyMCE.activeEditor) {
                    tinyMCE.activeEditor.setContent('');
                } else {
                    $('textarea[id="message"]').val('');
                }
            });
        });

        $('a.forum-editor-quote-button').click(function(e) {
            e.preventDefault();

            // Hide new post/topic buttons.
            $('a.forum-editor-button').hide();

            // Build quote.
            var quoteID = $(this).attr('data-value-id');
            var quoteContent = $('#post-quote-container-'+quoteID).html();

            // Add quote to the end of the editor.
            if (tinyMCE.activeEditor) {
                tinyMCE.activeEditor.setContent(tinyMCE.activeEditor.getContent()+quoteContent);
            } else {
                $('textarea[id="message"]').val($('textarea[id="message"]').val()+quoteContent);
            }

            // Call slideDown() instead of slideToggle() so we can add multiple quotes at once.
            $('#forum-editor-form').slideDown(400, function() {
                // We need to focus the form first to ensure scrolling.
                $('#forum-editor-form').focus();

                // Focus the editor at the last line.
                if (tinyMCE.activeEditor) {
                    tinyMCE.activeEditor.focus();
                    tinyMCE.activeEditor.selection.select(tinyMCE.activeEditor.getBody(), true);
                    tinyMCE.activeEditor.selection.collapse(false);
                } else {
                    $('textarea[id="message"]').focus();
                }
            });
        });

        $('a#add_file_link').click(function() {
            // Insert new upload element.
            $('<input type="file" name="forumfile[]" /><br>').insertBefore(this);

            // Check if we can add more upload elements.
            checkUploadsMaximumNumber();
        });

        $('.uploaded-files a.delete').click(function() {
            var filename= $(this).attr('data-filename');
            $('.files-to-delete').append('<input type="hidden" name="deletefile[]" value="'+filename+'" />');
            $(this).parent().remove();

            // Check if we can add more upload elements.
            checkUploadsMaximumNumber();

            // When there are no uploads anymore, remove the editor row.
            var filesNumber = $('.uploaded-files li').length;

            if (filesNumber == 0) {
                $('.uploaded-files').parent().remove();
            }
        });

        // Disable submit-button after first submit
        $.fn.preventDoubleSubmission = function() {
            $(this).on('submit', function(e) {
                var form = $(this);

                if (form.data('submitted') === true) {
                    e.preventDefault();
                } else {
                    form.data('submitted', true);
                }
            });

            return this;
        };
        $('#forum-editor-form').preventDoubleSubmission();

        function checkUploadsMaximumNumber() {
            var linkElement = $('a#add_file_link');
            var maximumNumber = linkElement.attr('data-maximum-number');

            if (maximumNumber > 0) {
                var inputsNumber = $('.editor-row-uploads input[type="file"]').length;
                var filesNumber = $('.uploaded-files li').length;
                var totalNumber = inputsNumber + filesNumber;

                if (totalNumber >= maximumNumber) {
                    linkElement.hide();
                } else {
                    linkElement.show();
                }
            }
        }

        // Add ability to toggle truncated quotes.
        $('#af-wrapper .post-message > blockquote').click(function() {
            $(this).toggleClass('full-quote');
        });

        // Mobile navigation.
        $('#forum-navigation-mobile').click(function() {
            $('#forum-navigation').toggleClass('show-navigation');
        });

        // Automatic submit for subscription settings.
        $('#af-wrapper input[name=subscription_level]').on('change', function() {
            $(this).closest('form').submit();
        });

        // Focus search input when clicking on container.
        $('#af-wrapper #forum-search').click(function() {
            $('#af-wrapper #forum-search input[name=keywords]').focus();
        });

        // Memberslist filter toggle.
        $('#af-wrapper #memberslist-filter-toggle').click(function() {
            $('#af-wrapper #memberslist-filter').slideToggle(0, function() {
                var final_state = $(this).is(':hidden') ? 'hidden' : 'visible';

                if (final_state === 'hidden') {
                    $('#af-wrapper #memberslist-filter-toggle').attr('class', 'title-element dashicons-before dashicons-arrow-down-alt2');
                    $('#af-wrapper #memberslist-filter-toggle').html($("#af-wrapper #memberslist-filter").attr('data-value-show-filters'));
                } else {
                    $('#af-wrapper #memberslist-filter-toggle').attr('class', 'title-element dashicons-before dashicons-arrow-up-alt2');
                    $('#af-wrapper #memberslist-filter-toggle').html($("#af-wrapper #memberslist-filter").attr('data-value-hide-filters'));
                }
            });
        });
    });
})(jQuery);
