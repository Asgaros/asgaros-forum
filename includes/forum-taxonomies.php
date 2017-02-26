<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumTaxonomies {
	public function __construct() {
		register_taxonomy(
            'asgarosforum-category',
            null,
            array(
                'public' => false,
                'rewrite' => false
            )
        );
	}
}
