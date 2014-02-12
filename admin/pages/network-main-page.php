<?php

class Batch_Create_Network_Main_Menu extends Origin_Admin_Page {
 	
 	public function __construct( $slug, $capability, $args ) {
 		parent::__construct( $slug, $capability, $args );

 		add_action( 'admin_init', array( &$this, 'process_form' ) );
 	}

	public function render_content() {
		if ( 'upload' == $this->get_current_tab() ) {

			$this->display_notices();

			$this->show_process_queue_notice();

			$creator = batch_create_get_creator();
			$old_files = $creator->get_old_sources();

			if ( $old_files ) {
				$clear_link = add_query_arg(
					'action',
					'clear_old_sources',
					$this->get_permalink()
				);

				$message = sprintf( __( 'You have %s old source file(s) stored on your system. These are no longer needed. <a href="%s">Delete them now</a>', INCSUB_BATCH_CREATE_LANG_DOMAIN ), count( $old_files ), $clear_link );
				Incsub_Batch_Create_Errors_Handler::show_updated_notice( $message );
			}

			$test_xls_url = INCSUB_BATCH_CREATE_PLUGIN_URL . 'inc/test.xls'; 
			$test_csv_url = INCSUB_BATCH_CREATE_PLUGIN_URL . 'inc/test.csv'; 

			$form_url = add_query_arg(
				'action',
				'process',
				$this->get_permalink()
			);
			?>
			<h3><?php _e( 'Instructions', INCSUB_BATCH_CREATE_LANG_DOMAIN ); ?></h3>
			<p><?php _e( "Batch create is designed for quickly creating sites and/or usernames or adding users to an existing site in batches of 10's, 100's or 1000's by uploading a .xls file.", INCSUB_BATCH_CREATE_LANG_DOMAIN ); ?></p>

			<ol>
				<li><?php printf( __( 'Download <a href="%s">this .xls</a> or <a href="%s">this .csv</a> file and use it as a template to create your batch file.', INCSUB_BATCH_CREATE_LANG_DOMAIN ), $test_xls_url, $test_csv_url ); ?></li>
				<li><?php _e( "Once you've added sites and/or usernames to the template save your file as an Excel 97-2003 Workbook or a .csv file.", INCSUB_BATCH_CREATE_LANG_DOMAIN ); ?></li>
				<li><?php _e( "Click on 'Choose File', locate your batch file, select 'This file has a header row', if you kept the first row in the template file, and click Upload.", INCSUB_BATCH_CREATE_LANG_DOMAIN ); ?></li>
				<li><?php _e( "Once uploaded it is placed into a queue. You need to click on the link 'here' in 'Click here to process the queue.' to start creating the usernames and/or sites.", INCSUB_BATCH_CREATE_LANG_DOMAIN ); ?></li>
				<li><?php _e( "You'll see a status bar displaying their progress as they're being created/added.", INCSUB_BATCH_CREATE_LANG_DOMAIN ); ?></li>
				<li><?php _e( "You can clear the queue by clicking on the link 'here' in 'you can clear the queue by clicking here.", INCSUB_BATCH_CREATE_LANG_DOMAIN ); ?></li>
				<li><?php _e( 'Here are the different ways you can use batch create:', INCSUB_BATCH_CREATE_LANG_DOMAIN ); ?></li>
				<img src="<?php echo INCSUB_BATCH_CREATE_ASSETS_URL . 'images/batchcreateex.jpg'; ?>" />
			</ol>

			<h3><?php _e( 'Upload file', INCSUB_BATCH_CREATE_LANG_DOMAIN ); ?></h3>
			<form action="<?php echo esc_url( $form_url ); ?>" method="post" enctype="multipart/form-data">
				<input type="file" name="csv_file" id="csv_file" size="20" /><br/><br/>

				<label for="disable_welcome_email">
					<input type="checkbox" name="disable_welcome_email" id="disable_welcome_email" value="1" /> 
					<?php _e('Do not send welcome email to users', INCSUB_BATCH_CREATE_LANG_DOMAIN );?>
				</label><br/><br/>

				<label for="header_row_yn">
					<input type="checkbox" name="header_row_yn" id="header_row_yn" value="1" /> 
					<?php _e('This file has a header row', INCSUB_BATCH_CREATE_LANG_DOMAIN );?>
				</label><br/>
				<span class="description"><?php _e( 'If this box is checked, the first row in the file <strong>WILL NOT</strong> be processed.', INCSUB_BATCH_CREATE_LANG_DOMAIN );?></span>
					  
				<?php wp_nonce_field( 'upload_batch_file' ); ?>
				<?php submit_button( __( 'Upload', INCSUB_BATCH_CREATE_LANG_DOMAIN ) ); ?>
			</form>
			<?php
		}

		if ( 'log-file' == $this->get_current_tab() ) {
			$creator = batch_create_get_creator();

			$log_file = $creator->get_log_content();

			$log_file = $log_file ? $log_file : '<p>' . __( 'The log is empty', INCSUB_BATCH_CREATE_LANG_DOMAIN ) . '</p>';

			$form_url = add_query_arg(
				array(
					'action' => 'batch-create-delete-log-file',
					'tab' => 'log-file'
				),
				$this->get_permalink()
			);
			?>
				<form action="<?php echo esc_url( $form_url ); ?>" method="post" >
					<pre style="width:96%;border:1px solid #DEDEDE;padding:2%;"><?php echo $log_file; ?></pre>
					<?php wp_nonce_field( 'batch-create-delete-log-file' ); ?>
					<?php submit_button( __( 'Delete log file', INCSUB_BATCH_CREATE_LANG_DOMAIN ) ); ?>
				</form>
			<?php
		}

		if ( 'queue' == $this->get_current_tab() ) {
			$table = new Batch_Create_Queue_Table();
			$table->prepare_items();

			$this->show_process_queue_notice();

			?><form action="" method="post" ><?php
				$table->display();
			?></form><?php
		}

		
	}

