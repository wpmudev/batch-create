<?php

class Incsub_Batch_Create_Creator {

	public $target_path;
	private $log_file;
	private $log_file_url;

	public function __construct() {
		$upload_dir = wp_upload_dir();
		$this->target_path = $upload_dir['basedir'] . '/batch-create/';
		$this->log_file = $this->target_path . 'batch_create.log';
		$this->log_file_url = $upload_dir['baseurl'] . '/batch-create/batch_create.log';

		add_action( 'network_admin_notices', array( &$this, 'admin_notices' ) );
		add_action( 'wp_ajax_process_queue', array( &$this, 'process_ajax_queue' ) );
			
	}

	public function admin_notices() {
		$error = false;
		if ( ! is_dir( $this->target_path ) ) {
			if ( ! wp_mkdir_p( $this->target_path ) )
				$error = sprintf( __( 'Unable to create directory %s. Is its parent directory writable by the server?', INCSUB_BATCH_CREATE_LANG_DOMAIN ), $this->target_path );
		} 
		else {
			if( ! file_exists( $this->log_file ) ) {
				$handle = fopen( $this->log_file, 'w' );
				if ( ! $handle )
					$error = sprintf( __( 'Unable to create log file %s. Is its parent directory writable by the server?', INCSUB_BATCH_CREATE_LANG_DOMAIN ), $this->target_path );
				fclose( $handle );
			}
		}

		if ( $error )
			Incsub_Batch_Create_Errors_Handler::show_error_notice( $error );
	}

	public function process_file( $file, $first_column = true, $uploaded = true ) {
		$file_name = basename( $file['name'] );
		if ( empty( $file_name ) ) {
			Incsub_Batch_Create_Errors_Handler::add_error( 'empty_file', __( 'You need to select a file', INCSUB_BATCH_CREATE_LANG_DOMAIN ) );
			return false;
		}

		$file_extension = end( explode( '.', $file_name ) );
		if( ! in_array( $file_extension, array( 'csv', 'xls' ) ) ) {
			Incsub_Batch_Create_Errors_Handler::add_error( 'file_type', __( 'The file type you uploaded is not supported. Please upload a .csv or .xls file.', INCSUB_BATCH_CREATE_LANG_DOMAIN ) );
			return false;
		}

		$file_path = $this->target_path . $file_name;
		if( $uploaded && ! move_uploaded_file( $file['tmp_name'], $file_path ) ) { // file not moved in upload directory
			Incsub_Batch_Create_Errors_Handler::add_error( 'file_type', __( 'Error uploading the file.', INCSUB_BATCH_CREATE_LANG_DOMAIN ) );
			return false;
		}

		$handle = @fopen( $file_path, 'r' );
		if ( ! $handle ) {
			Incsub_Batch_Create_Errors_Handler::add_error( 'file_type', __( 'Error reading the uploaded file.', INCSUB_BATCH_CREATE_LANG_DOMAIN ) );
			return false;
		}

		$tmp_new_blogs = array();
		if( 'csv' == $file_extension ) { // if csv file

			// Undefined buffer size should help with processing issue.
			while ( ( $buffer = fgetcsv( $handle, 0, ',' ) ) !== false ) {
				$tmp_new_blogs[] = $buffer;
			}

			fclose( $handle );

		} 
		elseif( 'xls' == $file_extension ) { // if xls file

			fclose( $handle );

			require_once( INCSUB_BATCH_CREATE_INCLUDES_DIR . 'excel/reader.php' );

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
				if ( ! empty( $tmp_cols[1] ) )
					$tmp_new_blogs[] = $tmp_cols;
			}
		}

		if ( ! $first_column )
			array_shift( $tmp_new_blogs );

		if ( $uploaded )
			@unlink( $file_path ); // Kill the file, we got the array

		$emails = array();
		$not_unique = array();

		switch ( $file_extension ) {
			case 'csv': { 
				$email_index = 4; 
				$username_index = 2;
				break; 
			}
			case 'xls': { 
				$user_name_index = 3;
				$email_index = 5; 
				break; 
			}
		}

		foreach ( $tmp_new_blogs as $idx => $tnb ) {
			if ( ! in_array( $tnb[$email_index], $emails ) ) {
				$emails[ $tnb[$username_index] ] = $tnb[$email_index];
			}
			else {
				$uname = array_search( $tnb[$email_index], $emails );
				if ( $tnb[$username_index] != $uname ) 
					$not_unique[$tnb[$email_index]] = ( isset( $not_unique[ $tnb[$email_index] ] ) && $not_unique[ $tnb[$email_index] ] ? $not_unique[ $tnb[$email_index] ] . ', ' : $uname . ', ') . $tnb[$user_name_index];
			}
		}
		
