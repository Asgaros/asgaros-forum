<?php

if (!defined('ABSPATH')) exit;

/**
 * @param array $query {
 *     Query arguments. All items are optional.
 *     @type string            $type                Determines sort order. Select from 'newest', 'active', 'online',
 *                                                  'random', 'popular', 'alphabetical'. Default: 'newest'.
 *     @type int               $per_page            Number of results to return. Default: 0 (no limit).
 *     @type int               $page                Page offset (together with $per_page). Default: 1.
 *     @type string|bool       $search_terms        Terms to search by. Search happens across xprofile fields. Requires
 *                                                  XProfile component. Default: false.
 *     @type array|string|bool $include             An array or comma-separated list of user IDs to which query should
 *                                                  be limited. Default: false.
 *     @type array|string|bool $exclude             An array or comma-separated list of user IDs that will be excluded
 *                                                  from query results. Default: false.
 *     @type array|string|bool $user_ids            An array or comma-separated list of IDs corresponding to the users
 *                                                  that should be returned. When this parameter is passed, it will
 *                                                  override all others; BP User objects will be constructed using these
 *                                                  IDs only. Default: false.
 *     @type string|bool       $meta_key            Limit results to users that have usermeta associated with this meta_key.
 *                                                  Usually used with $meta_value. Default: false.
 *     @type string|bool       $meta_value          When used with $meta_key, limits results to users whose usermeta value
 *                                                  associated with $meta_key matches $meta_value. Default: false.
 *     @type bool              $populate_extras     True if you want to fetch extra metadata
 *                                                  about returned users, such as total group and friend counts.
 *     @type string            $count_total         Determines how BP_User_Query will do a count of total users matching
 *                                                  the other filter criteria. Default value is 'count_query', which
 *                                                  does a separate SELECT COUNT query to determine the total.
 *                                                  'sql_count_found_rows' uses SQL_COUNT_FOUND_ROWS and
 *                                                  SELECT FOUND_ROWS(). Pass an empty string to skip the total user
 *                                                  count query.
 * }
 */
class AsgarosForumUserQuery {
	// TODO: Check required variables.
	// Array of variables to query with.
	public $query_vars = array();

	// List of found users and their respective data.
	public $results = array();

	// Total number of found users for the current query.
	public $total_users = 0;

	// List of found user IDs.
	public $user_ids = array();

	// SQL clauses for the user ID query.
	public $uid_clauses = array();

	// SQL table where the user ID is being fetched from.
	public $uid_table = '';

	// SQL database column name to order by.
	public $uid_name = '';

	// Standard response when the query should not return any rows.
	protected $no_results = array('join' => '', 'where' => '0 = 1');

	// Constructor.
	public function __construct($query = null) {
		// Cancel if no query is given.
		if (empty($query)) {
			return;
		}

		// TODO: Check required arguments.
		$this->query_vars = wp_parse_args($query, array(
			'type'                => 'alphabetical',
			'per_page'            => 0,
			'page'                => 1,
			'search_terms'        => false,
			'include'             => false,
			'exclude'             => false,
			'user_ids'            => false,
			'meta_key'            => false,
			'meta_value'          => false,
			'populate_extras'     => true,
			'count_total'         => 'count_query'
		));

		// Get user ids. If the user_ids param is present, we skip the query.
		// TODO: Check if necessary.
		if ($this->query_vars['user_ids'] !== false) {
			$this->user_ids = wp_parse_id_list($this->query_vars['user_ids']);
		} else {
			$this->prepare_user_ids_query();
			$this->do_user_ids_query();
		}

		// Cancel if no user IDs were found.
		if (empty($this->user_ids)) {
			return;
		}

		// Fetch additional data. First, using WP_User_Query.
		$this->do_wp_user_query();

		// Get BuddyPress specific user data.
		$this->populate_extras();
	}

