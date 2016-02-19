=== Asgaros Forum ===
Contributors: Asgaros
Tags: forums, discussion
Requires at least: 4.3.1
Tested up to: 4.4.2
Stable tag: 1.0.9
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Asgaros Forum is a lightweight and simple forum plugin for WordPress.

== Description ==
Asgaros Forum is the perfect WordPress plugin for you if you want to extend your website with a lightweight discussion board. It is easy to set up and manage, integrates perfectly with WordPress and comes with a small amount of features which makes it fast and simple.

= Features =
* Topic/post management (Remove/Edit/Close/Sticky/Move)
* Moderators
* Permissions
* Powerful editor
* File uploads
* Recent Forum Posts Widget
* Easy color customization
* Mobile device compatibility
* Supports multiple languages

= Translations =
* Bosnian
* Danish
* English
* Finnish
* French
* German
* Hungarian
* Italian
* Russian
* Spanish

== Installation ==
* Download Asgaros Forum.
* Create a new page for your forum to display on and add the shortcode [forum] at this page. Save your page.
* Upload the plugin files to the `/wp-content/plugins/asgaros-forum` directory, or install the plugin through the WordPress plugins screen directly.
* Activate the plugin through the 'Plugins' screen in WordPress.
* On the left side you will see a new admin menu called Forum.
* Configure your Asgaros Forum options, create your Categories and Forums.
* Done!

== Frequently Asked Questions ==
= Where can I add moderators? =
Moderators can be added via the user edit screen in the WordPress administration interface.
= I want help to translate Asgaros Forum =
You can help to translate Asgaros Forum on this site:
[https://translate.wordpress.org/projects/wp-plugins/asgaros-forum](https://translate.wordpress.org/projects/wp-plugins/asgaros-forum).
Please contact me in the forums if you want to be a Project Translation Editor (PTE) for a language.

== Screenshots ==
1. The forum overview.
2. The thread overview.
3. The thread view.
4. Creating a new thread.
5. Manage forums in the administration area.
6. Manage general options.

== Changelog ==
= 1.0.9 =
* Fixed: Broken thread titles when using multi-byte characters
* Fixed: Display issues with some themes
* Added: Category access permissions
* Added: Filter asgarosforum_filter_editor_settings
* Changed: Better support of some third-party plugins
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
* Added: "Last edited" info to posts
* Fixed: Wrong word wrap
* Fixed: Display issues with some themes
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
