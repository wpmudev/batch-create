<?php

// Pro  Sites
if ( ! function_exists( 'is_plugin_active_for_network' ) )
	include_once(ABSPATH . 'wp-admin/includes/plugin.php');

if ( is_plugin_active_for_network( 'pro-sites/pro-sites.php' ) ) {
	function batch_create_add_pro_status_meta( $queue_id, $row ) {
		

		if ( isset( $row[6] ) ) {
			$level = absint( $row[6] );
			$date = isset( $row[7] ) ? $row[7] : false;

			if ( ! $level )
				return;

			$extend = 9999999999;
			$timestamp = mysql2date( 'U', $date );
			if ( $timestamp )
				$extend = $timestamp;

			add_metadata( 'batch_create_queue', $queue_id, 'pro_site_extend', $extend, true );
			add_metadata( 'batch_create_queue', $queue_id, 'pro_site_level', $level, true );

		}
		
	}
	add_action( 'batch_create_queue_item_inserted', 'batch_create_add_pro_status_meta', 10, 2 );
	

	function batch_create_set_pro_status( $blog_id, $queue_item ) {
		global $psts;

		$queue_id = $queue_item->batch_create_ID;

		$extend = get_metadata( 'batch_create_queue', $queue_id, 'pro_site_extend', true );
		$level = get_metadata( 'batch_create_queue', $queue_id, 'pro_site_level', true );

		if ( $level && $extend ) {
			$extend = $extend < 9999999999 ? $extend - time() : $extend;
			$psts->extend( $blog_id, $extend, __('Manual', INCSUB_BATCH_CREATE_LANG_DOMAIN), $level );
		}
	}
	add_action( 'batch_create_blog_created', 'batch_create_set_pro_status', 10, 2 );

	function batch_create_add_pro_sites_columns( $columns ) {
		$columns['level'] = __( 'Pro Site Level', INCSUB_BATCH_CREATE_LANG_DOMAIN );
		$columns['extend'] = __( 'Until', INCSUB_BATCH_CREATE_LANG_DOMAIN );
		return $columns;
	}
	add_filter( 'batch_create_queue_columns', 'batch_create_add_pro_sites_columns' );

	function batch_create_display_pro_sites_columns( $content, $column, $item ) {
		if ( $column == 'level' ) {
			$level = get_metadata( 'batch_create_queue', $item['batch_create_ID'], 'pro_site_level', true );
			return $level ? $level : __( 'Not Pro', INCSUB_BATCH_CREATE_LANG_DOMAIN );
		}
		if ( $column == 'extend' ) {
			$extend = get_metadata( 'batch_create_queue', $item['batch_create_ID'], 'pro_site_extend', true );
			$extend = is_numeric( $extend ) && $extend < 9999999999 ? date_i18n( get_option( 'date_format' ), $extend ) : __( 'Permanent', INCSUB_BATCH_CREATE_LANG_DOMAIN );
			return $extend;
		}
	}
	add_filter( 'batch_create_display_queue_column', 'batch_create_display_pro_sites_columns', 10, 3 );

	function batch_create_add_pro_sites_instructions() {
		$test_xls_url = INCSUB_BATCH_CREATE_PLUGIN_URL . 'inc/test_pro.xls'; 
		$test_csv_url = INCSUB_BATCH_CREATE_PLUGIN_URL . 'inc/test_pro.csv'; 
		?>
			<h3><?php _e( 'Pro Sites Integration', INCSUB_BATCH_CREATE_LANG_DOMAIN ); ?></h3>
			<p><?php _e( 'Batch Create allows you to add new columns in the Excel/CSV file to specify the Pro Site level and the expiration date. Here is an example: ', INCSUB_BATCH_CREATE_LANG_DOMAIN ); ?></p>
			<p><?php printf( __( 'Download <a href="%s">this .xls</a> or <a href="%s">this .csv</a> file and use it as a template to create your batch file with Pro Sites columns.', INCSUB_BATCH_CREATE_LANG_DOMAIN ), $test_xls_url, $test_csv_url ); ?></p>
			<p><img width="650" src="<?php echo INCSUB_BATCH_CREATE_ASSETS_URL . 'images/batchcreatepro.jpg'; ?>" /></p>
		<?php
	}
	add_action( 'batch_create_instructions', 'batch_create_add_pro_sites_instructions' );
}