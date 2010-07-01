<?php
/*
Plugin Name: Batch Create
Plugin URI: 
Description:
Author: Andrew Billits
Version: 1.0.0
Author URI:
WDP ID: 84
*/

/* 
Copyright 2007-2009 Incsub (http://incsub.com)

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

$batch_create_current_version = '1.0.1';
//------------------------------------------------------------------------//
//---Hook-----------------------------------------------------------------//
//------------------------------------------------------------------------//
//check for activating
if ($_GET['page'] == 'batch-create'){
	batch_create_make_current();
}
add_action('admin_menu', 'batch_create_plug_pages');
//------------------------------------------------------------------------//
//---Functions------------------------------------------------------------//
//------------------------------------------------------------------------//
function batch_create_make_current() {
	global $wpdb, $batch_create_current_version;
	if (get_site_option( "batch_create_version" ) == '') {
		add_site_option( 'batch_create_version', '0.0.0' );
	}
	
	if (get_site_option( "batch_create_version" ) == $batch_create_current_version) {
		// do nothing
	} else {
		//update to current version
		update_site_option( "batch_create_installed", "no" );
		update_site_option( "batch_create_version", $batch_create_current_version );
	}
	batch_create_global_install();
	//--------------------------------------------------//
	if (get_option( "batch_create_version" ) == '') {
		add_option( 'batch_create_version', '0.0.0' );
	}
	
	if (get_option( "batch_create_version" ) == $batch_create_current_version) {
		// do nothing
	} else {
		//update to current version
		update_option( "batch_create_version", $batch_create_current_version );
		batch_create_blog_install();
	}
}

function batch_create_blog_install() {
	global $wpdb, $batch_create_current_version;
	$batch_create_hits_table = "";

	//$wpdb->query( $batch_create_hits_table );
}

function batch_create_global_install() {
	global $wpdb, $batch_create_current_version;
	if (get_site_option( "batch_create_installed" ) == '') {
		add_site_option( 'batch_create_installed', 'no' );
	}
	
	if (get_site_option( "batch_create_installed" ) == "yes") {
		// do nothing
	} else {
	
		$batch_create_table1 = "CREATE TABLE IF NOT EXISTS `" . $wpdb->base_prefix . "batch_create_queue` (
  `batch_create_ID` bigint(20) unsigned NOT NULL auto_increment,
  `batch_create_site` bigint(20),
  `batch_create_blog_name` varchar(255) NOT NULL default 'null',
  `batch_create_blog_title` varchar(255) NOT NULL default 'null',
  `batch_create_user_name` varchar(255) NOT NULL default 'null',
  `batch_create_user_pass` varchar(255) NOT NULL default 'null',
  `batch_create_user_email` varchar(255) NOT NULL default 'null',
  PRIMARY KEY  (`batch_create_ID`)
) ENGINE=MyISAM;";

		$wpdb->query( $batch_create_table1 );

		update_site_option( "batch_create_installed", "yes" );
	}
}

function batch_create_plug_pages() {
	global $wpdb, $wp_roles, $current_user;
	add_submenu_page('ms-admin.php', 'Batch Create', 'Batch Create', 10, 'batch-create', 'batch_create_page_main_output');
}

function batch_create_queue_insert($tmp_blog_name,$tmp_blog_title,$tmp_user_name,$tmp_user_pass,$tmp_user_email) {
	global $wpdb, $current_site;
	$wpdb->query( "INSERT INTO " . $wpdb->base_prefix . "batch_create_queue (batch_create_site, batch_create_blog_name, batch_create_blog_title, batch_create_user_name, batch_create_user_pass, batch_create_user_email) VALUES ( '" . $current_site->id . "', '" . $tmp_blog_name . "', '" . wp_specialchars($tmp_blog_title) . "', '" . $tmp_user_name . "', '" . $tmp_user_pass . "', '" . $tmp_user_email . "' )" );
}

function batch_create_queue_remove($batch_create_id) {
	global $wpdb, $current_site;
	$wpdb->query( "DELETE FROM " . $wpdb->base_prefix . "batch_create_queue WHERE batch_create_ID = '" . $batch_create_id . "' AND batch_create_site = '" . $current_site->id . "'" );
}

function batch_create_queue_clear() {
	global $wpdb, $current_site;
	$wpdb->query( "DELETE FROM " . $wpdb->base_prefix . "batch_create_queue WHERE batch_create_site = '" . $current_site->id . "'" );
}

function batch_create_process_queue($tmp_blog_name,$tmp_blog_title,$tmp_user_name,$tmp_user_pass,$tmp_user_email,$tmp_item_id) {
	global $wpdb, $current_site;
	$blog_id = '';
	$user_id = '';
	$base = '/';
	if ($tmp_blog_name == '' || $tmp_blog_name == strtolower('null')){
		$tmp_create_blog = 'no';
	} else {
		$tmp_create_blog = 'yes';
	}
	/*
	if ($tmp_user_name == '' || $tmp_user_name == strtolower('null')){
		$tmp_user_name = $tmp_blog_name;
	}
	*/
	$tmp_domain = strtolower( wp_specialchars($tmp_blog_name) );
	$tmp_user_email = trim(wp_specialchars($tmp_user_email));
	$tmp_blog_title = $tmp_blog_title;
	if( constant( "VHOST" ) == 'yes' ) {
		$tmp_blog_domain = $tmp_domain.".".$current_site->domain;
		$tmp_blog_path = $base;
	} else {
		$tmp_blog_domain = $current_site->domain;
		$tmp_blog_path = $base.$tmp_domain.'/';
	}
	
	$tmp_user_exists_check = $wpdb->get_var("SELECT COUNT(*) FROM wp_users WHERE user_email = '" . $tmp_user_email . "'");
	//echo '|' . $tmp_user_exists_check . '|' . $tmp_add_blog_email;
	if ($tmp_user_exists_check > 0){
		//user exists
		$user_id = $wpdb->get_var("SELECT ID FROM $wpdb->users WHERE user_email = '" . $tmp_user_email . "'");
	} else {
		if ($tmp_user_pass == '' || $tmp_user_pass == strtolower('null')){
			$tmp_user_pass = generate_random_password();
		}
		
		$user_id = wpmu_create_user( $tmp_user_name, $tmp_user_pass,  $tmp_user_email );
		if(false == $user_id) {
			die( __("<p>There was an error creating the user</p>") );
		} else {
			wp_new_user_notification($user_id, $tmp_user_pass);
			echo 'User: ' . $tmp_user_name . ' created!<br />';
		}
	}

	$wpdb->hide_errors();
	if ($tmp_create_blog == 'yes'){
		$blog_id = wpmu_create_blog($tmp_blog_domain, $tmp_blog_path, wp_specialchars( $tmp_blog_title ), $user_id ,'', $current_site->id);
		//$wpdb->show_errors();
		$wpdb->hide_errors();
		if( !is_wp_error($blog_id) ) {
			$content_mail = sprintf(__('New blog created by %1s\n\nAddress: http://%2s\nName: %3s'), $current_user->user_login , $tmp_blog_domain.$tmp_blog_path, wp_specialchars($tmp_blog_title) );
			@wp_mail( get_option('admin_email'),  sprintf(__('[%s] New Blog Created'), $current_site->site_name), $content_mail );
			//wp_redirect( add_queuery_arg( "updated", "blogadded", $_SERVER[ 'HTTP_REFERER' ] ) );
		
			//send email
			wpmu_welcome_notification($blog_id, $user_id, $tmp_user_pass, wp_specialchars( $tmp_blog_title ), '');
			echo 'Blog: ' . $tmp_add_blog_domain . ' created!<br />';
		} else {
			echo 'Error creating blog: ' . $tmp_add_blog_domain . ' - ' . $blog_id->get_error_message() . '<br />';
		}
	}
}