	// Prepare the query for user_ids.
	public function prepare_user_ids_query() {
		global $wpdb;

		extract($this->query_vars);

		// Setup the main SQL query container.
		$sql = array(
			'select'  => '',
			'where'   => array('1=1'),
			'orderby' => '',
			'order'   => '',
			'limit'   => ''
		);

		// Determines the sort order, which means it also determines where the
		// user IDs are drawn from (the SELECT and WHERE statements).
		switch ($type) {
			// 'alphabetical' sorts depend on the xprofile setup.
			case 'alphabetical' :
				$this->uid_name		= 'ID';
				$this->uid_table	= $wpdb->users;
				$sql['select']		= "SELECT u.{$this->uid_name} as id FROM {$this->uid_table} u";
				$sql['orderby']		= "ORDER BY u.display_name";
				$sql['order']		= "ASC";

				// To ensure that spam/deleted/non-activated users
				// are filtered out, we add an appropriate sub-query.
				$user_status_filter = 'user_status = 0';

				if (is_multisite()) {
					$user_status_filter = 'spam = 0 AND deleted = 0 AND user_status = 0';
				}

				$sql['where'][]		= "u.{$this->uid_name} IN ( SELECT ID FROM {$wpdb->users} WHERE {$user_status_filter} )";
				break;
			// Any other 'type' falls through.
			default :
				$this->uid_name		= 'ID';
				$this->uid_table	= $wpdb->users;
				$sql['select']		= "SELECT u.{$this->uid_name} as id FROM {$this->uid_table} u";
				break;
		}

		// 'include' - User ids to include in the results.
		$include_ids = $include !== false ? wp_parse_id_list($include) : array();

		// An array containing nothing but 0 should always fail.
		if (count($include_ids) === 1 && reset($include_ids) === 0) {
			$sql['where'][] = $this->no_results['where'];
		} else if (!empty($include_ids)) {
			$include_ids = implode(',', wp_parse_id_list($include_ids));
			$sql['where'][] = "u.{$this->uid_name} IN ({$include_ids})";
		}

		// 'exclude' - User ids to exclude from the results.
		if ($exclude !== false) {
			$exclude_ids = implode(',', wp_parse_id_list($exclude));
			$sql['where'][] = "u.{$this->uid_name} NOT IN ({$exclude_ids})";
		}

		// 'search_terms' searches user_login and user_nicename.
		if ($search_terms !== false) {
			$search_terms = $wpdb->esc_like(wp_kses_normalize_entities($search_terms));
			$search_terms_nospace = $search_terms.'%';
			$search_terms_space = '% '.$search_terms.'%';

			$matched_user_ids = $wpdb->get_col($wpdb->prepare(
				"SELECT ID FROM {$wpdb->users} WHERE ( user_login LIKE %s OR user_login LIKE %s OR user_nicename LIKE %s OR user_nicename LIKE %s )",
				$search_terms_nospace,
				$search_terms_space,
				$search_terms_nospace,
				$search_terms_space
			));

			$match_in_clause = empty($matched_user_ids) ? 'NULL' : implode(',', $matched_user_ids);
			$sql['where']['search'] = "u.{$this->uid_name} IN ({$match_in_clause})";
		}

		// 'meta_key', 'meta_value' allow usermeta search.
		// To avoid global joins, do a separate query.
		if ($meta_key !== false) {
			$meta_sql = $wpdb->prepare("SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s", $meta_key);

			if ($meta_value !== false) {
				$meta_sql .= $wpdb->prepare(" AND meta_value = %s", $meta_value);
			}

			$found_user_ids = $wpdb->get_col($meta_sql);

			if (!empty($found_user_ids)) {
				$sql['where'][] = "u.{$this->uid_name} IN (".implode(',', wp_parse_id_list($found_user_ids)).")";
			} else {
				$sql['where'][] = '1 = 0';
			}
		}

		// 'per_page', 'page' - handles LIMIT.
		if (!empty($per_page) && !empty($page)) {
			$sql['limit'] = $wpdb->prepare("LIMIT %d, %d", intval(($page - 1)*$per_page), intval($per_page));
		} else {
			$sql['limit'] = '';
		}

		// Assemble the query chunks.
		$this->uid_clauses['select']  = $sql['select'];
		$this->uid_clauses['where']   = !empty($sql['where']) ? 'WHERE '.implode(' AND ', $sql['where']) : '';
		$this->uid_clauses['orderby'] = $sql['orderby'];
		$this->uid_clauses['order']   = $sql['order'];
		$this->uid_clauses['limit']   = $sql['limit'];
	}

