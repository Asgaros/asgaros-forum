=== Mingle Forum Lite ===
Contributors: Thomas Belser, cartpauj
Requires at least: 3.5
Tested up to: 3.5.1
Stable tag: 1.0.0 Development-Version

Mingle Forum allows you to easily and quickly put a Forum on your WordPress site/blog.

== Description ==
**The best free WordPress forum plugin available at wordpress.org!** Mingle Forum is so easy to setup and manage, that you'll be up and running in no time! It integrates seamlessly with WordPress and its user management system, whicn means you don't have to make your users signup twice! Best of all, you don't have to try and manage two separate applications and databases. Mingle Forum comes with many standard forum featues, and also leverages WordPress permalinks for pretty SEO friendly URL's and page titles. So what are you waiting for? Give it a try, you won't regret it!!!

= Features (NOT UP2DATE!) =
* **NEW!** Now Multisite - Network Activate - friendly
* Image uploads
* SEO Friendly URLs
* Forum Sitemap
* Automatic Media embedding into Forum Topics and Replies (like Youtube, Flickr, Photobucket...)
* Quick Reply
* User Groups
* Captcha
* Search Forums
* Guests can create Topics/Replies if you allow it
* Sticky (Pinned) Topics
* Move, Edit, Remove and Close Topics
* Works out of the box with most themes (See FAQ)
* Supports multiple languages

= Notes =
* As of Mingle Forum Lite - we are officially dropping support for versions of WP older than 3.5. That's not to say it doesn't work with older versions, it just means we are not officially supporting them. We are also dropping official support for the Mingle Social Networking Plugin (by Blair Williams).

= Translations (NOT UP2DATE!) =
* Arabic
* Brazilian Portuguese
* Bulgarian
* Croatian
* Czech
* Danish
* Dutch
* Estonian
* Finnish
* French
* Georgian
* German
* Hebrew
* Hungarian
* Indonesian
* Italian
* Japanese
* Latvian
* Persian
* Polish
* Romanian
* Russian
* Simplified Chinese
* Slovak
* Slovanian
* Spanish
* Swedish
* Thai
* Traditional Chinese
* Turkish

== Installation ==
* Download Mingle Forum Lite
* Create a new page for your forum to display on
* Head to the page you just created for the forum, paste [mingleforum] and save, (NOTE: It's best to paste that under the HTML/Text tab of your page editor)
* Head to Dashboard -> Plugins -> Add New -> Upload
* Browse to the .zip file you downloaded and click open
* Click Install
* Click Activate
* Youll now see a new admin menu called Mingle Forum
* Configure your Mingle Forum Lite options, Set a Skin, create your Categories and Forums
* DONE! It is that easy

== Frequently Asked Questions ==
* **My users can't register?** - Dashboard -> Settings -> General -> Anyone can register -> Save
* **Help! I can't create new topics** - Make sure you have watched the setup videos and have created both categories AND forums.
* **How can I hide the sidebar on the forum page?** - When editing the forum page, if your theme supports it, you should be able to change your Template to a full-width one. If you don't see this option, either hire a developer to make one for you, or bug your theme author to add it.
* **I made customizations to the forum, but they get overwritten on every update** - If you customize anything inside of the /mingle-forum/ folder it will be overwritten, there's no way around that. If all you're changing is CSS, then put it somewhere else, like at the bottom of your theme's styles.css file (which gets overwritten when you upgrade your theme FYI).
* **My skin looks bad/funny** - Your theme is most likely causing a conflict with the forum's table styles. We do our best, but can't possibly make the forum work for every conceivable WordPress theme. Find a friend who knows a thing or two about CSS and see if they'll help you get some custom styles set to fix the issue.
* **Can I put different forums on different pages?** - No. Not right now, and maybe not ever.
* **SEO friedly URL's are not working** - Make sure you have permalinks enabled in WordPress. Any setting but "default" should work. We personally like Day & Name for blogs, or Postname for non-blogs.
* **Google isn't indexing my forum pages** - Step 1: Set WordPress Permalink settings to anything but default, then enable SEO Friendly URLs in the forum's options. Step 2: Make sure you don't have Canonical URLs enabled on the forum page by another plugin like All In One SEO Pack. Mingle Forum implements some of its own SEO features making this unneccessary.
* **I can only see the front page of the forum. No matter what I click, it goes back to the front page** - Step 1: Make sure the forum isn't the home page of your site. Mingle Forum does not currently work when set to the home page. Step 2: Try enabling or disabling SEO Friendly URL's in the Forum Options. Step 3: Are you using WP SEO by Yoast? If so, disable the option to rewrite ugly permalinks.

== Upgrade Notice ==
* Mingle Forum Lite contains some significant changes. Please do NOT upgrade from the original Mingle Forum plugin!

== Changelog ==
= 1.0.0 Development-Version =
* Removed: unused field in database
* Fixed: possible bug with generated IDs in usergroup administration
* Changed: shortening of long topic titles
* Removed: user registration date from topics
* Changed: forum style
* Removed: inputosaurus.js library from administration area
* Changed: revised administration area
* Removed: admin notifications functionality
* Removed: locked categories functionality
* Changed: guests must always fill out captcha
* Removed: spam time interval check
* Changed: css code cleanup and fixes
* Removed: forum header
* Changed: position of 'move topic' button
* Removed: moderator functionality
* Changed: cleaned up forum overview
* Removed: profile functionality
* Removed: settings area in frontend
* Removed: edit profile button
* Changed: using date/time format from WordPress settings
* Removed: login/logout/register functionality
* Removed: forum/topic subscription functionality
* Changed: hide avatars outside of topics
* Removed: show unread topics functionality
* Removed: info center
* Removed: category shrink/expand functionality
* Removed: category view
* Removed: recent replies widget
* Removed: integration with Cartpauj PM and Mingle
* Removed: rss feeds functionality
* Removed: attribution area
* Changed: renamed folder containing administration files
* Removed: files of old administration area
* Removed: skin management functionality
* Removed: about section in administration area
* Removed: hot/very hot topics functionality
* Removed: creation of forum topic on new WordPress post functionality
* Removed: user title functionality
* Removed: signature functionality
* Changed: plugin directory name to mingle-forum-lite
* Removed: monetize/advertising functionality
* Removed: accordion-behavior in administration options
* Initial commit
