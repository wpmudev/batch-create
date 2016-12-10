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

	// Tables names
	private $queue;
	private $queue_meta;

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

		$this->queue = $wpdb->base_prefix . 'batch_create_queue';
		$this->queue_meta = $wpdb->base_prefix . 'batch_create_queuemeta';

		// Get the correct character collate
        $db_charset_collate = '';
        if ( ! empty($wpdb->charset) )
          $this->db_charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
        if ( ! empty($wpdb->collate) )
          $this->db_charset_collate .= " COLLATE $wpdb->collate";
	}

	/**
	 * Create the required DB schema
	 * 
	 * @since 0.1
	 */
	public function create_schema() {
		$this->create_queue_table();
		$this->create_queue_meta_table();
	}

	/**
	 * Create the table 1
	 * @return type
	 */
	private function create_queue_table() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = "CREATE TABLE $this->queue (
				  batch_create_ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				  batch_create_site bigint(20) DEFAULT NULL,
				  batch_create_blog_name varchar(255) NOT NULL DEFAULT 'null',
				  batch_create_blog_title varchar(255) NOT NULL DEFAULT 'null',
				  batch_create_user_name varchar(255) NOT NULL DEFAULT 'null',
				  batch_create_user_pass varchar(255) NOT NULL DEFAULT 'null',
				  batch_create_user_email varchar(255) NOT NULL DEFAULT 'null',
				  batch_create_user_role varchar(255) NOT NULL DEFAULT 'null',
				  PRIMARY KEY  (batch_create_ID)
				) ENGINE=InnoDB $this->db_charset_collate;";
       	
        dbDelta($sql);
	}

	private function create_queue_meta_table() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = "CREATE TABLE IF NOT EXISTS $this->queue_meta (
				  meta_id bigint(20) NOT NULL AUTO_INCREMENT,
				  batch_create_queue_id bigint(20) NOT NULL,
				  meta_key varchar(255) DEFAULT NULL,
				  meta_value longtext,
				  PRIMARY KEY  (meta_id),
				  KEY batch_create_queue_id (batch_create_queue_id),
  				  KEY meta_key (meta_key)
				) ENGINE=InnoDB $this->db_charset_collate;";
       	
        dbDelta($sql);
	}

	/**
	 * Drop the schema
	 */
	public function delete_tables() {
		global $wpdb;

		$wpdb->query( "DROP TABLE $this->queue;" );
		$wpdb->query( "DROP TABLE $this->queue_meta;" );
	}


	public function count_queue_items() {
		global $wpdb, $current_site;

		$result = $wpdb->get_var( 
			$wpdb->prepare( 
				"SELECT COUNT(*) FROM $this->queue WHERE batch_create_site = %d", $current_site->id 
			) 
		);

		return absint( $result );
	}


	/**
	 * Add entry into the database
	 *
	 * Since Batch Create 1.1.0
	 */
	function insert_queue( $args ) {
		global $wpdb, $current_site;

		array_unshift( $args, $current_site->id );

		if ( 'Windows-1252' === mb_detect_encoding( $args[ 2 ] ) ) {
			$args[ 2 ] = iconv( "Windows-1252", "UTF-8", $args[2] );
		}

		$wpdb->insert(
			$this->queue,
			array(
				'batch_create_site'			=> $args[0],
				'batch_create_blog_name'	=> $args[1],
				'batch_create_blog_title'	=> $args[2],
				'batch_create_user_name'	=> $args[3],
				'batch_create_user_pass'	=> $args[4],
				'batch_create_user_email'	=> $args[5],
				'batch_create_user_role'	=> $args[6],
			),
			array(  '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		return $wpdb->insert_id;

	}

	function clear_queue() {
		$queue_item = $this->get_queue_item();
		while( ! empty( $queue_item ) ) {
			$this->delete_queue_item( $queue_item->batch_create_ID );
			$queue_item = $this->get_queue_item();
		}
	}

	public function get_queue_item() {
		global $wpdb, $current_site;

		return $wpdb->get_row( 
			$wpdb->prepare( "SELECT * FROM $this->queue WHERE batch_create_site = %d", $current_site->id )
		);
	}

	
	public function delete_queue_item( $id ) {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM $this->queue WHERE batch_create_ID = %d",
				$id
			)
		);

		$meta_keys = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT meta_key FROM $this->queue_meta WHERE batch_create_queue_id = %d",
				$id
			)
		);

		if ( ! empty( $meta_keys ) ) {
			foreach ( $meta_keys as $meta_key ) {
				batch_create_delete_queue_meta( $id, $meta_key );
			}
		}
	}

	public function get_queue_items( $current_page, $per_page ) {
		global $wpdb, $current_site;

		return $wpdb->get_results( 
			$wpdb->prepare( 
				"SELECT * FROM $this->queue WHERE batch_create_site = %d LIMIT %d, %d", 
				$current_site->id,
				intval( ( $current_page - 1 ) * $per_page),
				intval( $per_page )
			), ARRAY_A
		);
	}

}