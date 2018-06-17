=== Asgaros Forum ===
Contributors: Asgaros
Donate link: https://www.paypal.me/asgaros
Tags: forum, forums, discussion, multisite, community, bulletin, board, asgaros, support
Requires at least: 4.7
Tested up to: 4.9
Requires PHP: 5.2
Stable tag: 1.9.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Asgaros Forum is the best forum solution for WordPress! It comes with dozens of features in a beautiful design and stays simple and fast.

== Description ==
Asgaros Forum is the perfect WordPress plugin if you want to extend your website with a lightweight and feature-rich discussion board. It is easy to set up, super fast and integrates perfectly into WordPress.

= Installation =
* Create a new page for your forum to display on
* Add the shortcode [forum] to this page
* Add the page to your menu so you can access your forum
* Thats all!

= Demo, Support & Documentation =
* [Official Support Forum](https://www.asgaros.de/support/)
* [Documentation](https://www.asgaros.de/docs/)

= Features =
* Simple Structure & Content Management
* Profiles & Members List
* Powerful Editor
* Notifications
* SEO-friendly
* Reactions
* Uploads
* Search
* Moderators
* Statistics
* Permissions
* User Groups
* Who Is Online
* Guest Postings
* Reporting & Banning
* Easy Appearance Customization
* Widgets
* Mobile Theme
* Theme Manager
* Multilingualism
* Multiple Instances
* Multisite Compatibility

== Installation ==
* Download `Asgaros Forum`.
* Upload the plugin files to the `/wp-content/plugins/asgaros-forum` directory or install the plugin directly via the WordPress plugins screen.
* Activate the plugin via the `Plugins` screen in WordPress.
* Create a new page for your forum, add the `[forum]` shortcode to it and save the page.
* Add this page to your sites menu so you can access it.
* On the left side of the administration area you will find a new menu called `Forum`.
* Configure your options and create the categories/forums there.
* Done!

== Frequently Asked Questions ==
= I cant see new posts/threads or modifications I made to the forum =
If you are using some third-party plugin for caching (WP Super Cache for example) and disable caching for the forum-page, everything should work fine again.
= I cant upload my files =
By default only files of the following filetype can be uploaded: jpg, jpeg, gif, png, bmp, pdf. You can modify the allowed filetypes inside the forum administration.
= Where can I add moderators? =
Moderators can be added via the user edit screen in the WordPress administration interface.
= How can I show a specific post/topic/forum/category on a page? =
You can extend the shortcodes with different parameters to show specific content only. For example: `[forum post="POSTID"]`, `[forum topic="TOPICID"]`, `[forum forum="FORUMID"]`, `[forum category="CATEGORYID"]` or `[forum category="CATEGORYID1,CATEGORYID2"]`.
= Where can I ban users? =
Users can be banned via the user edit screen in the WordPress administration interface.
= How can I add a captcha to the editor for guests? =
To extend your forum with a captcha you have to use one of the available third-party captcha-plugins for WordPress and extend your themes functions.php file with the checking-logic via the available hooks and filters by your own. For example you can use the plugin [Really Simple CAPTCHA](https://wordpress.org/plugins/really-simple-captcha/) and extend your themes functions.php file with this code:
[https://gist.github.com/Asgaros/6d4b88b1f5013efb910d9fcd01284698](https://gist.github.com/Asgaros/6d4b88b1f5013efb910d9fcd01284698).
= I want help to translate Asgaros Forum =
You can help to translate Asgaros Forum on this site:
[https://translate.wordpress.org/projects/wp-plugins/asgaros-forum](https://translate.wordpress.org/projects/wp-plugins/asgaros-forum).
Please only use this site and dont send me your own .po/.mo files because it is hard to maintain if I get multiple translation-files for a language.
= Please approve my translations =
You can approve translations by yourself if you are a Project Translation Editor (PTE). Please contact me in the forums if you are a native speaker and want to become a PTE.
= How can I add my own theme? =
You can add own themes for your forum in the `/wp-content/themes-asgarosforum` directory (for example: `/wp-content/themes-asgarosforum/my-theme`). All themes in the `/wp-content/themes-asgarosforum` can be activated in the forum options. Each theme must have the following files: `style.css`, `widgets.css` and `editor.css`.
= Which hooks and filters are available? =
You can find a list of available hooks and filters on this site:
[https://www.asgaros.de/support/?view=thread&id=407](https://www.asgaros.de/support/?view=thread&id=407).

== Screenshots ==
1. The forum overview.
2. The thread overview.
3. The thread view.
4. Creating a new thread.
5. Manage forums in the administration area.
6. Manage general options.

== Changelog ==
= 1.9.3 =
* Fixed: Users cannot post in closed topics anymore
* Fixed: Users cannot create topics in closed forums anymore
* Changed: Show registration date in last seen-status when user was not online yet
= 1.9.2 =
* Fixed: Broken seo-friendly URLs in combination with certain server protocol configurations
= 1.9.1 =
* Fixed: Broken usernames when URL contains certain special characters
* Fixed: Broken profile URLs when usernames are numeric
* Fixed: Broken URLs when using certain custom permalink structures
* Fixed: Dont show last seen-status in profiles/memberslist when who is online-functionality is disabled
* Changed: Allow HTML in forum descriptions
* Added: asgarosforum_widget_recent_posts_custom_content hook
* Added: asgarosforum_widget_recent_topics_custom_content hook
= 1.9.0 =
* Added: SEO-friendly URLs
* Added: User option to disable mentioning notifications
* Added: Option to add new users to specific usergroups automatically
* Added: Show last post info on small screens
* Fixed: Display issues with some themes
* Fixed: Display issues on small screens
* Changed: Group activity by time
* Changed: Minor design changes
* Changed: Minor design changes in the administration area
* Performance improvements and code optimizations
= 1.8.4 =
* Added: Activity feed functionality
* Added: Option to hide specific usergroups from public
* Added: Option to set time limitation for editing posts
* Added: Show visible usergroups of user in posts
* Changed: Always use defined usergroup color when output an usergroup
* Changed: Improved descriptions in subscriptions overview
* Changed: Minor design changes
* Performance improvements and code optimizations
* Improved compatibility with Autoptimize
= 1.8.3 =
* Fixed: Serious performance issues in the notifications logic
* Fixed: Broken subscriptions functionality for administrators in categories which are accessible for moderators only
* Fixed: Broken subscription checkbox in editor
* Removed: asgarosforum_filter_subscribers_query_new_post filter
* Removed: asgarosforum_filter_subscribers_query_new_topic filter
* Changed: Minor design changes
* Changed: Mobile theme improvements
* Changed: Dont show subscription checkbox in editor when user is subscribed to all topics
* Performance improvements and code optimizations
= 1.8.2 =
* Added: Option to subscribe to all topics and posts
* Added: Possibility to unsubscribe from subscriptions inside the subscription overview
* Added: Option to hide category name in breadcrumbs
* Changed: Moved user subscription settings to the subscription overview
* Changed: Minor design changes
* Changed: Mobile theme improvements
* Changed: Moved mobile css rules into style.css file
* Fixed: Display issues on small screens
* Performance improvements and code optimizations
= 1.8.1 =
* Added: Time when an user was last seen to profiles and members list
* Added: Name of the person who mentioned an user to notification mail
* Added: asgarosforum_filter_notify_mentioned_user_message filter
* Fixed: Display issues in the administration area on small screens
* Fixed: Display issues with some themes
= 1.8.0 =
* Added: Mentioning functionality
* Added: Functionality to move forums
* Fixed: Reactions not saved correctly in some cases
* Fixed: Display issues with some themes
* Changed: Improved mobile navigation
* Changed: Minor design changes
* Performance improvements and code optimizations
= 1.7.2 =
* Fixed: Parse error when using some older versions of PHP
= 1.7.1 =
* Added: The search functionality now also checks topic titles
* Added: Include currently active guests in the statistics-area
* Fixed: Broken usernames in reports when users dont exist
* Fixed: Display issues with some themes
* Removed: Read more-button from editor
* Changed: New profile design
* Changed: Minor design changes
* Performance improvements and code optimizations
= 1.7.0 =
* Added: Reaction functionality
* Added: Reporting functionality
* Added: Search Widget
* Added: Option to limit file uploads to moderators
* Added: Option to change font
* Added: Option to change font size
* Added: Edit profile-link to profile
* Added: Possibility to toggle truncated quotes with a click
* Added: asgarosforum_prepare_{current_view} hooks
* Added: asgarosforum_filter_get_sticky_topics_order filter
* Fixed: Mark all read not working when using category-parameters in shortcode
* Fixed: Visited topics not marked as read in certain cases
* Fixed: Wrong author names for automatically created topics of scheduled blog posts
* Fixed: Wrong titles when using certain SEO plugins
* Fixed: Broken search when using certain special characters
* Fixed: Some strings could not get translated
* Fixed: Display issues with some themes
* Changed: Apply additional validation rules before saving options
* Changed: All theme and color options are now available in the appearance area
* Changed: Minor design changes
* Performance improvements and code optimizations
= 1.6.8 =
* Added: Option to change the border color
* Added: Option to hide members list for guests
* Added: Option to define number of members per page in members list
* Added: Show user role in members list
* Added: asgarosforum_filter_widget_avatar_size filter
* Fixed: Parse error when using some older versions of PHP
* Fixed: Wrong stylings when using custom colors
* Fixed: Display issues on mobile devices
* Changed: Design changes for the administration area
* Changed: Minor design changes
* Performance improvements and code optimizations
= 1.6.7 =
* Added: Members list
* Added: asgarosforum_filter_members_link filter
* Added: asgarosforum_filter_automatic_topic_title filter
* Added: asgarosforum_filter_automatic_topic_content filter
* Fixed: Only create automatic topics for new blog posts instead for all post types
* Performance improvements and code optimizations
= 1.6.6 =
* Fixed: Do additional error checks during database upgrade to prevent some errors during an update
= 1.6.5 =
* Added: Categories for user groups
* Fixed: Administrators didnt get notifications in some cases when using user groups
* Fixed: Hide topics and forums from subscription list when a user has no access to it
* Fixed: Display issues with some themes
* Changed: Show number of users for each user group in the user overview
* Changed: Minor design changes
* Performance improvements and code optimizations
= 1.6.4 =
* Added: Option to automatically create topics for new blog posts
* Fixed: Broken subscriptions/profile-view when using shortcode with category-parameter
* Changed: Truncate long quotes
* Changed: Minor design changes
* Changed: Renamed asgarosforum_after_add_thread_submit hook into asgarosforum_after_add_topic_submit
* Performance improvements and code optimizations
= 1.6.3 =
* Added: Pagination in topic-overview
* Added: Category name to breadcrumbs
* Added: User groups to profile
* Added: Biographical info to profile
* Added: Signature to profile
* Added: asgarosforum_filter_forum_menu filter
* Added: asgarosforum_filter_topic_menu filter
* Added: asgarosforum_filter_post_menu filter
* Changed: Minor design changes
* Performance improvements and code optimizations
* Compatibility with WordPress 4.9
* Added link to the official support forum to the administration area
= 1.6.2 =
* Added: Options to hide login/logout/register buttons
* Added: asgarosforum_custom_header_menu hook
* Fixed: Broken search when using plain permalink structure
* Fixed: Styling issues with highlighted usernames
* Fixed: Display issues with some themes
* Changed: Prevent indexing of the following views: addtopic, movetopic, addpost, editpost, search
* Changed: Show page number in meta title
* Changed: Minor design changes
= 1.6.1 =
* Fixed: Broken guest-posting functionality
= 1.6.0 =
* Added: Profile functionality
* Fixed: Subscriptions view not working with certain shortcode parameters
* Fixed: Rare PHP-notices
* Changed: Login/Logout/Register links are now accessible everywhere
* Changed: Minor design changes
* Performance improvements and code optimizations
= 1.5.9 =
* Added: Option to hide author posts counter
* Fixed: Prevent cross-site request forgery in settings
= 1.5.8 =
* Fixed: Prevent non-admin users from modifying settings
* Fixed: PHP errors when updating user profile
* Changed: Enqueue stylesheets
* Performance improvements and code optimizations
= 1.5.7 =
* Added: Subscription overview
* Fixed: Strip slashes when showing the description inside of a forum
* Changed: Highlight quotes in the editor
* Changed: Minor design changes
= 1.5.6 =
* Fixed: Broken structure administration
* Performance improvements and code optimizations
= 1.5.5 =
* Added: Option to show description in forum
* Added: asgarosforum_filter_avatar_size filter
* Fixed: Dont show last posts/topics in widgets when user cant access any categories
* Fixed: Limit maximum characters in a couple of input fields
* Fixed: Display issues in administration with small screen resolutions
* Fixed: Wrong stylings when using custom colors
* Changed: Minor design changes
* Performance improvements and code optimizations
= 1.5.4 =
* Added: Register links to some error messages
* Fixed: Huge performance drops when the forum has a lot of posts/topics
* Fixed: Broken editor with some editor-configurations
* Fixed: Wrong stylings when using custom colors
* Fixed: Display issues with some themes
* Changed: Increased page-navigation size on mobile devices
* Performance improvements and code optimizations
= 1.5.3 =
* Added: Option to change icon of forums
* Fixed: Missing data when using shortcode parameter for specific post
* Performance improvements and code optimizations
= 1.5.2 =
* Added: Option to set notification sender name
* Added: Option to set notification sender mail
* Fixed: Display issues with some themes
* Fixed: JavaScript warning when editing the forum structure
* Changed: Set forum cookies only on forum page
* Changed: Show possible error messages when uploading files
* Changed: Minor design changes
* Performance improvements and code optimizations
* Compatibility with WordPress 4.8
= 1.5.1 =
* Fixed: Fatal PHP error on some versions of PHP
= 1.5.0 =
* Added: User groups functionality
* Added: Show newest member in overview
* Added: Show names of online users in overview
* Added: Show who edited a post
* Added: asgarosforum_after_first_post hook
* Added: asgarosforum_custom_forum_column hook
* Added: asgarosforum_custom_topic_column hook
* Fixed: Dont remove paragraphs from quotes
* Fixed: Some strings could not get translated
* Changed: Show possible error messages when updating the structure
* Changed: Minor design changes
* Performance improvements and code optimizations
= 1.4.5 =
* Added: Widget option to hide avatars
* Added: Reply counter to recent forum topics widget
* Added: Show author in notification mails
* Added: asgarosforum_before_delete_post hook
* Added: asgarosforum_before_delete_topic hook
* Added: asgarosforum_after_delete_post hook
* Added: asgarosforum_after_delete_topic hook
* Fixed: Huge performance drops when the forum has a lot of posts/topics
* Fixed: Prevent creation of hidden content
* Fixed: Broken/missing links when the filename of uploads contains special characters
* Fixed: Do not try to generate thumbnails for pdf uploads
* Fixed: Dont show HTML-entities in the subject of notification mails
* Changed: Indent subforums in forums-list when moving topics
* Changed: Minor design changes
* Performance improvements and code optimizations
= 1.4.4 =
* Added: Avatars to widgets
* Added: asgarosforum_filter_widget_title_length filter
* Fixed: Strip slashes of forum names/descriptions in structure-administration
* Fixed: Warnings on multisite installations
* Changed: Highlight administrators/moderators in widgets
* Changed: Minor design changes
* Performance improvements and code optimizations
= 1.4.3 =
* Added: Open Graph tags
* Fixed: Display issues with some themes
* Changed: Minor design changes
* Mobile Theme Improvements
* Search Engine Optimizations
* Performance improvements and code optimizations
= 1.4.2 =
* Revised structure administration
* Changed: Minor design changes
* Removed: asgarosforum_action_add_category_form_fields hook
* Removed: asgarosforum_action_edit_category_form_fields hook
* Removed: asgarosforum_action_save_category_form_fields hook
* Removed: asgarosforum_filter_manage_columns filter
* Removed: asgarosforum_filter_manage_custom_columns filter
= 1.4.1 =
* Added: Option to show thumbnails for uploads
* Fixed: Correct escaping of keywords in search results view
* Changed: Keep keywords in search input field
* Changed: Minor design changes
* Performance improvements and code optimizations
= 1.4.0 =
* Added: Option to show who is online
* Added: Shortcode extension to show a specific post
* Added: Shortcode extension to show a specific topic
* Added: Shortcode extension to show a specific forum
* Added: Shortcode extension to show one or more specific categories
* Added: Option to hide breadcrumbs
* Added: Show statistics in the mobile view
* Added: Cancel button to editor
* Added: Show IDs of forums/categories inside the administration area
* Fixed: Sort categories correctly in the forum-overview
* Fixed: Load stylesheets and scripts only on forum page
* Fixed: Wrong labels in forum configuration
* Fixed: Display issues with some themes
* Changed: Hide new post/topic buttons when editor is active
* Changed: Show pagination under search results
* Changed: Show full breadcrumbs when moving topics
* Changed: Structure of the forum configuration
* Changed: Minor design changes
* Search Engine Optimizations
* Performance improvements and code optimizations
= 1.3.10 =
* Fixed: Filter [Forum] shortcodes from posts
* Fixed: Remove filtered shortcodes from post content only
* Fixed: Display issues with some themes
* Changed: Show editor for new posts in the lower area
* Performance improvements and code optimizations
* The required minimum WordPress version is now 4.7
= 1.3.9 =
* Fixed: Dont show error to logged-out users when the guest-posting functionality is disabled
* Fixed: Display issues with some themes
* Changed: Minor design changes
* Performance improvements and code optimizations
= 1.3.8 =
* Fixed: Notifications about new topics were sent to all users who subscribed to specific forums
* Fixed: Status change of topics was not working with some WordPress configurations
* Fixed: Scroll to the correct editor position when creating new posts
* Fixed: Display issues with some themes
* Changed: Small adjustment to the editor location
= 1.3.7 =
* Added: Possibility to add multiple quotes at once
* Fixed: Private/pending/draft pages can now be set as the forum location
* Fixed: Display issues with some themes
* Changed: Show editor at the same page when adding posts or topics
* Changed: Show all numbers in a format based on the used locale
* Changed: Always show all forum options
* Changed: Minor design changes
* Performance improvements and code optimizations
= 1.3.6 =
* Fixed: Save deactivated options in the administration area correctly
* Changed: Minor design changes
= 1.3.5 =
* Added: Option to limit number of uploads per post
* Added: Option to limit file size of uploads
* Fixed: Broken mark all read-button
* Fixed: Site administrators could not moderate topics/posts on multisite installations
* Fixed: Dont let post-footer hide post-content
* Fixed: PHP notices during creation or editing of content
* Fixed: Reload scripts and stylesheets in administration after plugin update
* Minor usability improvements
* Performance improvements and code optimizations
= 1.3.4 =
* Added: Signature functionality
* Fixed: Reload scripts and stylesheets after plugin update
* Fixed: Display issues with some themes
* Performance improvements and code optimizations
= 1.3.3 =
* Fixed: Parse error when using some older versions of PHP
= 1.3.2 =
* Added: Statistics functionality
* Added: asgarosforum_statistics_custom_element hook
* Added: asgarosforum_statistics_custom_content_bottom hook
* Fixed: Display issues with some themes
* Changed: Minor design changes
= 1.3.1 =
* Added: Subscriptions for specific forums
* Fixed: Group search results by topic to avoid duplicates
* Fixed: Sort search results correctly by relevance and date
* Fixed: Wrong stylings when using custome colors
* Fixed: Display issues when visiting the forum with a mobile device
* Fixed: Display issues with some themes
* Changed: Minor design changes
= 1.3.0 =
* Added: Search functionality
* Added: asgarosforum_filter_error_message_require_login filter
* Changed: Dont shorten topic titles
* Search Engine Optimizations
* Revised design
= 1.2.9 =
* Fixed: Broken widgets with some WordPress configurations
* Fixed: Dont send notifications about new posts/topics in restricted categories to all users
* Fixed: Dont send notifications to banned users
* Added: asgarosforum_{current_view}_custom_content_top hooks
* Added: asgarosforum_{current_view}_custom_content_bottom hooks
* Added: asgarosforum_filter_subscribers_query_new_topic filter
* Added: asgarosforum_filter_subscribers_query_new_post filter
* Added: asgarosforum_subscriber_mails_new_topic filter
* Added: asgarosforum_subscriber_mails_new_post filter
* Changed: Minor design changes
* Performance improvements and code optimizations
* Compatibility with WordPress 4.7
= 1.2.8 =
* Fixed: Broken link-generation with some WordPress configurations
= 1.2.7 =
* Fixed: Broken read/unread-logic
* Fixed: Remove cookies for guests correctly when mark all forums as read
* Changed: Try to determine widget-links when forum-location is not set correctly
= 1.2.6 =
* Fixed: Only show widgets when the forum is configured correctly
* Fixed: Show filtered login-message only when necessary
* Fixed: Rare PHP-notices
* Changed: Moved location-selection from widgets to forum-settings
* Setup improvements
* Performance improvements and code optimizations
* The required minimum WordPress version is now 4.6
= 1.2.5 =
* Fixed: Never highlight guests as topic-authors
* Added: Database-driven read/unread-logic across topics
* Added: Widget for recent forum topics
* Changed: Minor design changes
* Performance improvements and code optimizations
= 1.2.4 =
* Fixed: Various fixes in the read/unread-logic
* Added: Option to highlight thread authors
* Added: Option in user profiles to get notifications on new topics
* Added: asgarosforum_filter_subject_before_insert filter
* Added: asgarosforum_filter_content_before_insert filter
* Changed: Read/Unread icons are now better recognizable
* Changed: Renamed asgarosforum_filter_notify_administrator_message filter into asgarosforum_filter_notify_global_topic_subscribers_message
* Performance improvements and code optimizations
= 1.2.3 =
* Fixed: Remove slashes in the forum description
* Fixed: Escape HTML in the forum description
* Fixed: Display issues with some themes
* Changed: Links in notification mails are now clickable
* Changed: Added amount of posts to the asgarosforum_after_post_author hook
= 1.2.2 =
* Fixed: Remove tables on multisite installations correctly
* Fixed: Dont hide widget when there are no recent posts
* Fixed: Display issues with some themes
* Added: Option to allow uploads from guests
* Added: asgarosforum_filter_login_message filter
* Performance improvements and code optimizations
= 1.2.1 =
* Fixed: Prevent generation of wrong canonical links
* Fixed: Rare PHP-warning when using notifications
* Added: Multisite compatibility
* Changed: Show login-links at all pages
* Search Engine Optimizations
* Performance improvements and code optimizations
= 1.2.0 =
* Fixed: Correct escaping in notification mails
* Fixed: Display issues in notification mails with some characters
* Fixed: Resize external iframe-content (e.g. YouTube videos) correctly
* Fixed: Display issues with some themes
* Fixed: Added missing translation strings
* Fixed: Misleading strings
* Added: Option to allow guest postings
* Added: Option to allow shortcodes in posts
* Added: Option to hide uploads from guests
* Added: asgarosforum_editor_custom_content_bottom hook
* Added: asgarosforum_filter_insert_custom_validation filter
* Performance improvements and code optimizations
= 1.1.6 =
* Fixed: HTML is now rendered correctly in notification-mails
* Fixed: Correct escaping of URLs
* Fixed: Prevent modification of topic-subject
* Fixed: Prevent submitting the same form multiple times
* Fixed: Redirect to the current forum-page after login
* Added: Post number to the bottom of posts
* Added: asgarosforum_filter_notify_administrator_message filter
* Added: asgarosforum_filter_notify_topic_subscribers_message filter
* Changed: Revised editor
* Changed: Improved error handling
* Changed: Post number is linking to the post now instead of the date
* Changed: Added post ID to asgarosforum_after_post_message hook
* Changed: Renamed asgarosforum_after_thread_submit hook into asgarosforum_after_add_thread_submit
* Changed: Renamed asgarosforum_after_post_submit hook into asgarosforum_after_add_post_submit
* Changed: Renamed asgarosforum_after_edit_submit hook into asgarosforum_after_edit_post_submit
* Changed: Improved compatibility with some third-party plugins
* Performance improvements and code optimizations
= 1.1.5 =
* Fixed: Correct filtering of posts inside the widget
* Fixed: Hide post-counter for deleted users
* Fixed: The notification-text in mails is now translatable
* Fixed: Rare PHP-notice in categories-configuration
* Fixed: Display issues with some themes
* Added: Subscribe checkbox in editor for the current topic
* Added: asgarosforum_after_post_message hook
* Added: asgarosforum_filter_get_posts filter
* Added: asgarosforum_filter_get_posts_order filter
* Performance improvements and code optimizations
= 1.1.4 =
* Fixed: The names of some users were not shown correctly
= 1.1.3 =
* Fixed: Correct sanitizing of URL parameters
* Fixed: Removed unnecessary hyphen from username
* Added: Option to disable the minimal-configuration of the editor
= 1.1.2 =
* Fixed: PHP parse-error when using a PHP version less than 5.3
* Fixed: Display issues with some themes
* Added: Notifications functionality
* Performance improvements and code optimizations
= 1.1.1 =
* Fixed: PHP-Warning in theme-manager
= 1.1.0 =
* Fixed: Categories were not sorted correctly
* Fixed: Display issues with some themes
* Fixed: Prevent accessing some PHP-files directly
* Fixed: Added missing translation strings
* Added: Sub-forum functionality
* Added: Banning functionality
* Added: Theme manager functionality (thanks to Hisol)
* Added: Color picker for the text
* Added: Color picker for the background
* Changed: Administrators cant be set to forum-moderators anymore
* Changed: Subject in last-post-view links to the topic
* Changed: Revised forum management
* Changed: Minor design changes
* Changed: Provide translation files via WordPress Updater only
* Performance improvements and code optimizations
= 1.0.14 =
* Fixed: Display issues with some themes
* Added: Option to modify allowed filetypes for uploads
* Changed: Only the following filetypes can be uploaded by default: jpg, jpeg, gif, png, bmp, pdf
* Changed: Hide page-navigation when there is only one page
* Changed: Provide spanish translation updates via WordPress Updater
* Performance improvements and code optimizations
= 1.0.13 =
* Fixed: Closed forums were not saved correctly
* Fixed: Display issues with some themes
* Added: asgarosforum_filter_post_username filter
* Changed: Show moderator buttons only at the beginning of threads
* Changed: Minor design changes
* Performance improvements and code optimizations
= 1.0.12 =
* Fixed: Broken link of uploaded file when filename contains umlaute
* Fixed: Display issues with some themes
* Added: Option to close forums
* Changed: Categories are now ordered in the administration area
* Changed: Use default WordPress icons instead of own icon pack
* Changed: Minor design changes
* Changed: Provide portuguese (Portugal) translation updates via WordPress Updater
* Performance improvements and code optimizations
= 1.0.11 =
* Fixed: Missing page titles with some themes
* Fixed: Display issues when using apostrophes and backslashes
* Fixed: Wrong HTML escaping
* Fixed: Display issues with some themes
* Added: Portuguese (Portugal) translation (thanks to Sylvie & Bruno)
= 1.0.10 =
* Fixed: PHP errors when using a PHP version less than 5.3
* Fixed: Display issues with big post images in Internet Explorer
* Added: asgarosforum_after_thread_submit hook
* Added: asgarosforum_after_post_submit hook
* Added: asgarosforum_after_edit_submit hook
* Changed: Minor design changes
* Changed: Provide russian translation updates via WordPress Updater
* Performance improvements and code optimizations
= 1.0.9 =
* Fixed: Broken thread titles when using multi-byte characters
* Fixed: Display issues with some themes
* Added: Category access permissions
* Added: Filter asgarosforum_filter_editor_settings
* Changed: Improved compatibility with some third-party plugins
* Performance improvements and code optimizations
= 1.0.8 =
* Fixed: Insert forum at the correct shortcode position
* Fixed: Broken URLs with some third-party plugins
* Fixed: Display issues with some themes
* Added: Moderator functionality
* Added: Filter asgarosforum_filter_get_threads
* Added: Filter asgarosforum_filter_get_threads_order
* Added: Spanish translation (thanks to QuqurUxcho)
= 1.0.7 =
* Fixed: Prevent the creation of empty content
* Fixed: Hide widget for guests when access is limited to logged in users
* Fixed: Some PHP notices
* Fixed: Display issues with some themes
* Added: Option to hide the edit date
* Added: Filter hook asgarosforum_filter_post_content
* Changed: Editor error messages are now shown on the editor page
* Changed: Minor design changes
= 1.0.6 =
* Fixed: Wrong word wrap
* Fixed: Display issues with some themes
* Added: "Last edited" info to posts
* Changed: Provide hungarian translation updates via WordPress Updater
* Changed: Added author_id to asgarosforum_after_post_author action hook
* Performance improvements and code optimizations
= 1.0.5 =
* Added: Option to easily change the forum color
* Added: Option to limit access to logged in users
* Added: Action hook asgarosforum_after_post_author
* Added: Danish translation (thanks to crusie)
* Changed: Minor design changes
* Changed: Provide german translation updates via WordPress Updater
= 1.0.4 =
* Fixed: Display issues with some themes
* Fixed: Error messages were not translated
* Added: Option to highlight administrator names
* Added: Notice when user is not logged in
* Added: "Go back" link on error pages
* Added: Hungarian translation (thanks to zsebtyson)
* Changed: Permalink accessible via date instead of icon
* Updated: English and german translation
* Performance improvements and code optimizations
= 1.0.3 =
* Fixed: Icons not visible in some WordPress themes
* Fixed: Broken images inside quoted posts
* Fixed: Display issues with big images in posts
* Fixed: Prevent accessing PHP-files directly
* Added: Recent Forum Posts Widget
* Added: Bosnian translation (thanks to AntiDayton)
* Removed: Image captions
* Updated: English, german and russian translation
= 1.0.2 =
* Fixed: Dont modify page titles outside of the forum
* Fixed: Added missing translation strings
* Added: Editor button for adding images to posts
* Added: CSS design rules for better mobile device compatibility
* Added: Finnish translation (thanks to juhani.honkanen)
* Added: French translation (thanks to thomasroy)
* Added: Russian translation (thanks to ironboys)
* Changed: Minor design changes
* Updated: English and german translation
* Performance improvements and code optimizations
= 1.0.1 =
* Fixed: Added missing translation strings
* Fixed: Display issues with some default themes
* Changed: Minor design changes
* Changed: Translation slug from asgarosforum to asgaros-forum
* Updated: German translation
* Performance improvements and code optimizations
= 1.0.0 =
* First initial release