		if ( ! empty( $not_unique ) ) {
			// New users with same emails - this is no good. Bail out.
			$nu_msg = '<ul>';
			foreach ($not_unique as $nid => $nitem) {
				$nu_msg .= '<li>' . $nid . ': ' . $nitem . '</li>';
			}
			$nu_msg .= '</ul>';

			Incsub_Batch_Create_Errors_Handler::add_error( 'emails_not_unique', sprintf( __( 'Queue processing error. These emails are not unique: %s', INCSUB_BATCH_CREATE_LANG_DOMAIN ), $nu_msg ) );
			return false;
		}

		if ( empty( $tmp_new_blogs ) ) {
			Incsub_Batch_Create_Errors_Handler::add_error( 'no_data', __( 'No data was retrieved from the file. Please verify its content.', INCSUB_BATCH_CREATE_LANG_DOMAIN ) );
			return false;
		}

		// process data
		foreach( $tmp_new_blogs as $tmp_new_blog ) {
			$details_count = count( $tmp_new_blog );
			if (
				in_array($details_count, array(5, 6)) // if there are 5 or 6 entries on the line
				|| $details_count > 6 // assume a row padded with empty columns
			) {

				if ( ! count( array_filter( $tmp_new_blog ) ) ) 
					continue; // Every single field is empty - continue

				$tmp_new_blog = array_values($tmp_new_blog);

				$model = batch_create_get_model();

				$queue_item_id = $model->insert_queue( $tmp_new_blog );
				do_action( 'batch_create_queue_item_inserted', $queue_item_id, $tmp_new_blog );
			}

		}

