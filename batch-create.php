<?php
/*
Plugin Name: Batch Create NEW
Plugin URI: http://premium.wpmudev.org/project/batch-create
Description: Create hundred or thousands of blogs and users automatically by simply uploading a csv text file - subdomain and user creation automation has never been so easy.
Author: Ignacio (Incsub)
Text Domain: batch_create
Version: 1.3
Network: true
Author URI: http://premium.wpmudev.org/
WDP ID: 84
*/

/**
 * The main class of the plugin
 */

class Incsub_Batch_Create {

	// The version slug for the DB
	public static $version_option_slug = 'incsub_batch_create_version';

	// Admin pages. THey could be accesed from other points
	// So they're statics
	static $network_main_menu_page;

	static $batch_creator;

	public function __construct() {
		$this->set_globals();

		$this->includes();

		add_action( 'init', array( &$this, 'maybe_upgrade' ) );

		add_action( 'init', array( &$this, 'init_plugin' ) );

		add_action( 'plugins_loaded', array( &$this, 'load_text_domain' ) );

		add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_styles' ) );

		// We don't use the activation hook here
		// As sometimes is not very helpful and
		// we would need to check stuff to install not only when
		// we activate the plugin
		register_deactivation_hook( __FILE__, array( &$this, 'deactivate' ) );

	}

	public function enqueue_scripts() {
	}


	public function enqueue_styles() {
	}



	/**
	 * Set the plugin constants
	 */
	private function set_globals() {

		//TODO: Change the constant names

		// Basics
		define( 'INCSUB_BATCH_CREATE_VERSION', '1.3' );
		define( 'INCSUB_BATCH_CREATE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		define( 'INCSUB_BATCH_CREATE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		define( 'INCSUB_BATCH_CREATE_PLUGIN_FILE_DIR', plugin_dir_path( __FILE__ ) . 'batch-create.php' );

		// Language domain
		define( 'INCSUB_BATCH_CREATE_LANG_DOMAIN', 'incsub_batch_create' );

		// URLs
		define( 'INCSUB_BATCH_CREATE_ASSETS_URL', INCSUB_BATCH_CREATE_PLUGIN_URL . 'assets/' );

		// Dirs
		define( 'INCSUB_BATCH_CREATE_ADMIN_DIR', INCSUB_BATCH_CREATE_PLUGIN_DIR . 'admin/' );
		define( 'INCSUB_BATCH_CREATE_MODEL_DIR', INCSUB_BATCH_CREATE_PLUGIN_DIR . 'model/' );
		define( 'INCSUB_BATCH_CREATE_INCLUDES_DIR', INCSUB_BATCH_CREATE_PLUGIN_DIR . 'inc/' );

	}

	/**
	 * Include files needed
	 */
	private function includes() {
		// Model
		require_once( INCSUB_BATCH_CREATE_MODEL_DIR . 'model.php' );

		// Libraries
		require_once( INCSUB_BATCH_CREATE_INCLUDES_DIR . 'admin-page.php' );
		require_once( INCSUB_BATCH_CREATE_INCLUDES_DIR . 'errors-handler.php' );
		require_once( INCSUB_BATCH_CREATE_INCLUDES_DIR . 'helpers.php' );
		require_once( INCSUB_BATCH_CREATE_INCLUDES_DIR . 'creator.php' );

		// Admin Pages
		require_once( INCSUB_BATCH_CREATE_ADMIN_DIR . 'pages/network-main-page.php' );
		require_once( INCSUB_BATCH_CREATE_ADMIN_DIR . 'tables/queue-table.php' );
	}

	/**
	 * Upgrade the plugin when a new version is uploaded
	 */
	public function maybe_upgrade() {
		$current_version = get_site_option( self::$version_option_slug );

		if ( ! $current_version )
			$current_version = '1.2.3'; // This is the first version that includes some upgradings

		// For the second version, we're just saving the version in DB
		if ( version_compare( $current_version, '1.2.3', '<=' ) ) {
			require_once( INCSUB_BATCH_CREATE_INCLUDES_DIR . 'upgrade.php' );
			// Call upgrade functions here
		}

		//update_site_option( self::$version_option_slug, INCSUB_BATCH_CREATE_VERSION );
	}

	/**
	 * Actions executed when the plugin is deactivated
	 */
	public function deactivate() {
		// HEY! Do not delete anything from DB here
		// You better use the uninstall functionality
	}

	/**
	 * Load the plugin text domain and MO files
	 * 
	 * These can be uploaded to the main WP Languages folder
	 * or the plugin one
	 */
	public function load_text_domain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), INCSUB_BATCH_CREATE_LANG_DOMAIN );

		load_textdomain( INCSUB_BATCH_CREATE_LANG_DOMAIN, WP_LANG_DIR . '/' . INCSUB_BATCH_CREATE_LANG_DOMAIN . '/' . INCSUB_BATCH_CREATE_LANG_DOMAIN . '-' . $locale . '.mo' );
		load_plugin_textdomain( INCSUB_BATCH_CREATE_LANG_DOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
	}

	/**
	 * Initialize the plugin
	 */
	public function init_plugin() {

		// A network menu
		$args = array(
			'parent' => 'settings.php',
			'menu_title' => __( 'Batch Create', INCSUB_BATCH_CREATE_LANG_DOMAIN ),
			'page_title' => __( 'Batch Create', INCSUB_BATCH_CREATE_LANG_DOMAIN ),
			'network_menu' => true,
			'screen_icon_slug' => 'batch-create',
			'tabs' => array(
				'upload' => __( 'Batch Create', INCSUB_BATCH_CREATE_LANG_DOMAIN ),
				'queue' => __( 'Current queue', INCSUB_BATCH_CREATE_LANG_DOMAIN ),
				'log-file' => __( 'Log File', INCSUB_BATCH_CREATE_LANG_DOMAIN )
				
			)
		);
		self::$network_main_menu_page = new Batch_Create_Network_Main_Menu( 'batch-create-menu', 'manage_network', $args );

		self::$batch_creator = new Incsub_Batch_Create_Creator();
	}
}

new Incsub_Batch_Create();