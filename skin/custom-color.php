<?php
header('Content-type: text/css; charset: UTF-8');
if (isset($_GET['color']) && !empty($_GET['color']) && preg_match('/#([a-fA-F0-9]{3}){1,2}\b/', '#'.$_GET['color'])) {
$color = '#'.$_GET['color'];
?>
#af-wrapper a,
#af-wrapper .breadcrumbs a:hover,
#af-wrapper .icon-files-empty-small-yes:before,
#af-wrapper .icon-normal_closed-yes:before,
#af-wrapper .icon-sticky_closed-yes:before,
#af-wrapper .icon-normal_open-yes:before,
#af-wrapper .icon-sticky_open-yes:before,
#af-wrapper .icon-overview-yes:before {
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