		return true;
	}

	public function process_queue() {
		add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_process_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_process_styles' ) );
		add_action( 'admin_head', array( &$this, 'process_queue_javascript' ) );
	}

	public function enqueue_process_scripts() {
		wp_enqueue_script( 'jquery-ui-progressbar', INCSUB_BATCH_CREATE_ASSETS_URL . 'jquery-ui/jquery.ui.progressbar.js', array( 'jquery-ui-core', 'jquery-ui-widget' ) );
	}

	public function enqueue_process_styles() {
		wp_enqueue_style( 'jquery-ui-batchcreate', INCSUB_BATCH_CREATE_ASSETS_URL . 'jquery-ui/jquery-ui-1.10.3.custom.min.css', array() );
	}

	public function process_queue_javascript() {
		$model = batch_create_get_model();
		$queue_item = $model->get_queue_item();
		$items_count = $model->count_queue_items();

		$destination = add_query_arg( 
			'queue_updated', 
			'true',
			Incsub_Batch_Create::$network_main_menu_page->get_permalink()
		);

		?>
		<script type="text/javascript" >
			jQuery(function($) {


				var rt_count = 0;
				var rt_total = <?php echo $items_count; ?>;

				$('.processing_result')
					.removeClass( 'updated' )
					.html('<div id="progressbar" style="margin-top:20px"></div>')
					.ajaxStop(function() {
						window.location = "<?php echo $destination; ?>";
					})
				;

				$('#progressbar').progressbar({
					"value": 1
				});

				// Initialize processing
				process_item();

				function process_item () {
					if ( rt_count >= rt_total ) return false;

					$.post(
						ajaxurl, 
						{"action": "process_queue"}, 
						function(response) {
							console.log(response);
							process_item();
							rt_count = rt_count + 1;
							$( '#progressbar' ).progressbar( 'value', (rt_count / rt_total) * 100 );
						}
					);
				}
			});
		</script>
		<?php

	}

	public function process_full_queue() {
		set_time_limit(360); // Try to give the script plenty of time to run

		$model = batch_create_get_model();
		$queue_item = $model->get_queue_item();
		while( ! empty( $queue_item ) ) {
			$model->delete_queue_item( $queue_item->batch_create_ID );
			$this->process_queue_item( $queue_item );
			$queue_item = $model->get_queue_item();
		}
	}

	public function process_ajax_queue() {
		set_time_limit(180); // Try to give the script plenty of time to run
		$model = batch_create_get_model();
		$queue_item = $model->get_queue_item();
		if ( ! empty( $queue_item->batch_create_ID ) ) {
			$result = $this->process_queue_item( $queue_item );
			$model->delete_queue_item( $queue_item->batch_create_ID );
		}
		$this->ajax_die();
	}

	public function process_queue_item( $queue_item ) {
		global $current_site, $wp_version;

		$this->log(
			sprintf(
				"--- Starting queue item processing ---\n" .
				"\tBlog name: [%s]\n" .
				"\tUser name: [%s]",
				$queue_item->batch_create_blog_name, 
				$queue_item->batch_create_user_name
			) 
		);

		// USER EMAIL
		$email = sanitize_email( $queue_item->batch_create_user_email );

		if ( empty( $email ) ) {
			$this->log( __( 'Missing email address.', INCSUB_BATCH_CREATE_LANG_DOMAIN ) );
			return false;
		}
		if ( ! is_email( $email ) ) {
			$this->log( __( 'Invalid email address.', INCSUB_BATCH_CREATE_LANG_DOMAIN ) );
			return false;
		}

		// USER
		$user_name = $queue_item->batch_create_user_name;
		$user = get_user_by( 'login', $queue_item->batch_create_user_name );
		if ( ! empty( $user ) ) {
			$this->log( sprintf( 'User %s already exists (ID: %d)', $queue_item->batch_create_user_name, $user->ID ) );
			$user_id = $user->ID;
		}
		else {
			do_action( 'batch_create_before_create_user', $queue_item );
			$password = 'N/A';
			if ( '' == $queue_item->batch_create_user_pass || 'null' == strtolower( $queue_item->batch_create_user_pass ) )
				$password = wp_generate_password( 12, false );
			else
				$password = $queue_item->batch_create_user_pass;

			$password = apply_filters( 'batch_create_new_user_password', $password, $queue_item );
			
			$user_name = preg_replace( '/\s+/', '', sanitize_user( $queue_item->batch_create_user_name, true ) );

			$user_id = wp_create_user( $user_name, $password, $email );
			if ( is_wp_error( $user_id ) ) {
				$this->log( $user_id->get_error_message() );
				return false;
			}

			// Newly created users have no roles or caps until they are added to a blog.
			delete_user_option( $user_id, 'capabilities' );
			delete_user_option( $user_id, 'user_level' );

			do_action( 'wpmu_new_user', $user_id );

			$send = true;
			$send = apply_filters( 'batch_create_send_new_user_notification', $send, $user_id );

			if ( $send )
				wpmu_welcome_user_notification( $user_id, $password );

			$this->log( "User: $user_name created!" );
			do_action( 'batch_create_after_create_user', $queue_item, $user_id );
		}

		$blog_id = false;

		// We might have passed a blog ID instead of a domain
		if ( is_numeric( $queue_item->batch_create_blog_name ) ) {
			$blog_details = get_blog_details( $queue_item->batch_create_blog_name );
			if ( ! empty( $blog_details->blog_id ) )
				$blog_id = $blog_details->blog_id;
		}

		if ( ! $blog_id ) {
			// DOMAIN
			$domain = '';
			if ( preg_match( '|^([a-zA-Z0-9-])+$|', $queue_item->batch_create_blog_name ) )
				$domain = strtolower( $queue_item->batch_create_blog_name );

			if ( ! is_subdomain_install() ) {
				$subdirectory_reserved_names = apply_filters( 'subdirectory_reserved_names', array( 'page', 'comments', 'blog', 'files', 'feed' ) );
				if ( in_array( $domain, $subdirectory_reserved_names ) ) {
					$this->log( sprintf( __('The following words are reserved for use by WordPress functions and cannot be used as blog names: %s' ), implode( ', ', $subdirectory_reserved_names ) ) );
					return false;
				}
			}

			if ( empty( $domain ) ) {
				$this->log( __( 'Missing or invalid site address.' ) );
				return false;
			}

			if ( is_subdomain_install() ) {
				$newdomain = $domain . '.' . preg_replace( '|^www\.|', '', $current_site->domain );
				$path      = $current_site->path;
			} else {
				$newdomain = $current_site->domain;
				$path      = $current_site->path . $domain . '/';
			}


			// BLOG
			if ( in_array( $newdomain, array( '', 'null' ) ) ) {
				$this->log(sprintf( 'Blog name is empty! Blog will NOT be created', INCSUB_BATCH_CREATE_LANG_DOMAIN ) );
			}

			$blog_id = get_id_from_blogname( $domain );
		}
		
		$user_role = $queue_item->batch_create_user_role;
		if( $blog_id ) { 

			// blog exists
			$this->log( sprintf( __( 'Blog (%s) already exists (%d), user can be added', INCSUB_BATCH_CREATE_LANG_DOMAIN ), $newdomain, $blog_id ), 'debug');

			if ( empty( $user_role ) ) {
				$this->log( __( "User role empty. The user could not have been added to the blog", INCSUB_BATCH_CREATE_LANG_DOMAIN ) );
				return false;
			}

			if( ! empty( $user_role ) && add_user_to_blog( $blog_id, $user_id, $user_role ) ) { 
				// add user to blog
				$this->log( __( "User $user_name successfully added to blog {$newdomain}{$path}",INCSUB_BATCH_CREATE_LANG_DOMAIN ) );
				do_action( 'batch_create_user_added_to_blog', $blog_id, $user_id, $user_role, $queue_item );
			} 
			else {
				$this->log( sprintf( __( 'Blog (%s) does NOT already exist, not adding user at this point', INCSUB_BATCH_CREATE_LANG_DOMAIN ), $newdomain ), 'debug');
				$this->log( __( "Unable to add user $batch_create_user_name to blog {$newdomain}{$path}", INCSUB_BATCH_CREATE_LANG_DOMAIN ) );
				return false;
			}


		}
		else {
			$this->log( sprintf( __( "Blog (%s) does NOT exist yet", INCSUB_BATCH_CREATE_LANG_DOMAIN ), $newdomain ), 'debug');
		}

		$blog_title = $queue_item->batch_create_blog_title;
		if ( ! $blog_id && $user_id && ( ! in_array( $newdomain, array( '', 'null' ) ) && ! in_array( $blog_title, array( '', 'null' ) ) ) ) { 
			// create blog
			$this->log( __( 'Starting new blog creation', INCSUB_BATCH_CREATE_LANG_DOMAIN ) );

			// Create user blog and set Admin user, as a consequence.
			// Since this is the case, if the user in the batch queue has any explicit roles
			// OTHER then 'administrator', we'll assign current logged in user as new blog admin.
            $retry_add_user = false;
			if ( 'administrator' != $user_role && ! in_array( $user_role, array( '', 'null' ) ) ) {
				$admin_user = get_userdata( get_current_user_id() );
				$admin_id = $admin_user->ID;
				$this->log( sprintf( 'New user is NOT administrator: using current user (%s) as admin instead', $admin_user->user_login ) );
				// We've added another administrator but the user in the list must be added too once the blog is created
				$retry_add_user = true;
			} else {
				$this->log( sprintf( __( 'New user is administrator', INCSUB_BATCH_CREATE_LANG_DOMAIN ) ) );
				$admin_id = $user_id;
			}

			$this->log(
				sprintf( __(
					"Attempting to create a new blog with this data:\n" .
					"\tDomain: [%s]\n" .
					"\tPath: [%s]\n" .
					"\tTitle: [%s]\n" .
					"\tAdmin user ID: [%s]\n" .
					"\tOn site: [%s]",
					INCSUB_BATCH_CREATE_LANG_DOMAIN ),
					$newdomain, 
					$path, 
					esc_html( $blog_title ), 
					$admin_id, 
					$current_site->id
				)
			);

			global $wpdb;

			$wpdb->hide_errors();
			$blog_id = wpmu_create_blog( $newdomain, $path, $blog_title, $admin_id , array( 'public' => 1 ), $current_site->id );
			$wpdb->show_errors();

			if ( is_wp_error( $blog_id ) ) {
				$this->log( __( 'Error creating blog: ' . $newdomain . $path . ' - ' . $blog_id->get_error_message(), INCSUB_BATCH_CREATE_LANG_DOMAIN ) );
				return false;
			}

			do_action( 'batch_create_blog_created', $blog_id, $queue_item );

			if ( ! is_super_admin( $admin_id ) && ! get_user_option( 'primary_blog', $admin_id ) )
				update_user_option( $admin_id, 'primary_blog', $blog_id, true );

			if ( $retry_add_user ) {
			    add_user_to_blog( $blog_id, $user_id, $user_role );
            }

			$send = apply_filters( 'batch_create_send_welcome_notification', true, $blog_id );

			if ( ! empty( $password ) && $send )
				wpmu_welcome_notification( $blog_id, $admin_id, $password, $blog_title, array( 'public' => 1 ) );

			$this->log( 'Blog: ' . $newdomain . $path . ' created!' );
		}
		elseif ( $user_id && in_array( $domain, array( '', strtolower( 'null' ) ) ) ) {
			// If blog not explicitly requested, add user to main blog
			$this->log( "There was no explicitly requested blogs; adding user to main blog" );
			$result = add_user_to_blog( BLOG_ID_CURRENT_SITE, $user_id, 'subscriber' );
		}

		return true;
	}

	/**
 	 * Log
	 *
	 * Since Batch Create 1.1.0
	 */
	private function log( $message, $level = 'debug' ) {
		error_log( date_i18n( 'Y-m-d H:i:s' ) . " [{$level}] - $message\n", 3, $this->log_file );
	}

	private function ajax_die() {
		$this->log( __( "--- Queue item processing finished ---\n", INCSUB_BATCH_CREATE_LANG_DOMAIN ) );
	}

	public function get_old_sources() {
		if ( defined( 'GLOB_BRACE' ) )
			$files = glob( $this->target_path . '*.{csv,xls}', GLOB_BRACE );
		else
			$files = array_merge( glob( $this->target_path . '*.csv' ), glob( $this->target_path . '*.xls' ) );
		return $files ? $files : array();
	}

	public function get_log_content() {
		return @file_get_contents(  $this->log_file );
	}

	public function clear_log() {
		if ( file_exists( $this->log_file ) && filesize( $this->log_file ) > 0) {
			// Blank out the file
			$fp = fopen($this->log_file, 'w');
			fclose($fp);
		}
	}
}