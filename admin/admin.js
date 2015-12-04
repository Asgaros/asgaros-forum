(function($) {
    $(document).ready(function() {
        /******************************** CATEGORIES ********************************/
        $('a#mf_add_new_category').click(function() {
            $('#hidden-element-container li').clone().appendTo('ol#sortable-categories');
            return false;
        });
        $('body').on('click', '.mf_remove_category', function() {
            var answer = confirm(AFAdmin.remove_category_warning);
            if(answer) {
                $(this).parent().remove();
            }
            return false;
        });
        /******************************** SORTABLE FORUMS ********************************/
        $('.sortable_forums').each(function() {
            $(this).sortable({
                placeholder: "ui-state-highlight"
            });
        });
        $('.mf_add_new_forum').click(function() {
            var category_id = $(this).attr('data-value');
            $('#hidden-element-container input:eq(0)').attr("name", "mf_forum_id[" + category_id + "][]");
            $('#hidden-element-container input:eq(1)').attr("name", "forum_name[" + category_id + "][]");
            $('#hidden-element-container input:eq(2)').attr("name", "forum_description[" + category_id + "][]");
            $('#hidden-element-container li').clone().appendTo('ol#sortable-forums-' + category_id);
            return false;
        });
        $('body').on('click', '.mf_remove_forum', function() {
            var answer = confirm(AFAdmin.remove_forum_warning);
            if(answer) {
                $(this).parent().remove();
            }
            return false;
        });
    });
})(jQuery);
