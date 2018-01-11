<?php

if (!defined('ABSPATH')) exit;

$results = AsgarosForumSearch::getSearchResults();
$pagination = new AsgarosForumPagination($this);
$paginationRendering = ($results) ? '<div class="pages-and-menu">'.$pagination->renderPagination('search').'<div class="clear"></div></div>' : '';

echo $paginationRendering;

echo '<div class="title-element">';
    echo __('Search results:', 'asgaros-forum').' '.AsgarosForumSearch::$searchKeywordsForOutput;
    echo '<span class="last-post-headline">'.__('Last post', 'asgaros-forum').'</span>';
echo '</div>';
echo '<div class="content-element">';

if ($results) {
    foreach ($results as $topic) {
        require('topic-element.php');
    }
} else {
    echo '<div class="notice">'.__('No results found for:', 'asgaros-forum').' <b>'.AsgarosForumSearch::$searchKeywordsForOutput.'</b></div>';
}

echo '</div>';

echo $paginationRendering;
