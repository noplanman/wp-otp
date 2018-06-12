<?php
/**
 * User metadata management
 *
 * @package    Wp_Otp
 * @subpackage User_Meta
 * @since      0.1.0
 */

namespace Wp_Otp;

/**
 * Manage all the user metadata.
 *
 * @since 0.1.0
 */
class Wp_Otp_User_Meta {
	/**
	 * Instance of this class.
	 *
	 * @since 0.1.0
	 * @var Wp_Otp_User_Meta
	 */
	private static $instance;

	/**
	 * Meta key to save the data in the user options.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	private static $user_meta_key = WP_OTP_SLUG;

	/**
	 * All default user meta.
	 *
	 * @since 0.1.0
	 * @var array
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
	 * All user meta.
	 *
	 * @since 0.1.0
	 * @var array
	 */
	private static $user_meta = array();

	/**
	 * User ID of the user whose meta data is managed.
	 *
	 * @since 0.1.0
	 * @var int
	 */
	private static $user_id = 0;

	/**
	 * Preload the user metadata on initialisation.
	 *
	 * @since 0.1.0
	 */
	private function __construct() {
		$this->fetch();
	}

	/**
	 * Create / Get the instance of this class.
	 *
	 * @todo  : This class needs updating, so that data isn't static for the first called user_id.
	 *
	 * @since 0.1.0
	 *
	 * @param int $user_id ID of the user to load the meta data for.
	 *
	 * @return Wp_Otp_User_Meta Instance of this class.
	 */
	public static function get_instance( $user_id = 0 ) {
		if ( null === self::$instance ) {
			self::$user_id  = $user_id ?: get_current_user_id();
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Fetch the saved user meta data, filling in with the default values.
	 *
	 * @since 0.1.0
	 *
	 * @return Wp_Otp_User_Meta Instance of this class.
	 */
	private function fetch() {
		if ( 0 === count( self::$user_meta ) ) {
			self::$user_meta = wp_parse_args(
				get_user_meta( self::$user_id, self::$user_meta_key, true ),
				self::$default_user_meta
			);
		}

		return $this;
	}

	/**
	 * Get a specific option.
	 *
	 * @since 0.1.0
	 *
	 * @param string $key     ID of user meta to get.
	 * @param mixed  $default Override default value if option not found.
	 *
	 * @return mixed Requested option value.
	 */
	public function get( $key = null, $default = null ) {
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

		return $default;
	}

	/**
	 * Get all user meta.
	 *
	 * @since 0.1.0
	 *
	 * @return array All the user meta.
	 */
	public function get_all() {
		return self::$user_meta;
	}

	/**
	 * Set a certain user meta.
	 *
	 * @since 0.1.0
	 *
	 * @param string $key   ID of option to get.
	 * @param mixed  $value Value to be set for the passed option.
	 * @param bool   $save  Save the user meta immediately after setting them.
	 *
	 * @return Wp_Otp_User_Meta Instance of this class.
	 */
	public function set( $key, $value, $save = false ) {
		if ( null !== $key ) {
			if ( null !== $value ) {
				self::$user_meta[ $key ] = $value;
			} else {
				unset( self::$user_meta[ $key ] );
			}
		}

		$save && $this->save();

		return $this;
	}

	/**
	 * Set multiple user metas.
	 *
	 * @since 0.1.0
	 *
	 * @param array $metas Key-Value pairs of user meta to set.
	 * @param bool  $save  Save the user meta immediately after setting them.
	 *
	 * @return Wp_Otp_User_Meta Instance of this class.
	 */
	public function set_all( $metas, $save = false ) {
		foreach ( $metas as $key => $value ) {
			$this->set( $key, $value );
		}

		$save && $this->save();

		return $this;
	}

	/**
	 * Save the user meta.
	 *
	 * @since 0.1.0
	 *
	 * @return Wp_Otp_User_Meta Instance of this class.
	 */
	public function save() {
		update_user_meta( self::$user_id, self::$user_meta_key, self::$user_meta );

		return $this;
	}

	/**
	 * Clear the user meta.
	 *
	 * @since 0.1.0
	 *
	 * @return Wp_Otp_User_Meta Instance of this class.
	 */
	public static function clear() {
		$user_id = self::$user_id ?: get_current_user_id();
		if ( delete_user_meta( $user_id, self::$user_meta_key ) ) {
			// Reset instance.
			self::$instance = null;
		}

		return self::get_instance( $user_id );
	}
}
