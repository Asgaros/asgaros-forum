<?php

if (!defined('ABSPATH')) exit;

$results = $this->search->get_search_results();
$paginationRendering = ($results) ? '<div class="pages-and-menu">'.$this->pagination->renderPagination('search').'<div class="clear"></div></div>' : '';
echo $paginationRendering;

echo '<div class="title-element">';
    echo __('Search results:', 'asgaros-forum').' '.$this->search->search_keywords_for_output;
    echo '<span class="last-post-headline">'.__('Last post', 'asgaros-forum').'</span>';
echo '</div>';
echo '<div class="content-element">';

if ($results) {
    foreach ($results as $topic) {
        $this->render_topic_element($topic);
    }
} else {
    echo '<div class="notice">'.__('No results found for:', 'asgaros-forum').' <b>'.$this->search->search_keywords_for_output.'</b></div>';
}

echo '</div>';

echo $paginationRendering;