//------------------------------------------------------------------------//
//---Page Output Functions------------------------------------------------//
//------------------------------------------------------------------------//

function batch_create_page_main_output() {
	global $wpdb, $wp_roles, $current_user, $current_site;
	/*
	if(!current_blog_can('manage_options')) {
		echo "<p>Nice Try...</p>";  //If accessed properly, this message doesn't appear.
		return;
	}
	*/
	if (isset($_GET['updated'])) {
		?><div id="message" class="updated fade"><p><?php _e('' . urldecode($_GET['updatedmsg']) . '') ?></p></div><?php
	}
	echo '<div class="wrap">';
	switch( $_GET[ 'action' ] ) {
		//---------------------------------------------------//
		default:
		$tmp_queue_count_count = $wpdb->get_var("SELECT COUNT(*) FROM wp_batch_create_queue WHERE batch_create_site = '" . $current_site->id . "'");
		?>
			<h2><?php _e('Batch Create') ?></h2>
            <?php
			if ($tmp_queue_count_count > 0){
			?>
            <p><strong>Note:</strong> There are <?php echo $tmp_queue_count_count; ?> items (blogs/users) waiting to be processed. Click <a href="ms-admin.php?page=batch-create&action=loop">here</a> to process the queue. If there is a problem, you can clear the queue by clicking <a href="ms-admin.php?page=batch-create&action=clear">here</a>.</p>
            <?php
			}
			?>
			<form action="ms-admin.php?page=batch-create&action=process" method="post" enctype="multipart/form-data">
			<p>Upload a <a href="http://en.wikipedia.org/wiki/Comma-separated_values">CSV</a> .txt file with the form below. Click <a href="http://static.wpmudev.org/example_csv_file.txt">here</a> for an example CSV .txt file and <a href="ms-admin.php?page=batch-create&action=detailed_instructions">here</a> for detailed instructions.</p>
			<p>
			  <input name="csv_file" id="csv_file" size="20" type="file">
			  <input type="hidden" name="MAX_FILE_SIZE" value="100000" />
			</p>
			<p class="submit">
			  <input name="Submit" value="<?php _e('Upload &raquo;') ?>" type="submit">
			</p>
			</form>
            <p>
            <strong>Please Note</strong><br /><br />
            
            Spam filters, especially strict ones for institutional email addresses, may well block username and login information from reaching users.<br /><br />
            
            In this case you should either try to use free webmail accounts that won't block the emails (such as gmail.com, hotmail.com or mail.yahoo.com) or preset passwords.<br /><br />
            
            If your users do not have email accounts you can still set them up using a gmail.com address and adding a number for each different user. For example: myname+1@gmail.com, myname+2@gmail.com, myname+3@gmail.com<br /><br />
            
            The system will treat each of these as a separate email account but they will all arrive at myname@gmail.com
            </p>
        <?php
		break;
		//---------------------------------------------------//
		case "process":
			
			$target_path = constant( "ABSPATH" ) . constant( "UPLOADS" );
			$base_target_path = constant( "ABSPATH" ) . str_replace("/files/", "", constant( "UPLOADS" ));
			if (is_dir($base_target_path)) {
			} else {
				mkdir($base_target_path, 0777);
			}			
			if (is_dir($target_path)) {
			} else {
				mkdir($target_path, 0777);
			}
			$target_path = $target_path . basename( $_FILES['csv_file']['name']); 
			?>
			<h2><?php _e('Batch Create') ?></h2>
			<?php
			if(move_uploaded_file($_FILES['csv_file']['tmp_name'], $target_path)) {
				echo 'it worked!';
				$file_path = $target_path;
				$handle = @fopen($file_path, "r");
				
				if ($handle) {
					while (!feof($handle)) {
						$buffer = fgets($handle, 4096);
						//-------------------------------------//
						//echo $buffer;
						$tmp_line = $buffer;
						list($tmp_blog_name,$tmp_blog_title,$tmp_user_name,$tmp_user_pass,$tmp_user_email) = split('[,]', $tmp_line);
	
						batch_create_queue_insert($tmp_blog_name,$tmp_blog_title,$tmp_user_name,$tmp_user_pass,$tmp_user_email);
						//-------------------------------------//
					}
				}
			echo "
			<SCRIPT LANGUAGE='JavaScript'>
			window.location='ms-admin.php?page=batch-create&updated=true&updatedmsg=" . urlencode(__('Items added to queue.')) . "';
			</script>
			";
			} else{
				echo "<p>There was an error uploading the file, please try again!</p>";
			}
		break;
		//---------------------------------------------------//
		case "loop":
			set_time_limit(0);
			$tmp_queue_count_count = $wpdb->get_var("SELECT COUNT(*) FROM wp_batch_create_queue WHERE batch_create_site = '" . $current_site->id . "'");
			if ($tmp_queue_count_count == 0){
				echo "
				<SCRIPT LANGUAGE='JavaScript'>
				window.location='ms-admin.php?page=batch-create&updated=true&updatedmsg=" . urlencode(__('Finished!')) . "';
				</script>
				";	
			} else {
				?>
				<h2><?php _e('Batch Create') ?></h2>
				<p>Creating blogs... Roughly <?php echo $tmp_blogs_left_count; ?> left to create.</p>
				<?php
				
				//------------------------------//
				$query = "SELECT batch_create_ID, batch_create_blog_name, batch_create_blog_title, batch_create_user_name, batch_create_user_pass, batch_create_user_email FROM " . $wpdb->base_prefix . "batch_create_queue WHERE batch_create_site = '" . $current_site->id . "' LIMIT 1";
				$batch_create_queue_items_list = $wpdb->get_results( $query, ARRAY_A );
				//------------------------------//
				if (count($batch_create_queue_items_list) > 0){
					foreach ($batch_create_queue_items_list as $batch_create_queue_item){
						batch_create_process_queue($batch_create_queue_item['batch_create_blog_name'],$batch_create_queue_item['batch_create_blog_title'],$batch_create_queue_item['batch_create_user_name'],$batch_create_queue_item['batch_create_user_pass'],$batch_create_queue_item['batch_create_user_email'], $batch_create_queue_item['batch_create_ID']);
						batch_create_queue_remove($batch_create_queue_item['batch_create_ID']);
					}
				}
				//------------------------------//

				echo "
				<SCRIPT LANGUAGE='JavaScript'>
				window.location.reload();
				</script>
				";
				/*
				echo "
				<SCRIPT LANGUAGE='JavaScript'>
				window.location='ms-admin.php?page=batch-create&action=loop';
				</script>
				";
				*/
			}
		break;
		//---------------------------------------------------//
		case "detailed_instructions":
			?>
			<h2><?php _e('Detailed Instructions') ?></h2>
            <p>
BLOG_NAME,BLOG_TITLE,USER_NAME,USER_PASS,USER_EMAIL<br /><br />

BLOG_NAME = The name of the blog you want created. If you do not want the user to have a blog, please set this to 'null' without the quotation marks. No spaces allowed.<br /><br />

BLOG_TITLE = The title of the blog. This can be changed later.<br /><br />

USER_NAME = The name of the user. No spaces allowed.<br /><br />

USER_PASS = If you would like a password auto-generated please set this to 'null' without the quotation marks. No spaces allowed.<br /><br />

USER_EMAIL = You must provide a valid email for each user<br /><br />

Examples:<br /><br />

User with blog and preset password:<br />
demoblogname1,Demo Blog Title 1,username1,userpass1, useremail@domain.com<br /><br />

User with blog and auto-generated password:<br />
demoblogname2,Demo Blog Title 2,username2,null,useremail2@domain.com<br /><br />

User without blog:<br />
null,null,username3,userpass3,useremail3@domain.com<br /><br />


Together in a file these would look like:<br /><br />

demoblogname1,Demo Blog Title 1,username1,userpass1, useremail@domain.com<br />
demoblogname2,Demo Blog Title 2,username2,null,useremail2@domain.com<br />
null,null,username3,userpass3,useremail3@domain.com<br />
            </p>
            <?php
		break;
		//---------------------------------------------------//
		case "temp":
		break;
		//---------------------------------------------------//
		case "clear":
			batch_create_queue_clear();
			echo "
			<SCRIPT LANGUAGE='JavaScript'>
			window.location='ms-admin.php?page=batch-create&updated=true&updatedmsg=" . urlencode(__('Queue cleared.')) . "';
			</script>
			";
		break;
		//---------------------------------------------------//
	}
	echo '</div>';
}

?>
