<?php
/*
Plugin Name: Batch Create
Plugin URI: http://premium.wpmudev.org/project/batch-create
Description: Create hundred or thousands of blogs and users automatically by simply uploading a csv text file - subdomain and user creation automation has never been so easy.
Author: Andrew Billits, Ulrich Sossou
Version: 1.1.2
Network: true
Author URI: http://premium.wpmudev.org/
Text Domain: batch_create
WDP ID: 84
*/

/*
Copyright 2007-2011 Incsub (http://incsub.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 - GPLv2) as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

if ( !defined( 'BATCH_CREATE_FOR_BLOG_ADMINS' ) )
	define( 'BATCH_CREATE_FOR_BLOG_ADMINS', false );

define( 'BATCH_CREATE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) . 'batch-create-files/' );
define( 'BATCH_CREATE_PLUGIN_URL', plugin_dir_url( __FILE__ ) . 'batch-create-files/' );

class batch_create {

	/**
	 * @var string $version Stores version number
	 *
	 * Since Batch Create 1.1.0
	 */
	var $version = '1.1.2';

	/**
	 * @var string $target_path Files upload directory
	 *
	 * Since Batch Create 1.1.0
	 */
	var $target_path = '';

	/**
	 * @var string $log_file File were processing output is logged
	 *
	 * Since Batch Create 1.1.0
	 */
	var $log_file = '';

	/**
	 * @var string $log_file_url URL to access log file via the browser
	 *
	 * Since Batch Create 1.1.0
	 */
	var $log_file_url = '';

	/**
	 * @var string $topmenu Menu item which the plugin page will be added under
	 *
	 * Since Batch Create 1.1.0
	 */
	var $topmenu = '';

	/**
	 * PHP5 contructor
	 *
	 * Since Batch Create 1.1.0
	 */
	function __construct() {
		global $wp_version;

		$upload_dir = wp_upload_dir();
		$this->target_path = $upload_dir['basedir'] . '/batch-create/';

		$this->log_file = $this->target_path . 'batch_create.log';
		$this->log_file_url = $upload_dir['baseurl'] . '/batch-create/batch_create.log';

		add_action( 'admin_notices', array( &$this, 'admin_notices' ) );
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
		add_action( 'admin_init', array( &$this, 'make_current' ) );
		add_action( 'admin_head', array( &$this, 'process_queue_javascript' ) );
		add_action( 'wp_ajax_process_queue', array( &$this, 'process_queue' ) );

		// add admin menus
		if( BATCH_CREATE_FOR_BLOG_ADMINS ) {
			add_action( 'admin_menu', array( &$this, 'plug_pages' ) );
			$this->topmenu = 'tools.php';
			$this->capability = 'manage_options';
		} else {
			add_action( 'network_admin_menu', array( &$this, 'plug_pages' ) );
			add_action( 'admin_menu', array( &$this, 'plug_pages' ) );

			$wp_3_1_plus = version_compare( $wp_version , '3.0.9', '>' );
			$wp_2_9_less = version_compare( $wp_version , '3.0', '<' );

			if( $wp_2_9_less )
				$this->topmenu = 'wpmu-admin.php';
			elseif( $wp_3_1_plus )
				$this->topmenu = 'settings.php';
			else
				$this->topmenu = 'ms-admin.php';

			$this->capability = 'manage_network_options';
		}

		// load text domain
		if ( defined( 'WPMU_PLUGIN_DIR' ) && file_exists( WPMU_PLUGIN_DIR . '/batch-create.php' ) ) {
			load_muplugin_textdomain( 'batch_create', 'batch-create-files/languages' );
		} else {
			load_plugin_textdomain( 'batch_create', false, dirname( plugin_basename( __FILE__ ) ) . '/batch-create-files/languages' );
		}
	}

	/**
	 * PHP4 contructor
	 *
	 * Since Batch Create 1.1.0
	 */
	function batch_create() {
		$this->__construct();
	}

	/**
	 * Update plugin version
	 *
	 * Since Batch Create 1.1.0
	 */
	function make_current() {
		global $plugin_page;

		if( 'batch-create' !== $plugin_page )
			return;

		if ( get_site_option( 'batch_create_version' ) == '' )
			add_site_option( 'batch_create_version', '0.0.0' );

		if ( get_site_option( 'batch_create_version' ) !== $this->version ) {
			update_site_option( 'batch_create_version', $this->version );
			update_site_option( 'batch_create_installed', 'no' );
		}

		$this->global_install();

		if ( get_option( 'batch_create_version' ) == '' )
			add_option( 'batch_create_version', $this->version );

		if ( get_option( 'batch_create_version' ) !== $this->version )
			update_option( 'batch_create_version', $this->version );
	}

	/**
	 * Create necessary tables
	 *
	 * Since Batch Create 1.1.0
	 */
	function global_install() {
		global $wpdb;

		if ( get_site_option( 'batch_create_installed' ) == '' )
			add_site_option( 'batch_create_installed', 'no' );

		if ( get_site_option( 'batch_create_installed' ) !== 'yes' ) {

			if( @is_file( ABSPATH . '/wp-admin/includes/upgrade.php' ) )
				include_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
			else
				die( __( 'We have problem finding your \'/wp-admin/upgrade-functions.php\' and \'/wp-admin/includes/upgrade.php\'', 'batch_create' ) );

			$charset_collate = '';
			if( $wpdb->supports_collation() ) {
				if( !empty( $wpdb->charset ) ) {
					$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
				}
				if( !empty( $wpdb->collate ) ) {
					$charset_collate .= " COLLATE $wpdb->collate";
				}
			}

			$batch_create_table = "CREATE TABLE `{$wpdb->base_prefix}batch_create_queue` (
				`batch_create_ID` bigint(20) unsigned NOT NULL auto_increment,
				`batch_create_site` bigint(20),
				`batch_create_blog_name` varchar(255) NOT NULL default 'null',
				`batch_create_blog_title` varchar(255) NOT NULL default 'null',
				`batch_create_user_name` varchar(255) NOT NULL default 'null',
				`batch_create_user_pass` varchar(255) NOT NULL default 'null',
				`batch_create_user_email` varchar(255) NOT NULL default 'null',
				`batch_create_user_role` varchar(255) NOT NULL default 'null',
				PRIMARY KEY  (`batch_create_ID`)
			) $charset_collate;";

			maybe_create_table( "{$wpdb->base_prefix}batch_create_queue", $batch_create_table );

			// upgrade from versions < 1.1.0
			maybe_add_column( "{$wpdb->base_prefix}batch_create_queue", 'batch_create_user_role', "ALTER TABLE {$wpdb->base_prefix}batch_create_queue ADD `batch_create_user_role `varchar(255) NOT NULL default 'null';" );

			update_site_option( 'batch_create_installed', 'yes' );

		}

	}

	/**
	 * Create upload directory or display admin notice
	 *
	 * Since Batch Create 1.1.0
	 */
	function admin_notices() {
		if ( ! is_dir( $this->target_path ) ) {
			if ( ! wp_mkdir_p( $this->target_path ) )
				printf( __( '<div class="error"><p>Unable to create directory %s. Is its parent directory writable by the server?</p></div>', 'batch_create' ), $this->target_path );
		} else {
			if( ! file_exists( $this->log_file ) ) {
				$handle = fopen( $this->log_file, 'w' ) or printf( __( '<div class="error"><p>Unable to create log file %s. Is its parent directory writable by the server?</p></div>', 'batch_create' ), $this->target_path );
				fclose( $handle );
			}
		}
	}

	/**
	 * Add admin page
	 *
	 * Since Batch Create 1.1.0
	 */
	function plug_pages() {
		add_submenu_page( $this->topmenu, __( 'Batch Create', 'batch_create' ), __( 'Batch Create', 'batch_create' ), $this->capability, 'batch-create', array( &$this, 'page_main_output' ) );
	}

	/**
	 * Add entry into the database
	 *
	 * Since Batch Create 1.1.0
	 */
	function queue_insert( $args ) {
		global $wpdb, $current_site;

		// add current site id as first argument
		array_unshift( $args, $current_site->id );

		// args array should always have 7 args
		for( $i = 0; $i < 7; $i++ )
			$args[$i] = isset( $args[$i] ) ? $args[$i] : '';

		$args[2] = esc_html( $args[2] );

		$wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->base_prefix}batch_create_queue ( batch_create_site, batch_create_blog_name, batch_create_blog_title, batch_create_user_name, batch_create_user_pass, batch_create_user_email, batch_create_user_role ) VALUES ( %d, %s, %s, %s, %s, %s, %s )", $args ) );
	}

	/**
	 * Delete entry from database
	 *
	 * Since Batch Create 1.1.0
	 */
	function queue_remove( $batch_create_id ) {
		global $wpdb, $current_site;

		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->base_prefix}batch_create_queue WHERE batch_create_ID = '%d' AND batch_create_site = '%d'", $batch_create_id, $current_site->id ) );
	}

	/**
	 * Clear all entries from database
	 *
	 * Since Batch Create 1.1.0
	 */
	function queue_clear() {
		global $wpdb, $current_site;

		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->base_prefix}batch_create_queue WHERE batch_create_site = '%d'", $current_site->id ) );
	}

	/**
	 * Create blog and/or user from database entry
	 *
	 * Since Batch Create 1.1.0
	 */
	function process_queue() {
		global $wpdb, $current_site, $current_user;

		$args = $wpdb->get_row( $wpdb->prepare( "SELECT batch_create_ID, batch_create_blog_name, batch_create_blog_title, batch_create_user_name, batch_create_user_pass, batch_create_user_email, batch_create_user_role FROM {$wpdb->base_prefix}batch_create_queue WHERE batch_create_ID = %d LIMIT 1", $_POST['blog_id'] ), ARRAY_A );
		@extract( $args );

		$blog_id = '';
		$user_id = '';
		$base = '/';

		// Sanitize the blog name
		$batch_create_blog_name = preg_replace('~[ .&]+~', '', $batch_create_blog_name);
		$tmp_domain = strtolower( esc_html( $batch_create_blog_name ) );

		$batch_create_user_email = trim( esc_html( $batch_create_user_email ) );

		if( constant( 'VHOST' ) == 'yes' ) {
			$tmp_blog_domain = $tmp_domain . '.' . $current_site->domain;
			$tmp_blog_path = $base;
		} else {
			$tmp_blog_domain = $current_site->domain;
			$tmp_blog_path = $base . $tmp_domain . '/';
		}

		//$user = get_user_by_email( $batch_create_user_email );
		$user = get_user_by('login', $batch_create_user_name); // This is better, since usernames can't be changed

		if( ! empty( $user ) ) { // user exists
			$user_id = $user->ID;
		} else { // create user
			if( $batch_create_user_pass == '' || $batch_create_user_pass == strtolower( 'null' ) ) {
				$batch_create_user_pass = wp_generate_password();
			}

			$user_id = wpmu_create_user( $batch_create_user_name, $batch_create_user_pass,  $batch_create_user_email );
			if( false == $user_id ) {
				$this->log('There was an error creating a user ' . $batch_create_user_name);
				die( '<p>' . __( 'There was an error creating a user', 'batch_create' ) . '</p>' );
			} else {
				wp_new_user_notification( $user_id, $batch_create_user_pass );
				$this->log( "User: $batch_create_user_name created!" );
			}
		}

		if ( ! in_array( $batch_create_blog_name, array( '', strtolower( 'null' ) ) ) ) {
			$blog_id = get_id_from_blogname( $batch_create_blog_name );
			if( !empty( $blog_id ) ) { // blog exists
				if( isset( $batch_create_user_role ) && add_user_to_blog( $blog_id, $user_id, $batch_create_user_role ) == true ) { // add user to blog
					$this->log( "User $batch_create_user_name successfully added to blog {$tmp_blog_domain}{$tmp_blog_path}" );
				} else {
					$this->log( "Unable to add user $batch_create_user_name to blog {$tmp_blog_domain}{$tmp_blog_path}" );
				}
			}
		}

		if ( empty( $blog_id ) && ( ! in_array( $batch_create_blog_name, array( '', strtolower( 'null' ) ) ) && ! in_array( $batch_create_blog_title, array( '', strtolower( 'null' ) ) ) ) ) { // create blog
			// Create user blog and set Admin user, as a consequence.
			// Since this is the case, if the user in the batch queue has any explicit roles
			// OTHER then 'administrator', we'll assign current logged in user as new blog admin.
			if ('administrator' != $batch_create_user_role && !in_array($batch_create_user_role, array('', strtolower('null')))) {
				global $current_user;
				get_currentuserinfo();
				$admin_id = $current_user->ID;
			} else {
				$admin_id = $user_id;
			}
			$blog_id = wpmu_create_blog( $tmp_blog_domain, $tmp_blog_path, esc_html( $batch_create_blog_title ), $admin_id , 1, $current_site->id );
			// Now, if we created a new blog with currently logged in user,
			// we need to add the guy from the batch queue to the blog too
			if (!is_wp_error($blog_id) && $admin_id != $user_id) {
				add_user_to_blog($blog_id, $user_id, $batch_create_user_role);
			}

			if( ! is_wp_error( $blog_id ) ) {
				$content_mail = sprintf( __( 'New blog created by %1s\n\nAddress: http://%2s\nName: %3s', 'batch_create' ), $current_user->user_login , $tmp_blog_domain . $tmp_blog_path, esc_html( $batch_create_blog_title ) );
				@wp_mail( get_option( 'admin_email' ),  sprintf( __( '[%s] New Blog Created', 'batch_create' ), $current_site->site_name ), $content_mail );

				//send email
				wpmu_welcome_notification( $blog_id, $user_id, $batch_create_user_pass, esc_html( $batch_create_blog_title ), '' );
				$this->log( 'Blog: ' . $tmp_blog_domain . $tmp_blog_path . ' created!' );
			} else {
				$this->log( 'Error creating blog: ' . $tmp_blog_domain . $tmp_blog_path . ' - ' . $blog_id->get_error_message() );
			}
		} /* Vladislav addition */ else if (in_array($batch_create_blog_name, array('', strtolower('null')))) {
			// If blog not explicitly requested, add user to main blog
			$result = add_user_to_blog(BLOG_ID_CURRENT_SITE, $user_id, $batch_create_user_role);
		} /* End addition */

		$this->queue_remove( $batch_create_ID );

		$query = $wpdb->prepare( "SELECT batch_create_ID FROM {$wpdb->base_prefix}batch_create_queue WHERE batch_create_site = %d LIMIT 1", $current_site->id );
		$queue_item = $wpdb->get_var( $query );

		die( $queue_item );
	}

	/**
	 * Create blog and/or user from database entry
	 *
	 * Since Batch Create 1.1.0
	 */
	function page_main_output() {
		global $wpdb, $wp_roles, $current_user, $current_site;

		if( !current_user_can( $this->capability ) ) {
			echo '<p>' . __( 'Nice Try...', 'batch_create' ) . '</p>'; // If accessed properly, this message doesn't appear.
			return;
		}

		if ( isset( $_GET['updated'] ) )
			echo '<div id="message" class="updated fade"><p>' . stripslashes( batch_urldecode( $_GET['updatedmsg'] ) ) . '</p></div>';

		echo '<div class="wrap">';
			$tmp_queue_count_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->base_prefix}batch_create_queue WHERE batch_create_site = %d", $current_site->id ) );

			echo '<h2>' . __( 'Batch Create', 'batch_create' ) . '</h2>';
			if ( $tmp_queue_count_count > 0 )
				printf( __( '<p class="processing_result"><strong>Note:</strong> There are %d items (blogs/users) waiting to be processed. Click <a href="?page=batch-create&action=loop">here</a> to process the queue. If there is a problem, you can clear the queue by clicking <a href="?page=batch-create&action=clear">here</a>.</p>', 'batch_create' ), $tmp_queue_count_count );
			?>

			<a href="?page=batch-create&action=clear_log"><?php _e('Clear the log file', 'batch_create');?></a>

			<form action="?page=batch-create&action=process" method="post" enctype="multipart/form-data">
				<p>
				  <input type="file" name="csv_file" id="csv_file" size="20" />

				<div>
				  <input type="checkbox" name="header_row_yn" id="header_row_yn" value="1" />
				  	<label for="header_row_yn"><?php _e('This file has a header row', 'batch_create');?></label>
				  	<div class="howto"><?php _e('If this box is checked, the first row in the file <strong>WILL NOT</strong> be processed.', 'batch_create');?></div>
				</div>

				  <input type="hidden" name="max_file_size" value="100000" />
				  <input type="hidden" name="_wp_http_referer" value="<?php echo $_SERVER['REQUEST_URI'] ?>" />
				</p>
				<p class="submit">
				  <input name="Submit" value="<?php _e( 'Upload &raquo;', 'batch_create' ) ?>" type="submit" />
				</p>
			</form>

			<?php
			$this->instructions();
		echo '</div>';
	}

	function process_queue_javascript() {
		global $plugin_page, $wpdb, $current_site;

		if ( ! ( 'batch-create' == $plugin_page && isset( $_GET['action'] ) && 'loop' == $_GET['action'] ) )
			return;

		$query = $wpdb->prepare( "SELECT batch_create_ID FROM {$wpdb->base_prefix}batch_create_queue WHERE batch_create_site = %d LIMIT 1", $current_site->id );
		$queue_item = $wpdb->get_var( $query );

		$count_items = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->base_prefix}batch_create_queue WHERE batch_create_site = %d", $current_site->id ) );
	?>
	<script type="text/javascript" >
	jQuery(function($) {
		process_item(<?php echo $queue_item; ?>);
		var rt_count = 0;
		var rt_total = <?php echo $count_items; ?>;

		function process_item(id) {
			if (!parseInt(id)) return false; // What we have is not a numeric ID = there was an error. Bail out.
			var data = {
				action: 'process_queue',
				blog_id: id
			};

			$.post(ajaxurl, data, function(response) {
				if( '' !== response ) {
					process_item(response);
				}
				rt_count = rt_count + 1;
				$( '#progressbar' ).progressbar( 'value', ( rt_count / rt_total ) * 100 );
			});
		}

		$('.processing_result')
			.html('<div id="progressbar"></div>')
			.ajaxStop(function() {
				<?php $destination = add_query_arg( 'updated', 'true', add_query_arg( 'updatedmsg', batch_urlencode( sprintf( __( 'Queue processing complete. <a href="%s">See log file.</a>', 'batch_create' ), $this->log_file_url ) ), remove_query_arg( 'action', $_SERVER['REQUEST_URI'] ) ) ); ?>
				window.location = "<?php echo $destination; ?>";
			});

		$( '#progressbar' ).progressbar({
			value: 1
		});
	});
	</script>
	<?php
	}

	/**
	 * Execute actions
	 *
	 * Since Batch Create 1.1.0
	 */
	function admin_init() {
		$page = isset( $_GET[ 'page' ] ) ? $_GET[ 'page' ] : '';
		if( 'batch-create' !== $page ) // stop function execution if not on plugin page
			return;

		if( ! current_user_can( 'manage_network_options' ) ) { // check user permissions
			wp_die( 'Cheating Huh?' );
			exit;
		}

		// Fix plugin paths, if possible.
		$this->admin_notices();

		$action = isset( $_GET[ 'action' ] ) ? $_GET[ 'action' ] : '';
		switch( $action ) {

			case 'clear_log':
				if (file_exists($this->log_file) && filesize($this->log_file) > 0) {
					// Blank out the file
					$fp = fopen($this->log_file, 'w');
					fclose($fp);
				}
				wp_redirect( add_query_arg( 'updated', 'true', add_query_arg( 'updatedmsg', batch_urlencode( __( 'Log file cleared', 'batch_create' ) ), wp_get_referer() ) ) );
				die;

			case 'process': // process file upload

				$file_path = $this->target_path . basename( $_FILES['csv_file']['name']);
				$header_row_yn = @$_POST['header_row_yn'];

				if( ! move_uploaded_file( $_FILES['csv_file']['tmp_name'], $file_path ) ) { // file not moved in upload directory

					wp_redirect( add_query_arg( 'updated', 'true', add_query_arg( 'updatedmsg', batch_urlencode( __( 'There was an error uploading the file, please try again!', 'batch_create' ) ), wp_get_referer() ) ) );
					die;

				} else { // upload successful

					$file_extension = end( explode( '.', $file_path ) );

					if( ! in_array( $file_extension, array( 'csv', 'xls' ) ) ) { // unsupported file extension

						wp_redirect( add_query_arg( 'updated', 'true', add_query_arg( 'updatedmsg', batch_urlencode( __( 'The file type you uploaded is not supported. Please upload a .csv or .xls file.', 'batch_create' ) ), wp_get_referer() ) ) );
						die;

					} else { // supported file extension

						if ( ( $handle = @fopen( $file_path, 'r' ) ) == false ) { // unable to open file

							wp_redirect( add_query_arg( 'updated', 'true', add_query_arg( 'updatedmsg', batch_urlencode( __( 'Error reading the uploaded file.', 'batch_create' ) ), wp_get_referer() ) ) );

						} else { // open file

							$tmp_new_blogs = array();

							if( 'csv' == $file_extension ) { // if csv file

								while ( ( $buffer = fgetcsv( $handle, 4096, ',' ) ) !== false ) {
									$tmp_new_blogs[] = $buffer;
								}

								fclose( $handle );

							} elseif( 'xls' == $file_extension ) { // if xls file

								fclose( $handle );

								require( BATCH_CREATE_PLUGIN_DIR . 'excel/reader.php' );

								$data = new Spreadsheet_Excel_Reader();
								$data->setOutputEncoding( 'CP1251' );
								$data->read( $file_path );

								for ( $i = 1; $i <= $data->sheets[0]['numRows']; $i++ ) {
									$tmp_cols = array();
									for ( $j = 1; $j <= $data->sheets[0]['numCols']; $j++ ) {
										if( isset( $data->sheets[0]['cells'][$i][$j] ) ) {
											$tmp_cols[$j] = $data->sheets[0]['cells'][$i][$j]; // Index the result array
										} else $tmp_cols[$j] = ''; // Pad the result array -  this handles empty fields in the XLS
									}
									$tmp_new_blogs[] = $tmp_cols;
								}

							}

							if( !empty( $tmp_new_blogs ) ) {

								if ($header_row_yn) array_shift($tmp_new_blogs); // Remove header row, if instructed to do so.

								// Check for emails being unique
								$emails = array();
								$not_unique = array();
								foreach ($tmp_new_blogs as $idx=>$tnb) {
									if (!in_array($tnb[5], $emails)) $emails[$tnb[3]] = $tnb[5];
									else {
										$uname = array_search($tnb[5], $emails);
										if ($tnb[3] != $uname) $not_unique[$tnb[5]] = (($not_unique[$tnb[5]]) ? $not_unique[$tnb[5]] . ', ' : $uname . ', ') . $tnb[3];
									}
								}
								// New users with same emails - this is no good. Bail out.
								if (!empty($not_unique)) {
									$nu_msg = '<ul>';
									foreach ($not_unique as $nid=>$nitem) {
										$nu_msg .= '<li>' . $nid . ': ' . $nitem . '</li>';
									}
									$nu_msg .= '</ul>';
									wp_redirect(add_query_arg('updated', 'true', add_query_arg( 'updatedmsg', batch_urlencode( sprintf( __( 'Queue processing error. These emails are not unique: %s', 'batch_create' ), $nu_msg ) ), wp_get_referer() ) ));
									die;
								}

								// process data
								foreach( $tmp_new_blogs as $tmp_new_blog ) {
									$details_count = count( $tmp_new_blog );
									if( in_array( $details_count, array( 5, 6 ) ) ) { // if there are 5 or 6 entries on the line
										if (!count(array_filter($tmp_new_blog))) continue; // Every single field is empty - continue
										$this->queue_insert( $tmp_new_blog );
									}

								}

							} else {

								wp_redirect( add_query_arg( 'updated', 'true', add_query_arg( 'updatedmsg', batch_urlencode( __( 'No data was retrieved from the file. Please verify its content.', 'batch_create' ) ), wp_get_referer() ) ) );
								die;

							}

							wp_redirect( add_query_arg( 'updated', 'true', add_query_arg( 'updatedmsg', batch_urlencode( __( 'Items added to queue.', 'batch_create' ) ), wp_get_referer() ) ) );
							die;

						}

					}

				}
			break;

			case 'loop': // process queue
				if ( wp_script_is( 'jquery-ui-widget', 'registered' ) )
					wp_enqueue_script( 'jquery-ui-progressbar', plugins_url( 'batch-create-files/js/jquery.ui.progressbar.min.js', __FILE__ ), array( 'jquery-ui-core', 'jquery-ui-widget' ), '1.8.7' );
				else
					wp_enqueue_script( 'jquery-ui-progressbar', plugins_url( 'batch-create-files/js/ui.progressbar.min.js', __FILE__ ), array( 'jquery-ui-core' ), '1.7.3' );

				wp_enqueue_style( 'jquery-ui-batchcreate', plugins_url( 'batch-create-files/css/jquery-ui-1.7.3.custom.css', __FILE__ ), array(), '1.7.3' );
			break;

			case 'clear':
				$this->queue_clear();
				wp_redirect( add_query_arg( 'updated', 'true', add_query_arg( 'updatedmsg', batch_urlencode( __( 'Queue cleared.', 'batch_create' ) ), wp_get_referer() ) ) );
				exit;
			break;

			default:
			break;

		}
	}

	/**
	 * User Instructions
	 *
	 * Since Batch Create 1.1.0
	 */
	function instructions() {
		 //_e( '<h3>Detailed Instructions</h3> <h4>BLOG_NAME,BLOG_TITLE,USER_NAME,USER_PASS,USER_EMAIL,USER_ROLE</h4> <p><b>BLOG_NAME</b> = The name of the blog you want created or the user added to (if that blog already exists). If you do not want the user to have a blog, please set this to \'null\' without the quotation marks. No spaces allowed. This will be part of the URL for the blog (ex. blog_name.myblogs.org or myblogs.org/blog_name).</p>  <p><b>BLOG_TITLE</b> = The title of the blog. This can be changed later.</p> <p><b>USER_NAME</b> = The login or username of the user. No spaces allowed. This can\'t be changed later.</p> <p><b>USER_PASS</b> = If you would like a password auto-generated please set this to \'null\' without the quotation marks. No spaces allowed. The user will get an email with the password.</p> <p><b>USER_EMAIL</b> = You must provide a valid email for each user.</p> <p><b>USER_ROLE</b> = The user role (when the user is added to an existing blog). Set to subscriber, contributor, author, editor, or administrator.</p> <h4>Examples:</h4> <p><b>User with blog and preset password:</b><br /> demoblogname1,Demo Blog Title 1,username1,userpass1, useremail@domain.com,administrator</p> <p><b>User with blog and auto-generated password with default role of editor:</b><br /> demoblogname2,Demo Blog Title 2,username2,null,useremail2@domain.com,editor</p> <p><b>User without blog</b>:<br /> null,null,username3,userpass3,useremail3@domain.com,null</p> <p><b>User added to existing blog as an author:</b><br /> demoblogname4,null,username4,userpass4,useremail4@domain.com,author</p> <p><b>Together in a file these would look like:</b><br /> demoblogname1,Demo Blog Title 1,username1,userpass1,useremail@domain.com,administrator<br /> demoblogname2,Demo Blog Title 2,username2,null,useremail2@domain.com,editor<br /> null,null,username3,userpass3,useremail3@domain.com,null<br /> demoblogname4,null,username4,userpass4,useremail4@domain.com,editor</p> <h4>Please Note:</h4> <p>Spam filters, especially strict ones for institutional email addresses, may well block username and login information from reaching users.<br /><br /> In this case you should either try to use free webmail accounts that won\'t block the emails (such as gmail.com, hotmail.com or mail.yahoo.com) or preset passwords.<br /><br /> If your users do not have email accounts you can still set them up using a gmail.com address and adding a number for each different user. For example: myname+1@gmail.com, myname+2@gmail.com, myname+3@gmail.com<br /><br /> The system will treat each of these as a separate email account but they will all arrive at myname@gmail.com.</p>', 'batch_create' );
		 _e('<h3>Instructions:</h3>  <p>Batch create is designed for quickly creating sites and/or usernames or adding users to an existing site in batches of 10&#39;s, 100&#39;s or 1000&#39;s by uploading a <a href="' . plugins_url('batch-create-files/test.xls', __FILE__) . '">.xls</a> file.</p> <p>1.	 Download <a href="' . plugins_url('batch-create-files/test.xls', __FILE__) . '">this .xls</a> file and use it as a template to create your batch file.</p> <p>2.	 Once you&#39;ve added sites and/or usernames to the template save your file as an Excel 97-2003 Workbook or a .csv file.</p> <p>3.	Click on &#39;Choose File&#39;, locate your batch file, select &#39;This file has a header row&#39;, if you kept the first row in the template file, and click <strong>Upload</strong>.</p> <p>4.	Once uploaded it is placed into a queue. You need to click on the link &#39;here&#39; in &#39;Click here to process the queue.&#39; to start creating the usernames and/or sites.</p> <p>5.	You&#39;ll see a status bar displaying their progress as they&#39;re being created/added.</p> <p>6.	You can clear the queue by clicking on the link &#39;here&#39; in &#39;you can clear the queue by clicking here.&#39;</p> <p><strong>Here are the different ways you can use batch create:</strong></p> <img src="' . plugins_url('batch-create-files/css/images/batchcreateex.jpg', __FILE__) . '" />', 'batch_create' );
	}

	/**
	 * User Instructions
	 *
	 * Since Batch Create 1.1.0
	 */
	function log( $message ) {
		error_log( date_i18n( 'Y-m-d H:i:s' ) . " - $message\n", 3, $this->log_file );
	}

}

