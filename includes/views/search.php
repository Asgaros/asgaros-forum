<?php

if (!defined('ABSPATH')) exit;

$results = $this->getSearchResults();

if ($results) {
    echo '<div>'.$this->pageing('search').'<div class="clear"></div></div>';
}

echo '<div class="title-element">'.__('Search results:', 'asgaros-forum').' '.AsgarosForumSearch::$searchKeywords.'</div>';
echo '<div class="content-element">';

if ($results) {
    $elementMarker = '';
    $elementsCounter = 0;
    foreach ($results as $thread) {
        $elementsCounter++;
        $elementMarker = ($elementsCounter & 1) ? 'odd' : 'even';
        require('thread-element.php');
    }
} else {
    echo __('No results found for:', 'asgaros-forum').' <b>'.AsgarosForumSearch::$searchKeywords.'</b>';
}

echo '</div>';
