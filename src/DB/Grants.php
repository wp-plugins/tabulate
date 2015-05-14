<?php

namespace WordPress\Tabulate\DB;

/**
 * The Tabulate 'Grants' is a list of table names, and for each table a record
 * of what roles have each capability.
 */
class Grants {

	const READ = 'read';
	const CREATE = 'create';
	const UPDATE = 'update';
	const DELETE = 'delete';
	const IMPORT = 'import';

	private $option_name;

	public function __construct() {
		$this->option_name = TABULATE_SLUG . '_grants';
		add_option( $this->option_name, '', null, false );
	}

	public function get_capabilities() {
		return array(
			self::READ,
			self::CREATE,
			self::UPDATE,
			self::DELETE,
			self::IMPORT,
		);
	}

	public function get_roles() {
		$roles = array();
		foreach ( get_editable_roles() as $role_name => $role ) {
			$roles[ $role_name ] = $role[ 'name' ];
		}
		return $roles;
	}

	/**
	 * Get all stored capabilities, or optionally only those for a particular
	 * table.
	 *
	 * @param string $table A database table name.
	 * @return array
	 */
	public function get($table = null) {
		$options = get_option( $this->option_name, array() );
		if ($table && isset($options[$table] ) ) {
			return $options[$table];
		}
		return $options;
	}

	public function set($grants) {
		update_option( $this->option_name, $grants );
	}

	/**
	 * Check that the current user has the requested capability.
	 *
	 * @param array $all_capabilities The full list of capabilities granted (to add to).
	 * @param array $caps The capabilities being checked.
	 * @param array $args Values being passed in by `current_user_can()`.
	 *
	 * @return array
	 */
	public static function check( $all_capabilities, $caps, $args ) {

		// See if it's one of our capabilities being checked.
		$cap_full_name = array_shift( $caps );
		if ( stripos( $cap_full_name, TABULATE_SLUG ) === false) {
			return $all_capabilities;
		}
		// Strip the leading 'tabulate_' from the capability name.
		$cap = substr( $cap_full_name, strlen( TABULATE_SLUG ) + 1 );

		// Set up basic data.
		$table_name = ($args[2]) ? $args[2] : false;
		$grants = new self();

		// Users with 'promote_users' capability can do everything.
		if ( isset( $all_capabilities['promote_users'] ) ) {
			$all_capabilities[ $cap_full_name ] = true;
		}

		// Table has no grants, or doesn't have this one.
		$table_grants = $grants->get( $table_name );
		if ( !$table_grants || !isset( $table_grants[$cap] ) ) {
			return $all_capabilities;
		}

		// Table has grants of this capability; check whether the user has one
		// of the roles with this capability.
		$user = wp_get_current_user();
		//var_dump($user->roles);
		//var_dump($table_grants[$cap]);
		//exit();
		$intersect = array_intersect( $table_grants[$cap], $user->roles );
		//var_dump($intersect);
		if ( count( $intersect ) > 0 ) {
			$all_capabilities[ $cap_full_name ] = true;
		}

		return $all_capabilities;

	}

	public static function current_user_can( $grant, $table_name ) {
		$capability = TABULATE_SLUG . '_' . $grant;
		return current_user_can( $capability, $table_name );
	}

}
