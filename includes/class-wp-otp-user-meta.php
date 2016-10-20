<?php
/**
 * User metadata management
 *
 * @package    Wp_Otp
 * @subpackage User_Meta
 * @since      0.1.0
 */

namespace Wp_Otp;

defined( 'WPINC' ) || exit;

/**
 * Manage all the user metadata.
 *
 * @since 0.1.0
 */
class Wp_Otp_User_Meta {
	/**
	 * @var Wp_Otp_User_Meta Instance of this class.
	 */
	private static $instance;

	/**
	 * @var string Meta key to save the data in the user options.
	 */
	private static $user_meta_key = WP_OTP_SLUG;

	/**
	 * @var array All default user meta.
	 */
	private static $default_user_meta = [
		'counter' => null,
		'digest'  => 'sha1',
		'digits'  => 6,
		'enabled' => false,
		'method'  => 'totp',
		'notices' => null,
		'period'  => 30,
		'secret'  => null,
	];

	/**
	 * @var array All user meta.
	 */
	private static $user_meta;


	private static $user_id;

	/** Singleton, keep private. */
	final private function __clone() {
	}

	/** Singleton, keep private. */
	final private function __construct( $user_id ) {
		self::$user_id = $user_id;
		$this->get_user_meta();
	}

	/**
	 * Create / Get the instance of this class.
	 *
	 * @todo: This class needs updating, so that data isn't static for the first called user_id.
	 *
	 * @param int $user_id ID of the user to load the meta data for.
	 *
	 * @return Wp_Otp_User_Meta Instance of this class.
	 */
	public static function get_instance( $user_id = null ) {
		if ( null === self::$instance ) {
			self::$instance = new self( $user_id ?: get_current_user_id() );
		}

		return self::$instance;
	}


	/**
	 * Get a specific option.
	 *
	 * @param string       $key     ID of user meta to get.
	 * @param array|string $default Override default value if option not found.
	 *
	 * @return array|string Requested option value.
	 */
	public function get_user_meta( $key = null, $default = null ) {
		if ( null === self::$user_meta ) {
			self::$user_meta = wp_parse_args(
				get_user_meta( self::$user_id, self::$user_meta_key, true ),
				self::$default_user_meta
			);
		}
		if ( null !== $key ) {
			if ( isset( self::$user_meta[ $key ] ) ) {
				// Return found option value.
				return self::$user_meta[ $key ];
			} elseif ( null !== $default ) {
				// Return overridden default value.
				return $default;
			} elseif ( isset( self::$default_user_meta[ $key ] ) ) {
				// Return default option value.
				return self::$default_user_meta[ $key ];
			}
		}
	}

	/**
	 * Get all user meta.
	 *
	 * @return array All the user meta.
	 */
	public function get_all_user_meta() {
		return self::$user_meta;
	}

	/**
	 * Set a certain user meta.
	 *
	 * @param string       $key   ID of option to get.
	 * @param array|string $value Value to be set for the passed option.
	 * @param bool         $save  Save the user meta immediately after setting them.
	 */
	public function set_user_meta( $key, $value, $save = false ) {
		if ( null !== $key ) {
			if ( null !== $value ) {
				self::$user_meta[ $key ] = $value;
			} else {
				unset( self::$user_meta[ $key ] );
			}
		}

		$save && $this->save();
	}

	/**
	 * Set multiple user metas.
	 *
	 * @param array $metas Key-Value pairs of user meta to set.
	 * @param bool  $save  Save the user meta immediately after setting them.
	 */
	public function set_user_metas( $metas, $save = false ) {
		foreach ( $metas as $key => $value ) {
			$this->set_user_meta( $key, $value );
		}

		$save && $this->save();
	}

	/**
	 * Save the user meta.
	 */
	public function save() {
		update_user_meta( self::$user_id, self::$user_meta_key, self::$user_meta );
	}

	/**
	 * Save the user meta.
	 */
	public static function delete() {
		delete_user_meta( self::$user_id, self::$user_meta_key );
		self::$user_meta = null;

		// Reset defaults.
		self::get_instance()->get_user_meta();
	}
}
