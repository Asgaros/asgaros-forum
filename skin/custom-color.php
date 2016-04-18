<?php header('Content-type: text/css; charset: UTF-8'); ?>

<?php
if (isset($_GET['color']) && !empty($_GET['color']) && preg_match('/#([a-fA-F0-9]{3}){1,2}\b/', '#'.$_GET['color'])) {
$color = '#'.$_GET['color'];
?>
#af-wrapper a,
#af-wrapper .breadcrumbs a:hover,
#af-wrapper .unread:before {
	color: <?php echo $color; ?> !important;
}
#af-wrapper input[type="submit"],
#af-wrapper .forum-menu a,
#af-wrapper .pages a,
#af-wrapper .title-element {
    background-color: <?php echo $color; ?> !important;
}
#af-wrapper .content-element {
    border: 1px solid <?php echo $color; ?> !important;
}
<?php
}
?>

<?php
if (isset($_GET['text-color']) && !empty($_GET['text-color']) && preg_match('/#([a-fA-F0-9]{3}){1,2}\b/', '#'.$_GET['text-color'])) {
$text_color = '#'.$_GET['text-color'];
?>
#af-wrapper,
#af-wrapper .breadcrumbs,
#af-wrapper .breadcrumbs a {
    color: <?php echo $text_color; ?> !important;
}
<?php
}
?>

<?php
if (isset($_GET['background-color']) && !empty($_GET['background-color']) && preg_match('/#([a-fA-F0-9]{3}){1,2}\b/', '#'.$_GET['background-color'])) {
$background_color = '#'.$_GET['background-color'];
?>
#af-wrapper .content-element {
    background-color: <?php echo $background_color; ?> !important;
}
<?php
}
?>
