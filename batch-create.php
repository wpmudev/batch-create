<?php
/*
Plugin Name: Batch Create
Plugin URI: http://premium.wpmudev.org/project/batch-create
Description: Create hundred or thousands of blogs and users automatically by simply uploading a csv text file - subdomain and user creation automation has never been so easy.
Author: WPMU DEV
Text Domain: incsub_batch_create
Version: 1.5.2
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

		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

	}

	public function activate() {
		$model = batch_create_get_model();
		$model->create_schema();
		update_site_option( self::$version_option_slug, INCSUB_BATCH_CREATE_VERSION );
	}

	public function deactivate() {
		delete_site_option( self::$version_option_slug );
	}




	/**
	 * Set the plugin constants
	 */
	private function set_globals() {

		//TODO: Change the constant names

		// Basics
		define( 'INCSUB_BATCH_CREATE_VERSION', '1.5.1' );
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
		require_once( INCSUB_BATCH_CREATE_INCLUDES_DIR . 'integration.php' );

		// Admin Pages
		require_once( INCSUB_BATCH_CREATE_ADMIN_DIR . 'pages/network-main-page.php' );
		require_once( INCSUB_BATCH_CREATE_ADMIN_DIR . 'tables/queue-table.php' );

		global $wpmudev_notices;
		$wpmudev_notices[] = array( 'id'=> 84,'name'=> 'Batch Create', 'screens' => array( 'settings_page_batch-create-menu-network' ) );
		include_once( 'externals/wpmudev-dash-notification.php' );
	}

	/**
	 * Upgrade the plugin when a new version is uploaded
	 */
	public function maybe_upgrade() {
		$current_version = get_site_option( self::$version_option_slug );

		if ( $current_version === INCSUB_BATCH_CREATE_VERSION )
			return;

		if ( $current_version === false ) {
			$this->activate();
			return;
		}

		// For the second version, we're just saving the version in DB
		if ( version_compare( $current_version, '1.4', '<' ) ) {
			require_once( INCSUB_BATCH_CREATE_INCLUDES_DIR . 'upgrade.php' );
			batch_create_upgrade_14();
		}

		update_site_option( self::$version_option_slug, INCSUB_BATCH_CREATE_VERSION );
	}


	/**
	 * Load the plugin text domain and MO files
	 * 
	 * These can be uploaded to the main WP Languages folder
	 * or the plugin one
	 */
	public function load_text_domain() {
		load_plugin_textdomain( INCSUB_BATCH_CREATE_LANG_DOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Initialize the plugin
	 */
	public function init_plugin() {
		global $wpdb;

		require_once( INCSUB_BATCH_CREATE_INCLUDES_DIR . 'creator.php' );

		$wpdb->batch_create_queuemeta = $wpdb->base_prefix . 'batch_create_queuemeta';
		
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