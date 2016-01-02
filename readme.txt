=== Asgaros Forum ===
Contributors: Asgaros
Tags: forums, discussion
Requires at least: 4.3.1
Tested up to: 4.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Asgaros Forum is a lightweight and simple forum plugin for WordPress.

== Description ==
Asgaros Forum is the perfect WordPress plugin for you if you want to extend your website with a lightweight discussion board. It is easy to setup and manage, integrates perfectly with WordPress and comes with a small amout of features which makes it fast and simple.

= Features =
* File uploads
* Integration of the WordPress editor
* Stick, move, edit, remove and close threads
* Edit and remove posts
* Supports multiple languages

= Translations =
* English
* German

== Installation ==
* Download Asgaros Forum.
* Create a new page for your forum to display on and add the shortcode [forum] at this page. Save your page.
* Upload the plugin files to the `/wp-content/plugins/asgaros-forum` directory, or install the plugin through the WordPress plugins screen directly.
* Activate the plugin through the 'Plugins' screen in WordPress.
* On the left side you will see a new admin menu called Forum.
* Configure your Asgaros Forum options, create your Categories and Forums.
* Done!

== Frequently Asked Questions ==
= I want to use a different color =
Add this style rules to your theme CSS file and change #49617D to a color of your choice.
`
#af-wrapper a,
#af-wrapper .breadcrumbs a:hover,
.icon-files-empty-small-yes:before,
.icon-normal_closed-yes:before,
.icon-sticky_closed-yes:before,
.icon-normal_open-yes:before,
.icon-sticky_open-yes:before,
.icon-overview-yes:before,
.icon-link:before {
	color: #49617D !important;
}
#af-wrapper input[type="submit"],
#af-wrapper .forum-menu a,
#af-wrapper .pages a,
#af-wrapper .title-element {
    background-color: #49617D !important;
}
#af-wrapper .content-element {
    border: 1px solid #49617D !important;
}
`

== Screenshots ==
1. The forum overview.
2. The thread overview.
3. The thread view.
4. Creating a new thread.
5. Manage forums in the administration area.

== Changelog ==
= 1.0.1 =
* Removed some untranslated strings
* Fixed display issues with some default themes
* Minor design changes
* Code optimizations and cleanup
* Added missing translation strings
* Updated german translation
= 1.0.0 =
* First initial release
