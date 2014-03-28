<?php

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Batch_Create_Queue_Table extends WP_List_Table {

	function __construct(){
        global $status, $page;
                
        parent::__construct( array(
            'singular'  => 'item', 
            'plural'    => 'items',
            'ajax'      => false
        ) );
        
    }

    function column_default($item, $column) {
        return apply_filters( 'batch_create_display_queue_column', '', $column, $item );
    }

    function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            	'item',  
            	$item['batch_create_ID']
        );
    }

    function column_blogname( $item ) {

    	$actions = array(
            'delete'    => sprintf( __( '<span class="trash"><a class="trash" href="%s">%s</a></span>', INCSUB_BATCH_CREATE_LANG_DOMAIN ), 
            	esc_url( add_query_arg( array( 'action' => 'delete', 'item' => absint( $item['batch_create_ID'] ) ) ) ),
            	__( 'Delete', INCSUB_BATCH_CREATE_LANG_DOMAIN )
            )
        );
        
        return $item['batch_create_blog_name'] . $this->row_actions( $actions );
    }

    function column_blogtitle( $item ) { 
        return $item['batch_create_blog_title'];
    }

    function column_username( $item ) {
        return $item['batch_create_user_name'];
    }

    function column_userpass( $item ) {
        return $item['batch_create_user_pass'];
    }

    function column_user_email( $item ) {
        return $item['batch_create_user_email'];
    }

    function column_user_role( $item ) {
        return $item['batch_create_user_role'];
    }

    function column_meta( $item ) {
        $meta = batch_create_get_queue_meta( $item['batch_create_ID'] );
        if ( ! empty ( $meta ) && is_array( $meta ) ) {
            foreach( $meta as $meta_key => $value  ) {
                echo '<span style="display:inline-block;vertical-align:middle">' . $meta_key . '</span>';
                ?>
                    <input type="text" disabled size="4" value ="<?php echo esc_attr( print_r( batch_create_get_queue_meta( $item['batch_create_ID'], $meta_key, true ), true ) ); ?>"><br/>
                <?php
            }
        }
    }
    function get_columns(){
        $columns = apply_filters( 'batch_create_queue_columns', array(
            'cb'                => '<input type="checkbox" />', //Render a checkbox instead of text
            'blogname'          => __( 'Blog name', INCSUB_BATCH_CREATE_LANG_DOMAIN ),
            'blogtitle'         => __( 'Blog title', INCSUB_BATCH_CREATE_LANG_DOMAIN ),
            'username'          => __( 'Username', INCSUB_BATCH_CREATE_LANG_DOMAIN ),
            'userpass'          => __( 'User password', INCSUB_BATCH_CREATE_LANG_DOMAIN ),
            'user_email'        => __( 'User email', INCSUB_BATCH_CREATE_LANG_DOMAIN ),
            'user_role'         => __( 'User role', INCSUB_BATCH_CREATE_LANG_DOMAIN ),
            'meta'  	        => __( 'Additional options', INCSUB_BATCH_CREATE_LANG_DOMAIN )
        ));

        if ( ! isset( $_GET['display_meta'] ) )
            unset( $columns['meta'] );

        return $columns;
    }

    function get_bulk_actions() {
        $actions = array(
            'delete'    => __( 'Delete items', INCSUB_BATCH_CREATE_LANG_DOMAIN )
        );
        return $actions;
    }

    function process_bulk_action() {
        
        if( 'delete' === $this->current_action() ) {

            $model = batch_create_get_model();
        	if ( ! isset( $_POST['item'] ) && isset( $_GET['item'] ) ) {
        		$model->delete_queue_item( absint( $_GET['item'] ) );
        	}
        	else {
        		$items = $_POST['item'];
        		if ( ! empty( $items ) ) {
        			foreach ( $items as $item ) {
                        $model->delete_queue_item( absint( $item ) );
					}
        		}
        	}

        	?>
				<div class="updated">
					<p><?php _e( 'Items deleted', INCSUB_BATCH_CREATE_LANG_DOMAIN ); ?></p>
				</div>
        	<?php
        }
        
    }

    function prepare_items() {
        global $wpdb, $page;

        $per_page = 15;
      
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array();

        $this->_column_headers = array($columns, $hidden, $sortable);

        $this->process_bulk_action();
        $current_page = $this->get_pagenum();

        $model = batch_create_get_model();

        $items = $model->get_queue_items( $current_page, $per_page );

        $this->items = $items;               

        $total = $model->count_queue_items();
        $this->set_pagination_args( array(
            'total_items' => $model->count_queue_items(),                 
            'per_page'    => $per_page,              
            'total_pages' => ceil( $total / $per_page )  
        ) );
    }
}