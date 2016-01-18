(function($) {
    $(document).ready(function() {
        $('.af-add-new-forum').click(function() {
            var category_id = $(this).attr('data-value');
            $('#new-element input:eq(0)').attr('name', 'forum_id[' + category_id + '][]');
            $('#new-element input:eq(1)').attr('name', 'forum_name[' + category_id + '][]');
            $('#new-element input:eq(2)').attr('name', 'forum_description[' + category_id + '][]');
            $('#new-element div').clone().appendTo('div#category-' + category_id);
            return false;
        });
        $('body').on('click', '.af-remove-forum', function() {
            var answer = confirm(asgarosforum_admin.remove_forum_warning);
            if (answer) {
                $(this).parent().remove();
            }
            return false;
        });
        $('body').on('click', '.af-sort-up', function() {
            $before = $(this).parent().parent().prev();
            $(this).parent().parent().insertBefore($before);
        });
        $('body').on('click', '.af-sort-down', function() {
            $after = $(this).parent().parent().next();
            $(this).parent().parent().insertAfter($after);
        });
        $('.inline-edit-col input[name=slug]').parents('label').hide();

        // Adding color picker
        $('.custom-color').wpColorPicker();
    });
})(jQuery);
