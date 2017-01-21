<?php

header('Content-type: text/css; charset: UTF-8');

if (!empty($_GET['color']) && preg_match('/#([a-fA-F0-9]{3}){1,2}\b/', '#'.$_GET['color'])) {
	$color = '#'.$_GET['color'];
?>
#af-wrapper a,
#af-wrapper .unread:before,
#af-wrapper #topic-subscription,
#af-wrapper #forum-subscription {
	color: <?php echo $color; ?> !important;
}
#af-wrapper input[type="submit"],
#af-wrapper .forum-menu a,
#af-wrapper .pages a,
#af-wrapper .title-element,
#af-wrapper .post-author-marker {
    background-color: <?php echo $color; ?> !important;
}
#af-wrapper .title-element,
#af-wrapper .post-author-marker,
#af-wrapper .forum-menu a {
    border-bottom: 2px solid <?php echo $color; ?> !important;
}
#af-wrapper input[type="submit"] {
	border: 1px solid <?php echo $color; ?> !important;
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
<?php
}

if (!empty($_GET['background-color']) && preg_match('/#([a-fA-F0-9]{3}){1,2}\b/', '#'.$_GET['background-color'])) {
	$background_color = '#'.$_GET['background-color'];
?>
#af-wrapper .content-element,
#af-wrapper .content-element .odd,
#af-wrapper #statistics {
    background-color: <?php echo $background_color; ?> !important;
}
<?php
}
?>
