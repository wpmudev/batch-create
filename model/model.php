<?php

/**
 * The main model class.
 * 
 * Please, do not spread your queries through your
 * code, group your queries here.
 * 
 * You can create new classes for different models
 */

class Incsub_Batch_Create_Model {

	static $instance;

	// This option will tell WP if the schema has been created
	// Instead of using the activation hook, we'll use this
	// TODO: Change slug
	public $schema_created_option_slug = 'batch_create_installed';

	// Tables names
	private $table_name;

	// Charset and Collate
	private $db_charset_collate;


	/**
	 * Return an instance of the class
	 * 
	 * @since 0.1
	 * 
	 * @return Object
	 */
	public static function get_instance() {
		if ( self::$instance === null )
			self::$instance = new self();
            
        return self::$instance;
	}
 
	/**
	 * Set the tables names, charset, collate and creates the schema if needed.
	 * This way, the schema will be created when the model is created for first time.
	 */
	protected function __construct() {
		global $wpdb;

		// TODO: Change tables names
		$this->table_name = $wpdb->base_prefix . 'batch_create_queue';

		// Get the correct character collate
        $db_charset_collate = '';
        if ( ! empty($wpdb->charset) )
          $this->db_charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
        if ( ! empty($wpdb->collate) )
          $this->db_charset_collate .= " COLLATE $wpdb->collate";

      	// Have we created the DB schema?
      	if ( false === get_site_option( $this->schema_created_option_slug ) ) {
      		$this->create_schema();

      		update_site_option( $this->schema_created_option_slug, true );
      	}
	}

	/**
	 * Create the required DB schema
	 * 
	 * @since 0.1
	 */
	private function create_schema() {
		$this->create_table();
	}

	/**
	 * Create the table 1
	 * @return type
	 */
	private function create_table() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = "CREATE TABLE IF NOT EXISTS $this->table_name (
				  `batch_create_ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				  `batch_create_site` bigint(20) DEFAULT NULL,
				  `batch_create_blog_name` varchar(255) NOT NULL DEFAULT 'null',
				  `batch_create_blog_title` varchar(255) NOT NULL DEFAULT 'null',
				  `batch_create_user_name` varchar(255) NOT NULL DEFAULT 'null',
				  `batch_create_user_pass` varchar(255) NOT NULL DEFAULT 'null',
				  `batch_create_user_email` varchar(255) NOT NULL DEFAULT 'null',
				  `batch_create_user_role` varchar(255) NOT NULL DEFAULT 'null',
				  PRIMARY KEY (`batch_create_ID`)
				) ENGINE=InnoDB $this->db_charset_collate;";
       	
        dbDelta($sql);
	}

	/**
	 * Drop the schema
	 */
	public function delete_tables() {
		global $wpdb;

		$wpdb->query( "DROP TABLE $this->table_name;" );
	}

	public function get_pending_queue_count() {
		global $wpdb, $current_site;

		return $wpdb->get_var( 
			$wpdb->prepare( "SELECT COUNT(*) FROM $this->table_name WHERE batch_create_site = %d", $current_site->id ) 
		);
	}

	/**
	 * Add entry into the database
	 *
	 * Since Batch Create 1.1.0
	 */
	function insert_queue( $args ) {
		global $wpdb, $current_site;

		// add current site id as first argument
		array_unshift( $args, $current_site->id );

		// args array should always have 7 args
		for( $i = 0; $i < 7; $i++ )
			$args[$i] = isset( $args[$i] ) ? $args[$i] : '';

		$args[2] = iconv( "Windows-1252", "UTF-8", $args[2] );

		$wpdb->query( 
			$wpdb->prepare( 
				"INSERT INTO $this->table_name 
				( batch_create_site, batch_create_blog_name, batch_create_blog_title, batch_create_user_name, batch_create_user_pass, batch_create_user_email, batch_create_user_role ) 
				VALUES ( %d, %s, %s, %s, %s, %s, %s )", 
				$args 
			) 
		);
	}

	function clear_queue() {
		global $wpdb, $current_site;

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM $this->table_name WHERE batch_create_site = %d",
				$current_site->id
			)
		);
	}

	public function get_queue_item() {
		global $wpdb, $current_site;

		return $wpdb->get_row( 
			$wpdb->prepare( "SELECT * FROM $this->table_name WHERE batch_create_site = %d", $current_site->id )
		);
	}

	public function count_queue_items() {
		global $wpdb, $current_site;

		return $wpdb->get_var( 
			$wpdb->prepare( 
				"SELECT COUNT(*) FROM $this->table_name WHERE batch_create_site = %d", $current_site->id 
			) 
		);
	}

	public function delete_queue_item( $id ) {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM $this->table_name WHERE batch_create_ID = %d",
				$id
			)
		);
	}

	public function get_queue_items( $current_page, $per_page ) {
		global $wpdb, $current_site;

		return $wpdb->get_results( 
			$wpdb->prepare( 
				"SELECT * FROM $this->table_name WHERE batch_create_site = %d LIMIT %d, %d", 
				$current_site->id,
				intval( ( $current_page - 1 ) * $per_page),
				intval( $per_page )
			), ARRAY_A
		);
	}


}