<?php

if (!defined('ABSPATH')) exit;

echo '<h1 class="main-title">'.__('Search', 'asgaros-forum').'</h1>';

$results = AsgarosForumSearch::getSearchResults();
$pagination = new AsgarosForumPagination($this);
$paginationRendering = ($results) ? $pagination->renderPagination('search') : '';

if ($results) {
    echo '<div class="pages-and-menu">'.$paginationRendering.'<div class="clear"></div></div>';
}

echo '<div class="title-element">';
    echo __('Search results:', 'asgaros-forum').' '.AsgarosForumSearch::$searchKeywords;
    echo '<span class="last-post-headline">'.__('Last post:', 'asgaros-forum').'</span>';
echo '</div>';
echo '<div class="content-element">';

if ($results) {
    foreach ($results as $thread) {
        require('topic-element.php');
    }
} else {
    echo __('No results found for:', 'asgaros-forum').' <b>'.AsgarosForumSearch::$searchKeywords.'</b>';
}

echo '</div>';

if ($results) {
    echo '<div class="pages-and-menu">'.$paginationRendering.'<div class="clear"></div></div>';
}