/**
 * Uses base64 encoding to mangle the string before regularly urlencoding it.
 *
 * This is a fix for redirection 404 on some servers.
 *
 * @param string Message to be encoded.
 * @return string encoded message.
 */
function batch_urlencode ($str) {
	return urlencode(base64_encode($str));
}

/**
 * Decodes the string back to its' original, usable form.
 *
 * @param string Message processed with batch_urlencode().
 * @return string Decoded message.
 */
function batch_urldecode ($str) {
	return urldecode(base64_decode($str));
}

$batch_create = new batch_create();

/**
 * Show notification if WPMUDEV Update Notifications plugin is not installed
 **/
if ( !function_exists( 'wdp_un_check' ) ) {
	add_action( 'admin_notices', 'wdp_un_check', 5 );
	add_action( 'network_admin_notices', 'wdp_un_check', 5 );

	function wdp_un_check() {
		if ( !class_exists( 'WPMUDEV_Update_Notifications' ) && current_user_can( 'edit_users' ) )
			echo '<div class="error fade"><p>' . __('Please install the latest version of <a href="http://premium.wpmudev.org/project/update-notifications/" title="Download Now &raquo;">our free Update Notifications plugin</a> which helps you stay up-to-date with the most stable, secure versions of WPMU DEV themes and plugins. <a href="http://premium.wpmudev.org/wpmu-dev/update-notifications-plugin-information/">More information &raquo;</a>', 'wpmudev') . '</a></p></div>';
	}
}