	private function show_process_queue_notice() {
		$model = batch_create_get_model();
		$tmp_queue_count = $model->get_pending_queue_count();

		if ( $tmp_queue_count > 0 ) {

			$proccess_link = add_query_arg(
				'action',
				'loop',
				$this->get_permalink()
			);

			$clear_link = add_query_arg(
				'action',
				'clear',
				$this->get_permalink()
			);

			$message = sprintf( 
				__( '<strong>Note:</strong> There are %d items (blogs/users) waiting to be processed. Click <a class="button-secondary" href="%s">here</a> to process the queue. If there is a problem, you can clear the queue by clicking <a href="%s">here</a>.', INCSUB_BATCH_CREATE_LANG_DOMAIN ), 
				$tmp_queue_count, 
				$proccess_link,
				$clear_link
			);

			Incsub_Batch_Create_Errors_Handler::show_updated_notice( $message, 'processing_result' );
		}
	}

	private function display_notices() {
		if ( isset( $_GET['page'] ) && $this->get_menu_slug() == $_GET['page'] ) {
			Incsub_Batch_Create_Errors_Handler::show_errors_notice();

			if ( isset( $_GET['uploaded'] ) )
				Incsub_Batch_Create_Errors_Handler::show_updated_notice( __( 'Items added to queue.', INCSUB_BATCH_CREATE_LANG_DOMAIN ) );
			
			if ( isset( $_GET['queue_cleared'] ) )
				Incsub_Batch_Create_Errors_Handler::show_updated_notice( __( 'Queue cleared.', INCSUB_BATCH_CREATE_LANG_DOMAIN ) );
			
			if ( isset( $_GET['old_cleared'] ) )
				Incsub_Batch_Create_Errors_Handler::show_updated_notice( __( 'Old sources deleted', INCSUB_BATCH_CREATE_LANG_DOMAIN ) );

			if ( isset( $_GET['log_cleared'] ) )
				Incsub_Batch_Create_Errors_Handler::show_updated_notice( __( 'Log file cleared', INCSUB_BATCH_CREATE_LANG_DOMAIN ) );

			if ( isset( $_GET['queue_updated'] ) ) {
				$log_link = add_query_arg('tab', 'log-file', $this->get_permalink() );
				Incsub_Batch_Create_Errors_Handler::show_updated_notice( sprintf( __( 'Queue processing complete. <a href="%s">See log file.</a>', INCSUB_BATCH_CREATE_LANG_DOMAIN ), $log_link ) );
			}
				
		}

		
	}

	public function process_form() {
		if ( isset( $_GET['page'] ) && $this->get_menu_slug() == $_GET['page'] ) {
			
			if ( isset( $_GET['action'] ) && 'process' == $_GET['action'] && isset( $_POST['submit'] ) ) {
				// Uploading file
				if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'upload_batch_file' ) || ! current_user_can( $this->get_capability() ) )
					return;	

				$header_row = ! isset( $_POST['header_row_yn'] );
				$welcome_email = ! isset( $_POST['disable_welcome_email'] );

				$creator = batch_create_get_creator();
				$done = $creator->process_file( $_FILES['csv_file'], $header_row, true, $welcome_email );

				if ( ! $done )
					return;

				$redirect_to = add_query_arg(
					'uploaded',
					'true',
					$this->get_permalink()
				);

				wp_redirect( $redirect_to );
					
			}
			if ( isset( $_GET['action'] ) && 'clear' == $_GET['action'] ) {
				$model = batch_create_get_model();
				$model->clear_queue();

				$redirect_to = add_query_arg(
					'queue_cleared',
					'true',
					$this->get_permalink()
				);

				wp_redirect( $redirect_to );
			}
			if ( isset( $_GET['action'] ) && 'clear_old_sources' == $_GET['action'] ) {
				$creator = batch_create_get_creator();

				$olds = $creator->get_old_sources();
				foreach ( $olds as $old ) {
					@unlink($old);
				}

				$redirect_to = add_query_arg(
					'old_cleared',
					'true',
					$this->get_permalink()
				);

				wp_redirect( $redirect_to );
			}
			if ( isset( $_GET['action'] ) && 'loop' == $_GET['action'] ) {
				$creator = batch_create_get_creator();
				$creator->process_queue();
			}

			if ( isset( $_GET['action'] ) && 'batch-create-delete-log-file' == $_GET['action'] ) {

				if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'batch-create-delete-log-file' ) || ! current_user_can( $this->get_capability() ) )
					return;	

				$creator = batch_create_get_creator();
				$creator->clear_log();

				$redirect_to = add_query_arg(
					array(
						'log_cleared' => 'true',
						'tab' => 'log-file'
					),
					$this->get_permalink()
				);

				wp_redirect( $redirect_to );
			}
			

		}
	}

}

