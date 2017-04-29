<?php

if (!defined('ABSPATH')) exit;

class AsgarosForumTaxonomies {
	private static $asgarosforum = null;

	public function __construct($object) {
		self::$asgarosforum = $object;

		add_action('init', array($this, 'initialize'));
	}

	public function initialize() {
		register_taxonomy(
            'asgarosforum-category',
            null,
            array(
                'public' => false,
                'rewrite' => false
            )
        );

		register_taxonomy(
			'asgarosforum-usergroup',
			null,
			array(
				'public' => false,
				'rewrite' => false
			)
		);
	}
}
