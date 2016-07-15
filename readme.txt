=== Asgaros Forum ===
Contributors: Asgaros
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=4A5464D83ACMJ
Tags: forums, discussion
Requires at least: 4.4
Tested up to: 4.5
Stable tag: 1.1.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Asgaros Forum is a lightweight and simple forum plugin for WordPress.

== Description ==
Asgaros Forum is the perfect WordPress plugin for you if you want to extend your website with a lightweight discussion board. It is easy to set up and manage, integrates perfectly with WordPress and comes with a small amount of features which makes it fast and simple.

= Installation =
Create a new page for your forum to display on and add the shortcode [forum] to this page. Add this page to your menu so you can access the forum. Thats all!

= Features =
* Topic & post management
* Sub-forums
* Notifications
* Moderators
* Permissions
* Banning
* Powerful editor
* File uploads
* Recent Forum Posts Widget
* Easy color customization
* Theme manager
* Mobile device compatibility
* Supports multiple languages

== Installation ==
* Download Asgaros Forum.
* Upload the plugin files to the `/wp-content/plugins/asgaros-forum` directory, or install the plugin through the WordPress plugins screen directly.
* Activate the plugin through the 'Plugins' screen in WordPress.
* On the left side you will see a new admin menu called Forum.
* Configure your Asgaros Forum options, create your Categories and Forums.
* Create a new page for your forum to display on and add the shortcode [forum] to this page. Save your page.
* Add this page to your menu so you can access the forum.
* Done!

== Frequently Asked Questions ==
= I cant see new posts/threads or modifications I made to the forum =
If you are using some third-party plugin for caching (WP Super Cache for example) and disable caching for the forum-page, everything should work fine again.
= I cant upload my files =
By default only files of the following filetype can be uploaded: jpg, jpeg, gif, png, bmp, pdf. You can modify the allowed filetypes inside the forum administration.
= Where can I add moderators? =
Moderators can be added via the user edit screen in the WordPress administration interface.
= Where can I ban users? =
Users can be banned via the user edit screen in the WordPress administration interface.
= I want help to translate Asgaros Forum =
You can help to translate Asgaros Forum on this site:
[https://translate.wordpress.org/projects/wp-plugins/asgaros-forum](https://translate.wordpress.org/projects/wp-plugins/asgaros-forum).
Please only use this site and dont send me your own .po/.mo files because it is hard to maintain if I get multiple translation-files for a language.
= Please approve my translations =
You can approve translations by yourself if you are a Project Translation Editor (PTE). Please contact me in the forums if you are a native speaker and want to become a PTE.
= How can I add my own theme? =
You can add own themes for your forum in the `/wp-content/themes-asgarosforum` directory (for example: `/wp-content/themes-asgarosforum/my-theme`). All themes in the `/wp-content/themes-asgarosforum` can be activated in the forum options. Each theme must have at least those files: `style.css`, `mobile.css` and `widgets.css`.
= Which hooks are available =
* asgarosforum_after_post_author
* asgarosforum_after_post_message
* asgarosforum_after_add_thread_submit
* asgarosforum_after_add_post_submit
* asgarosforum_after_edit_post_submit
* asgarosforum_action_add_category_form_fields
* asgarosforum_action_edit_category_form_fields
* asgarosforum_action_save_category_form_fields
= Which filters are available =
* asgarosforum_filter_post_username
* asgarosforum_filter_post_content
* asgarosforum_filter_editor_settings
* asgarosforum_filter_get_posts
* asgarosforum_filter_get_threads
* asgarosforum_filter_get_posts_order
* asgarosforum_filter_get_threads_order
* asgarosforum_filter_notify_administrator_message
* asgarosforum_filter_notify_topic_subscribers_message

== Screenshots ==
1. The forum overview.
2. The thread overview.
3. The thread view.
4. Creating a new thread.
5. Manage forums in the administration area.
6. Manage general options.

== Changelog ==
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
* Added: Sub-forum functionality
* Added: Banning functionality
* Added: Theme manager functionality (thanks to Hisol)
* Added: Color picker for the text
* Added: Color picker for the background
* Added: Missing translation strings
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
* Fixed: Removed untranslatable strings
* Added: Editor button for adding images to posts
* Added: CSS design rules for better mobile device compatibility
* Added: Finnish translation (thanks to juhani.honkanen)
* Added: French translation (thanks to thomasroy)
* Added: Russian translation (thanks to ironboys)
* Changed: Minor design changes
* Updated: English and german translation
* Performance improvements and code optimizations
= 1.0.1 =
* Fixed: Removed untranslatable strings
* Fixed: Display issues with some default themes
* Added: Missing translation strings
* Changed: Minor design changes
* Changed: Translation slug from asgarosforum to asgaros-forum
* Updated: German translation
* Performance improvements and code optimizations
= 1.0.0 =
* First initial release
