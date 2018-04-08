<?php

header('Content-type: text/css; charset: UTF-8');

if (!empty($_GET['color']) && preg_match('/#([a-fA-F0-9]{3}){1,2}\b/', '#'.$_GET['color'])) {
	$color = '#'.$_GET['color'];
?>
#af-wrapper a,
#af-wrapper .unread:before,
#af-wrapper #topic-subscription,
#af-wrapper #forum-subscription,
#af-wrapper .forum-post-menu a,
#af-wrapper #forum-profile .display-name,
#af-wrapper input[type="checkbox"]:checked:before {
	color: <?php echo $color; ?> !important;
}
#af-wrapper input[type="submit"],
#af-wrapper .forum-menu a,
#af-wrapper .title-element,
#af-wrapper .post-author-marker,
#af-wrapper #forum-header,
#af-wrapper #profile-header .background-avatar,
#af-wrapper #read-unread .unread,
#af-wrapper input[type="radio"]:checked:before {
    background-color: <?php echo $color; ?> !important;
}
#af-wrapper .title-element,
#af-wrapper .post-author-marker,
#af-wrapper .forum-menu a,
#af-wrapper input[type="submit"],
#af-wrapper #forum-search,
#af-wrapper #forum-header,
#af-wrapper #forum-navigation a,
#af-wrapper input[type="radio"]:focus,
#af-wrapper input[type="checkbox"]:focus {
	border-color: <?php echo $color; ?> !important;
}
<?php
}

if (!empty($_GET['text-color']) && preg_match('/#([a-fA-F0-9]{3}){1,2}\b/', '#'.$_GET['text-color'])) {
	$text_color = '#'.$_GET['text-color'];
?>
#af-wrapper,
#af-wrapper .main-title {
    color: <?php echo $text_color; ?> !important;
}
#af-wrapper #read-unread .read {
	background-color: <?php echo $text_color; ?> !important;
}
<?php
}

if (!empty($_GET['background-color']) && preg_match('/#([a-fA-F0-9]{3}){1,2}\b/', '#'.$_GET['background-color'])) {
	$background_color = '#'.$_GET['background-color'];
?>
#af-wrapper .content-element,
#af-wrapper .content-element .odd,
#af-wrapper #statistics,
#af-wrapper #statistics-online-users,
#af-wrapper #forum-profile,
#af-wrapper .post-element,
#af-wrapper .post-message,
#af-wrapper .topic-sticky,
#af-wrapper .topic-sticky .topic-poster {
    background-color: <?php echo $background_color; ?> !important;
}
<?php
}

if (!empty($_GET['border-color']) && preg_match('/#([a-fA-F0-9]{3}){1,2}\b/', '#'.$_GET['border-color'])) {
	$border_color = '#'.$_GET['border-color'];
?>
#af-wrapper .forum,
#af-wrapper .topic,
#af-wrapper .member,
#af-wrapper .forum-poster,
#af-wrapper .topic-poster,
#af-wrapper .member-last-seen,
#af-wrapper .subscription,
#af-wrapper .content-element,
#af-wrapper .forum-post-header,
#af-wrapper #statistics-body,
#af-wrapper #statistics .statistics-element,
#af-wrapper #statistics-online-users,
#af-wrapper #forum-profile,
#af-wrapper #profile-header,
#af-wrapper .editor-row,
#af-wrapper .editor-row-subject,
#af-wrapper .sticky-bottom,
#af-wrapper .signature,
#af-wrapper .post-element,
#af-wrapper .post-message,
#af-wrapper .forum-subforums,
#af-wrapper .edit-profile-link,
#af-wrapper .uploaded-file img,
#af-wrapper .subscription-option,
#af-wrapper .topic-sticky,
#af-wrapper .topic-sticky .topic-poster {
    border-color: <?php echo $border_color; ?> !important;
}
<?php
}

if (!empty($_GET['font'])) {
	$font = $_GET['font'];
?>
#af-wrapper {
    font-family: <?php echo $font; ?> !important;
}
<?php
}

if (!empty($_GET['font-size'])) {
	$font_size = $_GET['font-size'];
?>
#af-wrapper {
    font-size: <?php echo $font_size; ?> !important;
}
<?php
}
?>