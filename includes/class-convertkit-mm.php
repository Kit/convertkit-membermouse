<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    ConvertKit_MM
 * @subpackage ConvertKit_MM/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    ConvertKit_MM
 * @subpackage ConvertKit_MM/includes
 * @author     Daniel Espnioza <daniel@growdevelopment.com>
 */
class ConvertKit_MM {

	/**
	 * Holds the class object.
	 *
	 * @since   1.2.0
	 *
	 * @var     object
	 */
	private static $instance;

	/**
	 * Holds singleton initialized classes that include
	 * action and filter hooks.
	 *
	 * @since   1.2.0
	 *
	 * @var     array
	 */
	private $classes = array();

	/**
	 * Constructor. Acts as a bootstrap to load the rest of the plugin
	 *
	 * @since   1.0.0
	 */
	public function __construct() {

		// Initialize.
		add_action( 'init', array( $this, 'init' ) );

	}

	/**
	 * Initialize admin, frontend and global Plugin classes.
	 *
	 * @since   1.2.0
	 */
	public function init() {

		// Initialize class(es) to register hooks.
		$this->initialize_admin();
		$this->initialize_frontend();
		$this->initialize_global();

	}

	/**
	 * Initialize classes for the WordPress Administration interface
	 *
	 * @since   1.2.0
	 */
	private function initialize_admin() {

		// Bail if this request isn't for the WordPress Administration interface.
		if ( ! is_admin() ) {
			return;
		}

		$this->classes['admin'] = new ConvertKit_MM_Admin();

		/**
		 * Initialize integration classes for the WordPress Administration interface.
		 *
		 * @since   1.2.2
		 */
		do_action( 'convertkit_membermouse_initialize_admin' );

	}

	/**
	 * Initialize classes for the frontend web site
	 *
	 * @since   1.2.0
	 */
	private function initialize_frontend() {

		// Bail if this request isn't for the frontend web site.
		if ( is_admin() ) {
			return;
		}

		/**
		 * Initialize integration classes for the frontend web site.
		 *
		 * @since   1.2.0
		 */
		do_action( 'convertkit_membermouse_initialize_frontend' );

	}

	/**
	 * Initialize classes required globally, across the WordPress Administration, CLI, Cron and Frontend
	 * web site.
	 *
	 * @since   1.2.0
	 */
	private function initialize_global() {

		$this->classes['actions'] = new ConvertKit_MM_Actions();

		/**
		 * Initialize integration classes for the frontend web site.
		 *
		 * @since   1.2.0
		 */
		do_action( 'convertkit_membermouse_initialize_global' );

	}

	/**
	 * Returns the given class
	 *
	 * @since   1.2.0
	 *
	 * @param   string $name   Class Name.
	 * @return  object          Class Object
	 */
	public function get_class( $name ) {

		// If the class hasn't been loaded, throw a WordPress die screen
		// to avoid a PHP fatal error.
		if ( ! isset( $this->classes[ $name ] ) ) {
			// Define the error.
			$error = new WP_Error(
				'convertkit_membermouse_get_class',
				sprintf(
					/* translators: %1$s: PHP class name */
					__( 'Kit for MemberMouse Error: Could not load Plugin class <strong>%1$s</strong>', 'convertkit-mm' ),
					$name
				)
			);

			// Depending on the request, return or display an error.
			// Admin UI.
			if ( is_admin() ) {
				wp_die(
					esc_attr( $error->get_error_message() ),
					esc_html__( 'Kit for MemberMouse Error', 'convertkit-mm' ),
					array(
						'back_link' => true,
					)
				);
			}

			// Cron / CLI.
			return $error;
		}

		// Return the class object.
		return $this->classes[ $name ];

	}

	/**
	 * Returns the singleton instance of the class.
	 *
	 * @since   1.2.0
	 *
	 * @return  object Class.
	 */
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;

	}

}
