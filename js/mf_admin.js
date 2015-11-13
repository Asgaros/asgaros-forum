(function($) {
  $(document).ready(function() {
/******************************** SORTABLE CATEGORIES ********************************/
    //Make Categories Sortable
    $('#sortable-categories').sortable({
      placeholder: "ui-state-highlight",
      start: function() {
        $('div.user-groups-area').hide();
      }
    });

    $('a.access_control').click(function() {
      id = $(this).attr('data-value');
      $('div#user-groups-' + id).slideToggle();
      return false;
    });

    $('a#mf_add_new_category').click(function() {
      var new_category_row = get_new_category_row();

      $(new_category_row).hide().appendTo('ol#sortable-categories').fadeIn(500);

      return false;
    });

    $('body').on('click', '.mf_remove_category', function() {
      var answer = confirm(MFAdmin.remove_category_warning);

      if(answer) {
        $(this).parent().fadeOut(500, function() {
          $(this).remove();
        });
      }

      return false;
    });

    function get_new_category_row() {
      var random_id = Math.floor(Math.random() * (1000000 - 100000)) + 100000;

      return '<li class="ui-state-default">\
                <input type="hidden" name="mf_category_id[]" value="new" />\
                &nbsp;&nbsp;\
                <label for="category-name-' + random_id + '">' + MFAdmin.category_name_label + '</label>\
                <input type="text" name="category_name[]" id="category-name-' + random_id + '" value="" />\
                &nbsp;&nbsp;\
                <label for="category-description-' + random_id + '">' + MFAdmin.category_description_label + '</label>\
                <input type="text" name="category_description[]" id="category-description-' + random_id + '" value="" size="50" />\
                <a href="#" class="mf_remove_category" title="' + MFAdmin.remove_category_a_title + '">\
                  <img src="' + MFAdmin.images_url + 'remove.png" width="24" />\
                </a>\
              </li>';
    }

/******************************** SORTABLE FORUMS ********************************/
    //Make Forums Sortable
    $('.sortable_forums').each(function() {
      $(this).sortable({
        placeholder: "ui-state-highlight"
      });
    });

    //Add New Forum Button
    $('.mf_add_new_forum').click(function() {
      var category_id = $(this).attr('data-value');
      var new_forum_row = get_new_forum_row(category_id);

      $(new_forum_row).hide().appendTo('ol#sortable-forums-' + category_id).fadeIn(500);

      return false;
    });

    function get_new_forum_row(category_id) {
      var random_id = Math.floor(Math.random() * (1000000 - 100000)) + 100000;

      return '<li class="ui-state-active">\
                <input type="hidden" name="mf_forum_id[' + category_id + '][]" value="new" />\
                &nbsp;&nbsp;\
                <label for="forum-name-' + random_id + '">' + MFAdmin.forum_name_label + '</label>\
                <input type="text" name="forum_name[' + category_id + '][]" id="forum-name-' + random_id + '" value="" />\
                &nbsp;&nbsp;\
                <label for="forum-description-' + random_id + '">' + MFAdmin.forum_description_label + '</label>\
                <input type="text" name="forum_description[' + category_id + '][]" id="forum-description-' + random_id + '" value="" size="50" />\
                <a href="#" class="mf_remove_forum" title="' + MFAdmin.remove_forum_a_title + '">\
                  <img src="' + MFAdmin.images_url + 'remove.png" width="24" />\
                </a>\
              </li>';
    }

    //Delete a Forum
    $('body').on('click', '.mf_remove_forum', function() {
      var answer = confirm(MFAdmin.remove_forum_warning);

      if(answer) {
        $(this).parent().fadeOut(500, function() {
          $(this).remove();
        });
      }

      return false;
    });

    /******************************** USER GROUPS STUFF *******************************/
    $('a#mf_add_new_user_group').click(function() {
        var new_user_group_row = get_new_user_group_row();
        $(new_user_group_row).appendTo('ol#user-groups');
        return false;
    });
    $('body').on('click', '.mf_remove_user_group', function() {
        var answer = confirm(MFAdmin.remove_user_group_warning);
        if(answer) {
            $(this).parent().remove();
        }
        return false;
    });
    function get_new_user_group_row() {
        var random_id = Math.floor(Math.random() * (1000000 - 100000)) + 100000;
        return '<li class="ui-state-default mf_user_group_li_item">\
            <input type="hidden" name="mf_user_group_id[]" value="new" />\
            <label for="user-group-name-' + random_id + '">' + MFAdmin.user_group_name_label + '</label>\
            <input type="text" name="user_group_name[]" id="user-group-name-' + random_id + '" value="" />\
            &nbsp;&nbsp;\
            <label for="user-group-description-' + random_id + '">' + MFAdmin.user_group_description_label + '</label>\
            <input type="text" name="user_group_description[]" id="user-group-description-' + random_id + '" value="" size="40" />\
            <a href="#" class="mf_remove_user_group" title="' + MFAdmin.remove_user_group_a_title + '">\
            <img src="' + MFAdmin.images_url + 'remove.png" width="24" />\
            </a>\
            </li>';
        }
    });
})(jQuery);
