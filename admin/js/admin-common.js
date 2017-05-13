(function($) {
    $(document).ready(function() {
        $('#forum-user-group-select').change(function() {
            var action = $(this).parent().attr('action');
            action = action + '?forum-user-group='+$(this).val();
            window.location = action;
        });
    });
})(jQuery);
