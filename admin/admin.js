(function($) {
    $(document).ready(function() {
        /******************************** FORUMS ********************************/
        $('.af_add_new_forum').click(function() {
            var category_id = $(this).attr('data-value');
            $('#hidden-element-container input:eq(0)').attr("name", "af_forum_id[" + category_id + "][]");
            $('#hidden-element-container input:eq(1)').attr("name", "forum_name[" + category_id + "][]");
            $('#hidden-element-container input:eq(2)').attr("name", "forum_description[" + category_id + "][]");
            $('#hidden-element-container li').clone().appendTo('ol#sortable-forums-' + category_id);
            return false;
        });
        $('body').on('click', '.af_remove_forum', function() {
            var answer = confirm(asgarosforum_admin.remove_forum_warning);
            if(answer) {
                $(this).parent().remove();
            }
            return false;
        });
        $('.af-sort-up').click(function() {
            $before = $(this).parent().parent().prev();
            $(this).parent().parent().insertBefore($before);
        });
        $('.af-sort-down').click(function() {
            $after = $(this).parent().parent().next();
            $(this).parent().parent().insertAfter($after);
        });
    });
})(jQuery);