	/**
	 * Query for IDs of users that match the query parameters.
	 *
	 * Perform a database query to specifically get only user IDs, using
	 * existing query variables set previously in the constructor.
	 *
	 * Also used to quickly perform user total counts.
	 */
	public function do_user_ids_query() {
		global $wpdb;

		// If counting using SQL_CALC_FOUND_ROWS, set it up here.
		if ($this->query_vars['count_total'] == 'sql_calc_found_rows') {
			$this->uid_clauses['select'] = str_replace('SELECT', 'SELECT SQL_CALC_FOUND_ROWS', $this->uid_clauses['select']);
		}

		// Get the specific user ids.
		$this->user_ids = $wpdb->get_col("{$this->uid_clauses['select']} {$this->uid_clauses['where']} {$this->uid_clauses['orderby']} {$this->uid_clauses['order']} {$this->uid_clauses['limit']}");

		// Get the total user count.
		if ($this->query_vars['count_total'] == 'sql_calc_found_rows') {
			$this->total_users = $wpdb->get_var("SELECT FOUND_ROWS()");
		} else if ($this->query_vars['count_total'] == 'count_query') {
			$count_select = preg_replace('/^SELECT.*?FROM (\S+) u/', "SELECT COUNT(u.{$this->uid_name}) FROM $1 u", $this->uid_clauses['select']);
			$this->total_users = $wpdb->get_var("{$count_select} {$this->uid_clauses['where']}");
		}
	}

	// Use WP_User_Query() to pull data for the user IDs retrieved in the main query.
	public function do_wp_user_query() {
		$fields = array('ID', 'user_login', 'user_nicename', 'user_email', 'user_url', 'user_registered', 'user_status', 'display_name');

		if (is_multisite()) {
			$fields[] = 'spam';
			$fields[] = 'deleted';
		}

		$wp_user_query = new WP_User_Query(array(
			// Relevant.
			'fields'      => $fields,
			'include'     => $this->user_ids,
			'count_total' => false // We already have a count.
		));

		// We calculate total_users using a standalone query, except
		// when a whitelist of user_ids is passed to the constructor.
		// This clause covers the latter situation, and ensures that
		// pagination works when querying by $user_ids.
		if (empty($this->total_users)) {
			$this->total_users = count($wp_user_query->results);
		}

		// Reindex for easier matching.
		$r = array();
		foreach ($wp_user_query->results as $u) {
			$r[$u->ID] = $u;
		}

		// Match up to the user ids from the main query.
		foreach ($this->user_ids as $key => $uid) {
			if (isset($r[$uid])) {
				$r[$uid]->ID = (int)$uid;
				$r[$uid]->user_status = (int)$r[$uid]->user_status;
				$this->results[$uid] = $r[$uid];
			// Remove user ID from original user_ids property.
			} else {
				unset($this->user_ids[$key]);
			}
		}
	}

	// Perform a database query to populate any extra metadata we might need.
	public function populate_extras() {
		global $wpdb;

		// Bail if no users.
		if (empty($this->user_ids) || empty($this->results)) {
			return;
		}

		// In the case of the 'popular' sort type, we force
		// populate_extras to true, because we need the friend counts.
		if ($this->query_vars['type'] == 'popular') {
			$this->query_vars['populate_extras'] = 1;
		}

		// Bail if the populate_extras flag is set to false.
		if (!(bool)$this->query_vars['populate_extras']) {
			return;
		}

		// Turn user ID's into a query-usable, comma separated value.
		$user_ids_sql = implode(',', wp_parse_id_list($this->user_ids));

		// TODO: More cleanup from here.
		/////////////////////////////////////////////////

		// When meta_key or meta_value have been passed to the query,
		// fetch the resulting values for use in the template functions.
		if ( ! empty( $this->query_vars['meta_key'] ) ) {
			$meta_sql = array(
				'select' => "SELECT user_id, meta_key, meta_value",
				'from'   => "FROM $wpdb->usermeta",
				'where'  => $wpdb->prepare( "WHERE meta_key = %s", $this->query_vars['meta_key'] )
			);

			if ( false !== $this->query_vars['meta_value'] ) {
				$meta_sql['where'] .= $wpdb->prepare( " AND meta_value = %s", $this->query_vars['meta_value'] );
			}

			$metas = $wpdb->get_results( "{$meta_sql['select']} {$meta_sql['from']} {$meta_sql['where']}" );

			if ( ! empty( $metas ) ) {
				foreach ( $metas as $meta ) {
					if ( isset( $this->results[ $meta->user_id ] ) ) {
						$this->results[ $meta->user_id ]->meta_key = $meta->meta_key;

						if ( ! empty( $meta->meta_value ) ) {
							$this->results[ $meta->user_id ]->meta_value = $meta->meta_value;
						}
					}
				}
			}
		}
	}
}
