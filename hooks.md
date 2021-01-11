# Available Hooks in Asgaros Forum

Overview of [actions](#actions) and [filters](#filters) at asgaros forum.

## Actions
- asgarosforum_after_post_author
- asgarosforum_after_post_message
- asgarosforum_after_add_topic_submit
- asgarosforum_after_add_post_submit
- asgarosforum_after_edit_post_submit
- asgarosforum_after_topic_approve
- [asgarosforum_{current_view}_custom_content_top](#asgarosforum_current_view_custom_content_top)
- asgarosforum_{current_view}_custom_content_bottom
- asgarosforum_editor_custom_content_bottom
- asgarosforum_statistics_custom_element
- asgarosforum_statistics_custom_content_bottom
- asgarosforum_admin_show_custom_category_data
- asgarosforum_before_delete_post
- asgarosforum_before_delete_topic
- asgarosforum_after_delete_post
- asgarosforum_after_delete_topic
- asgarosforum_custom_forum_column
- asgarosforum_custom_topic_column
- asgarosforum_custom_profile_content
- asgarosforum_custom_profile_menu
- asgarosforum_custom_header_menu
- asgarosforum_prepare_{current_view}
- asgarosforum_wp_head
- asgarosforum_bottom_navigation
- asgarosforum_usergroup_{ID}_add_user
- asgarosforum_usergroup_{ID}_remove_user
- asgarosforum_prepare
- asgarosforum_breadcrumbs_{current_view}
- [asgarosforum_content_top](#asgarosforum_content_top)
- [asgarosforum_content_header](#asgarosforum_content_header)
- asgarosforum_after_category
- asgarosforum_after_forum
- asgarosforum_after_topic
- asgarosforum_after_post
- asgarosforum_content_bottom
- asgarosforum_add_admin_submenu_page
- asgarosforum_execution_check
- asgarosforum_enqueue_css_js
- asgarosforum_profile_row
- asgarosforum_after_add_reaction
- asgarosforum_after_remove_reaction
- asgarosforum_after_update_reaction

### asgarosforum_after_post_author

### asgarosforum_after_post_message

### asgarosforum_after_add_topic_submit

### asgarosforum_after_add_post_submit

### asgarosforum_after_edit_post_submit

### asgarosforum_after_topic_approve

### asgarosforum_{current_view}_custom_content_top

#### Description
Make some action on top of asgaros forum for a specific view. The code will be executed before the header.

Available Views:

| view_name    | Description                             |
|--------------|-----------------------------------------|
| overview     | Overview page of all forums             |
| forum        | Page with a single forum                |
| topic        | Page with a single topic                |
| post         | Only used in the single post shortcode  |
| editpost     | Edit a post                             |
| addpost      | Add a post                              |
| addtopic     | Add a topic                             |
| movetopic    | Move a topic                            |
| search       | Page with results of search             |
| subscription | Overview of all subscriptions of a user |
| profile      | Page with profile of Asgaros Forum      |
| history      | Page with users history in profile      |
| members      | Page with list of members               |
| activity     | Activity page of Forum                  |
| unread       | Page with unread posts                  |
| unapproved   | Page with unapproved posts/topics       |
| reports      | page with reported posts/topics         |


#### Usage

```php
<?php
   add_action('asgarosforum_{current_view}_custom_content_top', 'function_name');
?>
```

#### Examples

```php
<?php
    // Add action to print message on top of the memberslist
     add_action('asgarosforum_members_custom_content_top', 'add_welcome_message');

    // Print welcome message
    function add_welcome_message(){

        echo '<h2>Welcome to the List of all Members</h2>';
    }
?>
```

#### Source

[forum.php](includes/forum.php)

### asgarosforum_{current_view}_custom_content_bottom

### asgarosforum_editor_custom_content_bottom

### asgarosforum_statistics_custom_element

### asgarosforum_statistics_custom_content_bottom

### asgarosforum_admin_show_custom_category_data

### asgarosforum_before_delete_post

### asgarosforum_before_delete_topic

### asgarosforum_after_delete_post

### asgarosforum_after_delete_topic

### asgarosforum_custom_forum_column

### asgarosforum_custom_topic_column

### asgarosforum_custom_profile_content

### asgarosforum_custom_profile_menu

### asgarosforum_custom_header_menu

### asgarosforum_prepare_{current_view}

### asgarosforum_wp_head

### asgarosforum_bottom_navigation

### asgarosforum_usergroup_{ID}_add_user

### asgarosforum_usergroup_{ID}_remove_user

### asgarosforum_prepare

### asgarosforum_breadcrumbs_{current_view}

### asgarosforum_content_top

#### Description
Make some action on top of asgaros forum. The code will be executed before the header.

#### Usage

```php
<?php
   add_action('asgarosforum_post_custom_content_top', 'function_name');
?>
```

#### Examples

```php
<?php
    // Add action to print message on top of the forum
     add_action('asgarosforum_post_custom_content_top', 'add_welcome_message');

    // Print welcome message
    function add_welcome_message(){

        echo '<h2>Welcome to our Forum</h2>';
    }
?>
```

#### Source

[forum.php](includes/forum.php)

### asgarosforum_content_header

#### Description
Make some action after the content header

#### Usage

```php
<?php
   add_action('asgarosforum_content_header', 'function_name');
?>
```

#### Examples

```php
<?php
    // Add action to print information after header
     add_action('asgarosforum_content_header', 'add_information');

    // Print information
    function add_information(){

        echo '<div class="forum-notification">Please read the forum rules before creating a topic.</div>';
    }
?>
```

#### Source

[forum.php](includes/forum.php)

### asgarosforum_after_category

### asgarosforum_after_forum

### asgarosforum_after_topic

### asgarosforum_after_post

### asgarosforum_content_bottom

### asgarosforum_add_admin_submenu_page

### asgarosforum_execution_check

### asgarosforum_enqueue_css_js

### asgarosforum_profile_row

### asgarosforum_after_add_reaction

### asgarosforum_after_remove_reaction

### asgarosforum_after_update_reaction


## Filters

- asgarosforum_filter_login_message
- asgarosforum_filter_post_username
- asgarosforum_filter_post_content
- asgarosforum_filter_post_shortcodes
- asgarosforum_filter_editor_settings
- asgarosforum_filter_editor_buttons
- asgarosforum_filter_get_posts
- asgarosforum_filter_get_threads
- asgarosforum_filter_get_posts_order
- asgarosforum_filter_get_threads_order
- asgarosforum_filter_notify_global_topic_subscribers_message
- asgarosforum_filter_notify_topic_subscribers_message
- asgarosforum_filter_notify_mentioned_user_message
- asgarosforum_filter_insert_custom_validation
- asgarosforum_filter_subject_before_insert
- asgarosforum_filter_content_before_insert
- asgarosforum_filter_widget_title_length
- asgarosforum_widget_excerpt_length
- asgarosforum_subscriber_mails_new_post
- asgarosforum_subscriber_mails_new_topic
- asgarosforum_filter_error_message_require_login
- asgarosforum_filter_user_groups_taxonomy_name
- asgarosforum_filter_avatar_size
- [asgarosforum_filter_profile_header_image](#asgarosforum_filter_profile_header_image)
- asgarosforum_filter_profile_link
- asgarosforum_filter_history_link
- [asgarosforum_filter_show_header](#asgarosforum_filter_show_header)
- [asgarosforum_filter_header_menu](#asgarosforum_filter_header_menu)
- asgarosforum_filter_forum_menu
- asgarosforum_filter_topic_menu
- asgarosforum_filter_post_menu
- asgarosforum_filter_members_link
- asgarosforum_filter_automatic_topic_title
- asgarosforum_filter_automatic_topic_content
- asgarosforum_filter_widget_avatar_size
- asgarosforum_filter_get_sticky_topics_order
- asgarosforum_user_replacements
- asgarosforum_seo_trailing_slash
- asgarosforum_reactions
- asgarosforum_widget_recent_posts_custom_content
- asgarosforum_widget_recent_topics_custom_content
- asgarosforum_title_separator
- asgarosforum_signature
- [asgarosforum_filter_meta_post_type](#asgarosforum_filter_meta_post_type)
- [asgarosforum_filter_upload_folder](#asgarosforum_filter_upload_folder)

### asgarosforum_filter_login_message

### asgarosforum_filter_post_username

### asgarosforum_filter_post_content

### asgarosforum_filter_post_shortcodes

### asgarosforum_filter_editor_settings

### asgarosforum_filter_editor_buttons

### asgarosforum_filter_get_posts

### asgarosforum_filter_get_threads

### asgarosforum_filter_get_posts_order

### asgarosforum_filter_get_threads_order

### asgarosforum_filter_notify_global_topic_subscribers_message

### asgarosforum_filter_notify_topic_subscribers_message

### asgarosforum_filter_notify_mentioned_user_message

### asgarosforum_filter_insert_custom_validation

### asgarosforum_filter_subject_before_insert

### asgarosforum_filter_content_before_insert

### asgarosforum_filter_widget_title_length

### asgarosforum_widget_excerpt_length

### asgarosforum_subscriber_mails_new_post

### asgarosforum_subscriber_mails_new_topic

### asgarosforum_filter_error_message_require_login

### asgarosforum_filter_user_groups_taxonomy_name

### asgarosforum_filter_avatar_size

### asgarosforum_filter_profile_header_image

#### Description
Filters the URL to the background image of the forum profile header.

#### Parameters

##### $url

URL to the background image.

##### $user_id

User ID of shown profile.

#### Usage

```php
<?php
    add_filter ( 'asgarosforum_filter_profile_header_image', 'function_name', 10, 2);
?>
```

#### Examples

```php
<?php
    // Add filter to customize a user profile header background image
    add_filter ( 'asgarosforum_filter_profile_header_image', 'custom_profile_background', 10, 2);

    // Remove profile header background if user is admin
    function custom_profile_background( $url, $user_id){

        // check if user is admin
        if ( user_can( $user_id, 'manage_options' )){
            $url = false;
        }

        return $url;
    }
?>
```

#### Source

[forum-profile.php](includes/forum-profile.php)

### asgarosforum_filter_profile_link

### asgarosforum_filter_history_link

### asgarosforum_filter_show_header

#### Description
Show or hide the forum header.

#### Parameters

##### $show_header
Boolean value to show or hide header

**true**: show header
**false**: hide header

#### Usage

```php
<?php
    add_filter ( 'asgarosforum_filter_show_header', 'function_name');
?>
```

#### Examples

```php
<?php
    // Add filter to hide forum header for logged out users
    add_filter ( 'asgarosforum_filter_show_header', 'hide_header');

    // Function to hide header
    function hide_header(){

        return is_user_logged_in();
    }
?>
```

#### Source

[forum.php](includes/forum.php)

### asgarosforum_filter_header_menu

#### Description
Filter the header menu of asgaros forum.

#### Parameters

##### $menu_entries

Array with Menu Entries as arrays:

```php
$menu_entries = array(
    'name' =>   array(
                    'menu_class'        =>  'HTML Class'
                    'menu_link_text'    =>  'Link Text',
                    'menu_url'          =>  '/url',
                    'menu_login_status' =>  '0',  // (0 = all, 1 = only logged in, 2 = only logged out)
                    'menu_new_tab'      =>  true  // (true = open in new tab, false = open in same tab
                ),
);
```



Names of the standard menu entries:

| name         | description                         | visibility      |
|--------------|-------------------------------------|-----------------|
| home         | Homepage of the Asgaros Forum       | Always          |
| profile      | Profile of the active member        | Only logged in  |
| memberslist  | List of all members                 | Always          |
| subscription | Page to manage subscriptions        | Only logged in  |
| activity     | Page of all activities in the forum | Always          |
| login        | Login page                          | Only logged out |
| register     | Register Page                       | Only logged out |
| logout       | Logout actual user                  | Only logged out |


#### Usage

```php
<?php
    add_filter ( 'asgarosforum_filter_header_menu', 'function_name');
?>
```

#### Examples

```php
<?php
    // Add filter to customize the forum header menu
    add_filter ( 'asgarosforum_filter_header_menu', 'my_custom_menu');

    // Function to customize the forum menu
    function my_custom_menu( $menu_entries){

        // Open memberslist in new tab
        $menu_entries['memberslist']['menu_new_tab'] = true;

        // Create new menu entry
        $menu_entry = array(
                          'menu_class'        =>  'impress',
                          'menu_link_text'    =>  'Impress',
                          'menu_url'          =>  '/impress',
                          'menu_login_status' =>  '0',
                          'menu_new_tab'      =>  true
                      );


        // Add Entry at beginning of the menu
        array_unshift( $menu_entries, $menu_entry);

        return $menu_entries;
    }
?>
```

#### Source

[forum.php](includes/forum.php)

### asgarosforum_filter_forum_menu

### asgarosforum_filter_topic_menu

### asgarosforum_filter_post_menu

### asgarosforum_filter_members_link

### asgarosforum_filter_automatic_topic_title

### asgarosforum_filter_automatic_topic_content

### asgarosforum_filter_widget_avatar_size

### asgarosforum_filter_get_sticky_topics_order

### asgarosforum_user_replacements

### asgarosforum_seo_trailing_slash

### asgarosforum_reactions

### asgarosforum_widget_recent_posts_custom_content

### asgarosforum_widget_recent_topics_custom_content

### asgarosforum_title_separator

### asgarosforum_signature

### asgarosforum_filter_meta_post_type

#### Description
Add the metabox to a custom post type

#### Parameters

##### $post_types

Array with post types

```php
$menu_entries = array('post', 'page');
```

#### Usage

```php
<?php
    add_filter ( 'asgarosforum_filter_meta_post_type', 'function_name');
?>
```

#### Examples

```php
<?php

   add_filter('asgarosforum_filter_meta_post_type', 'add_post_type');

   // Add custom post type to the list of post types
   function add_post_type ($post_types){
       $post_types[] = 'custom_post_type';

       return $post_types;
   }
?>
```

#### Source

[forum.php](includes/forum.php)

### asgarosforum_filter_upload_folder

#### Description
Change the folder for image uploads.

#### Parameters

##### $upload_folder
String with the current name of the upload folder.


#### Usage

```php
<?php
    add_filter ( 'asgarosforum_filter_upload_folder', 'function_name');
?>
```

#### Examples

```php
<?php

   add_filter('asgarosforum_filter_upload_folder', 'change_upload_folder');

   // Add custom post type to the list of post types
   function change_upload_folder ($upload_folder){
       
       $upload_folder = 'new_upload_folder';

       return $upload_folder;
   }
?>
```

#### Source

[forum-uploads.php](includes/forum-uploads.php)