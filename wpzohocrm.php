<?php
/*
Plugin Name: WooCommerce with Zoho CRM Integration
Plugin URI: https://wordpress.org/plugins/wpzohocrm
Description: WooCommerce with Zoho CRM Integration plugin automatically adds the customer as a contact and/or lead in your Zoho CRM account whenever an order is placed in WooCommerce.
Author: Keval Shah
Version: 1.0
Author URI: http://kevalshah.webs.com/
*/


if(get_option('wpzohocrm_status')=='enable'){
require_once(__DIR__.'/zoho_methods.php');
add_action( 'init', 'codex_leads_init' );
add_action( 'init', 'codex_contacts_init' );
}
if ( isset($_GET['sync']) && 'leads' ===  $_GET['sync'] ){
	if(empty(get_option('wpzohocrm_auth_key')) || '' === get_option('wpzohocrm_auth_key') ){
		add_action( 'admin_notices', 'zoho_auth_key_error' );
	}else{
	add_action('admin_init','sync_leads_init');
	}
}

if ( isset($_GET['sync']) && 'contacts' ===  $_GET['sync'] ){
	if(empty(get_option('wpzohocrm_auth_key')) || '' === get_option('wpzohocrm_auth_key') ){
		add_action( 'admin_notices', 'zoho_auth_key_error' );
	}else{
	add_action('admin_init','sync_contacts_init');
	}
}
 
function wpzohocrm_menu_page(){
    add_menu_page( 
        __( 'Zoho CRM Integration', 'wpzohocrm' ),
        'Zoho CRM Integration',
        'manage_options',
        'wpzohocrm',
        'wpzohocrm_menu_page_callaback'
    ); 
	if(get_option('wpzohocrm_status')=='enable'){
	add_submenu_page( 'wpzohocrm', 'Leads', 'Leads','manage_options', 'edit.php?post_type=leads');
	add_submenu_page( 'wpzohocrm', 'Contacts', 'Contacts','manage_options', 'edit.php?post_type=contacts');
	}
}

add_action('admin_init', 'wpzohocrm_settings_add');
add_action('admin_init', 'wpzohocrm_settings_fields');
add_action( 'admin_menu', 'wpzohocrm_menu_page' );

function wpzohocrm_menu_page_callaback(){ 
?>
    <div class="wrap">
	
	<?php if ( isset( $_GET['settings-updated'] ) ) {echo '<div id="message" class="updated notice notice-success is-dismissible"><p>Settings updated.</div>';} ?>
	<?php if ( isset($_GET['sync']) && 'leads' ===  $_GET['sync'] && !empty(get_option('wpzohocrm_auth_key'))){echo '<div id="message" class="updated notice notice-success is-dismissible"><p>Leads Synchronize Completed.</div>';} ?>
	<?php if ( isset($_GET['sync']) && 'contacts' ===  $_GET['sync'] && !empty(get_option('wpzohocrm_auth_key'))  ){echo '<div id="message" class="updated notice notice-success is-dismissible"><p>Contacts Synchronize Completed.</div>';} ?>
			<form method="post" action="options.php">
					<?php settings_fields( 'wpzohocrm_fields_settings' ); ?>
					<?php do_settings_sections( 'wpzohocrm_settings' ); ?>
					<?php submit_button(); ?>
			</form>
			<p><a href="?page=wpzohocrm&sync=leads">Sync Leads</a></p>
			<p><a href="?page=wpzohocrm&sync=contacts">Sync Contacts</a></p>
			</div>  
<?php }
function zoho_auth_key_error(){ ?>
	<div class="error notice">
        <p><?php _e( 'Please enter ZOHO CRM Authorization Key. <a href="https://www.zoho.com/creator/help/api/prerequisites/generate-auth-token.html" target="_blank">Generate ZOHO CRM Authorization Key!</a>', 'wpzohocrm' ); ?></p>
    </div>
<?php return false; }
function wpzohocrm_settings_add() {
add_settings_section( 'wpzohocrm_settings', 'Zoho CRM Integration', 'wpzohocrm_settings_callback', 'wpzohocrm_settings' );
if(empty(get_option('sync_number')) || '' === get_option('sync_number') ){
update_option('sync_number',10);
}
}
function wpzohocrm_settings_callback() {
	}
function wpzohocrm_status_setting_callback(){
		$options = array('disable'=>'Disable','enable'=>'Enable');
					$options_markup = "";
					$value = get_option( 'wpzohocrm_status' );
					if( ! $value ) { // If no value exists
						$value = 'enable';
					}
					foreach( $options as $key => $label ){
						$options_markup .= sprintf( '<option value="%s" %s>%s</option>', $key, selected( $value, $key, false ), $label );
					}
					printf( '<select name="%1$s" id="%1$s">%2$s</select>', 'wpzohocrm_status', $options_markup );
				
}
function wpzohocrm_auth_key_setting_callback(){
	$value = get_option( 'wpzohocrm_auth_key' );
		printf( '<input name="%1$s" id="%1$s" type="%2$s" placeholder="%3$s" value="%4$s" />','wpzohocrm_auth_key', 'text', '',$value);
				
}
function wpzohocrm_sync_number_setting_callback(){
	$value = get_option( 'sync_number' );
		printf( '<input name="%1$s" id="%1$s" type="%2$s" placeholder="%3$s" value="%4$s" />','sync_number', 'text', '',$value);
				
}
function wpzohocrm_settings_fields() {
		add_settings_field(
	'wpzohocrm_status',
	'Status',
	'wpzohocrm_status_setting_callback',
	'wpzohocrm_settings',
	'wpzohocrm_settings'
);     
register_setting( 'wpzohocrm_fields_settings', 'wpzohocrm_status' );
if(get_option('wpzohocrm_status')=='enable'){
add_settings_field(
	'wpzohocrm_auth_key',
	'Authorization Key',
	'wpzohocrm_auth_key_setting_callback',
	'wpzohocrm_settings',
	'wpzohocrm_settings'
);     
register_setting( 'wpzohocrm_fields_settings', 'wpzohocrm_auth_key' );
add_settings_field(
	'sync_number',
	'Synchronize Number',
	'wpzohocrm_sync_number_setting_callback',
	'wpzohocrm_settings',
	'wpzohocrm_settings'
);     
register_setting( 'wpzohocrm_fields_settings', 'sync_number' );	
}	
	}


function codex_leads_init() {
	$labels = array(
		'name'               => _x( 'Leads', 'wpzohocrm' ),
		'singular_name'      => _x( 'Lead', 'wpzohocrm' ),
		'menu_name'          => _x( 'Leads', 'wpzohocrm' ),
		'name_admin_bar'     => _x( 'Lead','wpzohocrm' ),
		'add_new'            => _x( 'Add New', 'wpzohocrm' ),
		'add_new_item'       => __( 'Add New Lead', 'wpzohocrm' ),
		'new_item'           => __( 'New Lead', 'wpzohocrm' ),
		'edit_item'          => __( 'Edit Lead', 'wpzohocrm' ),
		'view_item'          => __( 'View Lead', 'wpzohocrm' ),
		'all_items'          => __( 'All Leads', 'wpzohocrm' ),
		'search_items'       => __( 'Search Leads', 'wpzohocrm' ),
		'parent_item_colon'  => __( 'Parent Leads:', 'wpzohocrm' ),
		'not_found'          => __( 'No Leads found.', 'wpzohocrm' ),
		'not_found_in_trash' => __( 'No Leads found in Trash.', 'wpzohocrm' )
	);

	$args = array(
		'labels'             => $labels,
                'description'        => __( 'Description.', 'wpzohocrm' ),
		'public'             => false,
		'publicly_queryable' => false,
		'show_ui'            => true,
		'show_in_menu'       => false,
		'query_var'          => false,
		'rewrite'            => false,
		'capability_type'    => 'post',
		'capabilities' => array(
			'create_posts' => 'do_not_allow',
		),
		'map_meta_cap' => true,
		'has_archive'        => true,
		'hierarchical'       => false,
		'menu_position'      => null,
		'supports'           => array( 'title','custom-fields')
	);

	register_post_type( 'leads', $args );
}



function codex_contacts_init() {
	$labels = array(
		'name'               => _x( 'Contacts', 'wpzohocrm' ),
		'singular_name'      => _x( 'Contact', 'wpzohocrm' ),
		'menu_name'          => _x( 'Contacts', 'wpzohocrm' ),
		'name_admin_bar'     => _x( 'Contact', 'wpzohocrm' ),
		'add_new'            => _x( 'Add New', 'wpzohocrm' ),
		'add_new_item'       => __( 'Add New Contact', 'wpzohocrm' ),
		'new_item'           => __( 'New Contact', 'wpzohocrm' ),
		'edit_item'          => __( 'Edit Contact', 'wpzohocrm' ),
		'view_item'          => __( 'View Contact', 'wpzohocrm' ),
		'all_items'          => __( 'All Contacts', 'wpzohocrm' ),
		'search_items'       => __( 'Search Contacts', 'wpzohocrm' ),
		'parent_item_colon'  => __( 'Parent Contacts:', 'wpzohocrm' ),
		'not_found'          => __( 'No Contacts found.', 'wpzohocrm' ),
		'not_found_in_trash' => __( 'No Contacts found in Trash.', 'wpzohocrm' )
	);

	$args = array(
		'labels'             => $labels,
                'description'        => __( 'Description.', 'wpzohocrm' ),
		'public'             => false,
		'publicly_queryable' => false,
		'show_ui'            => true,
		'show_in_menu'       => false,
		'query_var'          => false,
		'rewrite'            => false,
		'capability_type'    => 'post',
		'capabilities' => array(
			'create_posts' => 'do_not_allow',
		),
		'map_meta_cap' => true,
		'has_archive'        => true,
		'hierarchical'       => false,
		'menu_position'      => null,
		'supports'           => array( 'title','custom-fields')
	);

	register_post_type( 'contacts', $args );
}

if(get_option('wpzohocrm_status')=='enable'){
if ( is_admin() ) {
		if ( 'post.php' === $pagenow && isset($_GET['post']) && 'leads' === get_post_type( $_GET['post'] ) ){
            add_action( 'load-post.php',     'init_metabox');
            add_action( 'load-post-new.php', 'init_metabox');
		}
        }
function init_metabox() {
        add_action( 'add_meta_boxes','add_metabox');
        add_action( 'save_post','save_metabox');
    }
function add_metabox() {
		global $post;
		$AUTH_TOKEN = get_option('wpzohocrm_auth_key'); 
		$zoho_api = new Zoho($AUTH_TOKEN);
		
		$zoho_result = $zoho_api->get_records('Leads',1,1, array(), false, '');
		foreach ($zoho_result as $zkey => $zvalue){
			foreach($zvalue as $key => $value){
				$id = $key;
				add_meta_box($id,__( $key, 'wpzohocrm' ),'render_metabox','leads');
			}
		}
		
		
    }
 
    /**
     * Renders the meta box.
     */
function render_metabox($post, $metabox) {
		$id = $metabox['id'];
		echo '<p><input type="text" name="'.$id.'" value="'.get_post_meta( $post->ID, $id, true ).'" /></p>';
    }
 
function save_metabox( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return $post_id;
	}
	if ( 'leads' === $_POST['post_type'] ) {
		if ( !current_user_can( 'edit_page', $post_id ) ) {
			return $post_id;
		} elseif ( !current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}  
	}else{
		return;
	}
	$AUTH_TOKEN = get_option('wpzohocrm_auth_key'); 
		$zoho_api = new Zoho($AUTH_TOKEN);
		$leadid = get_post_meta( $post_id, 'LEADID', true );
		echo $leadid;
		if(!empty($leadid)){
		$ID = get_post_meta( $post_id, 'LEADID', true );
		$zoho_result = $zoho_api->get_record_by_id('Leads', $ID);
			foreach($zoho_result as $key => $value){
				$id = $key;
				$old = get_post_meta( $post_id, $id, true );
				if($_POST[$id]==$value){
				$new = $value;
				}else{
				$new = $_POST[$id];
				}
				if ( $new && $new !== $old ) {
					update_post_meta( $post_id, $id, $new );
				} elseif ( '' === $new && $old ) {
					delete_post_meta( $post_id, $id, $old );
				}
			}
		}else{
			$zoho_result = $zoho_api->get_records('Leads',1,1, array(), false, '');
		foreach ($zoho_result as $zkey => $zvalue){
			foreach($zvalue as $key => $value){
				$id = $key;
				$old = get_post_meta( $post_id, $id, true );
				if(empty($_POST[$id])){
				$new = $value;
				}else{
				$new = $_POST[$id];
				}
				if ( $new && $new !== $old ) {
					update_post_meta( $post_id, $id, $new );
				} elseif ( '' === $new && $old ) {
					delete_post_meta( $post_id, $id, $old );
				}
			}
		}
		
		}

	}


if ( is_admin() ) {
		if ( 'post.php' === $pagenow && isset($_GET['post']) && 'contacts' === get_post_type( $_GET['post'] ) ){
            add_action( 'load-post.php',     'init_metabox_contacts');
            add_action( 'load-post-new.php', 'init_metabox_contacts');
		}
        }
function init_metabox_contacts() {
        add_action( 'add_meta_boxes','add_metabox_contacts');
        add_action( 'save_post','save_metabox_contacts');
    }
function add_metabox_contacts() {
		global $post;
		$AUTH_TOKEN = get_option('wpzohocrm_auth_key'); 
		$zoho_api = new Zoho($AUTH_TOKEN);
		
		$zoho_result = $zoho_api->get_records('Contacts',1,1, array(), false, '');
		foreach ($zoho_result as $zkey => $zvalue){
			foreach($zvalue as $key => $value){
				$id = $key;
				add_meta_box($id,__( $key, 'wpzohocrm' ),'render_metabox_contacts','contacts');
			}
		}
		
		
    }
 
    /**
     * Renders the meta box.
     */
function render_metabox_contacts($post, $metabox) {
		$id = $metabox['id'];
		echo '<p><input type="text" name="'.$id.'" value="'.get_post_meta( $post->ID, $id, true ).'" /></p>';
    }
 
function save_metabox_contacts( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return $post_id;
	}
	if ( 'contacts' === $_POST['post_type'] ) {
		if ( !current_user_can( 'edit_page', $post_id ) ) {
			return $post_id;
		} elseif ( !current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}  
	}else{
		return;
	}
	$AUTH_TOKEN = get_option('wpzohocrm_auth_key'); 
		$zoho_api = new Zoho($AUTH_TOKEN);
		$leadid = get_post_meta( $post_id, 'CONTACTID', true );
		//echo $leadid;
		if(!empty($leadid)){
		$ID = get_post_meta( $post_id, 'CONTACTID', true );
		$zoho_result = $zoho_api->get_record_by_id('Leads', $ID);
			foreach($zoho_result as $key => $value){
				$id = $key;
				$old = get_post_meta( $post_id, $id, true );
				if($_POST[$id]==$value){
				$new = $value;
				}else{
				$new = $_POST[$id];
				}
				if ( $new && $new !== $old ) {
					update_post_meta( $post_id, $id, $new );
				} elseif ( '' === $new && $old ) {
					delete_post_meta( $post_id, $id, $old );
				}
			}
		}else{
			$zoho_result = $zoho_api->get_records('Contacts',1,1, array(), false, '');
		foreach ($zoho_result as $zkey => $zvalue){
			foreach($zvalue as $key => $value){
				$id = $key;
				$old = get_post_meta( $post_id, $id, true );
				if(empty($_POST[$id])){
				$new = $value;
				}else{
				$new = $_POST[$id];
				}
				if ( $new && $new !== $old ) {
					update_post_meta( $post_id, $id, $new );
				} elseif ( '' === $new && $old ) {
					delete_post_meta( $post_id, $id, $old );
				}
			}
		}
		
		}

	}
	


add_action('woocommerce_thankyou', 'add_leads_init', 10, 1);
function add_leads_init($order_id){
	$AUTH_TOKEN = get_option('wpzohocrm_auth_key'); 
		$zoho_api = new Zoho($AUTH_TOKEN);
		
		
		
	 if ( ! $order_id )
        return;

    // Getting an instance of the order object
    $order = wc_get_order( $order_id );

	$order_data = $order->get_data();
	
	$order_billing_first_name = $order_data['billing']['first_name'];
	$order_billing_last_name = $order_data['billing']['last_name'];
	$order_billing_company = $order_data['billing']['company'];
	$order_billing_address_1 = $order_data['billing']['address_1'];
	$order_billing_address_2 = $order_data['billing']['address_2'];
	$order_billing_city = $order_data['billing']['city'];
	$order_billing_state = $order_data['billing']['state'];
	$order_billing_postcode = $order_data['billing']['postcode'];
	$order_billing_country = $order_data['billing']['country'];
	$order_billing_email = $order_data['billing']['email'];
	$order_billing_phone = $order_data['billing']['phone'];
	
	$leads_data = array();  
	$leads_data['First Name'] = $order_billing_first_name;
	$leads_data['Last Name'] = $order_billing_last_name;
	$leads_data['Company'] = $order_billing_company;
	$leads_data['Email'] = $order_billing_email;
	$leads_data['Phone'] = $order_billing_phone;
	$leads_data['City'] = $order_billing_city;
	$leads_data['State'] = $order_billing_state;
	$leads_data['Zip Code'] = $order_billing_postcode;
	$leads_data['Country'] = $order_billing_country;
	$leads_data['Lead Source'] = 'Online Store';
	
	$email = $order_billing_email;  
	$zoho_result = $zoho_api->get_record_by_searching('Leads', "(Email:$email)");
	if(empty($zoho_result['LEADID'])){
	$response = $zoho_api->insert_record('Leads', $leads_data);
	}
	
}

add_action('woocommerce_thankyou', 'add_contacts_init', 10, 1);
function add_contacts_init($order_id){
	$AUTH_TOKEN = get_option('wpzohocrm_auth_key'); 
		$zoho_api = new Zoho($AUTH_TOKEN);
		
		
	 if ( ! $order_id )
        return;

    // Getting an instance of the order object
    $order = wc_get_order( $order_id );

	$order_data = $order->get_data();
	
	$order_billing_first_name = $order_data['billing']['first_name'];
	$order_billing_last_name = $order_data['billing']['last_name'];
	$order_billing_company = $order_data['billing']['company'];
	$order_billing_address_1 = $order_data['billing']['address_1'];
	$order_billing_address_2 = $order_data['billing']['address_2'];
	$order_billing_city = $order_data['billing']['city'];
	$order_billing_state = $order_data['billing']['state'];
	$order_billing_postcode = $order_data['billing']['postcode'];
	$order_billing_country = $order_data['billing']['country'];
	$order_billing_email = $order_data['billing']['email'];
	$order_billing_phone = $order_data['billing']['phone'];
	
	$contacts_data = array();  
	$contacts_data['Account Name'] = $order_billing_first_name.' '.$order_billing_last_name;
	$contacts_data['Full Name'] = $order_billing_first_name.' '.$order_billing_last_name;
	$contacts_data['First Name'] = $order_billing_first_name;
	$contacts_data['Last Name'] = $order_billing_last_name;
	$contacts_data['Company'] = $order_billing_company;
	$contacts_data['Email'] = $order_billing_email;
	$contacts_data['Phone'] = $order_billing_phone;
	$contacts_data['Mailing Street'] = $order_billing_address_1;
	$contacts_data['Other Street']= $order_billing_address_2;
	$contacts_data['Mailing City'] = $order_billing_city;
	$contacts_data['Mailing State'] = $order_billing_state;
	$contacts_data['Mailing Zip'] = $order_billing_postcode;
	$contacts_data['Mailing Country'] = $order_billing_country;
	
	$email = $order_billing_email;  
	$zoho_result = $zoho_api->get_record_by_searching('Contacts', "(Email:$email)");
	if(empty($zoho_result['CONTACTID'])){
	$response = $zoho_api->insert_record('Contacts', $contacts_data);
	}
	
}


function sync_leads_init(){
	
	global $wpdb,$wp;
	
		$AUTH_TOKEN = get_option('wpzohocrm_auth_key');  
		$zoho_api = new Zoho($AUTH_TOKEN);
		$sync_number = get_option('sync_number');
		$count_leads = wp_count_posts('leads');
		if($count_leads->publish==0){
		$start_index = 1;
		}else{
			$start_index = $count_leads->publish;
		}
		$sync_number = $count_leads->publish + $sync_number;
		
		$zoho_result = $zoho_api->get_records('Leads',$start_index,$sync_number, [],false,'Created_Time',true);
		foreach ($zoho_result as $zkey => $zvalue){
			
			if(empty($zvalue['First Name']) || $zvalue['First Name'] === '' || $zvalue['First Name'] === NULL || $zvalue['First Name'] == 'null'){
				$title = $zvalue['Last Name'];
			}else if(empty($zvalue['Last Name']) || $zvalue['Last Name'] === '' || $zvalue['Last Name']=== NULL || $zvalue['Last Name']== 'null'){
				$title = $zvalue['First Name'];
			}else{
				$title = $zvalue['First Name'].' '.$zvalue['Last Name'];
			}
			$leadid = $zvalue['LEADID'];
			$sql = "SELECT meta_value FROM ".$wpdb->postmeta." WHERE meta_value LIKE ".$leadid;
			$leadiddb = $wpdb->get_var( $sql );
			if($leadiddb!=$leadid){
			$my_post = array(
			  'post_title'    => $title,
			  'post_status'   => 'publish',
			  'post_type' => 'leads',
				'meta_input'   => $zvalue
				);
				wp_insert_post( $my_post );
			}
		}
		
		
	
}

function sync_contacts_init(){
	
	global $wpdb;
		$AUTH_TOKEN = get_option('wpzohocrm_auth_key');  
		$zoho_api = new Zoho($AUTH_TOKEN); 
		$sync_number = get_option('sync_number');
		$count_contacts = wp_count_posts('contacts');
		if($count_contacts->publish==0){
		$start_index = 1;
		}else{
			$start_index = $count_contacts->publish;
		}
		$sync_number = $count_contacts->publish + $sync_number;
		
		$zoho_result = $zoho_api->get_records('Contacts',$start_index,$sync_number, [],false,'Created_Time',true);
		$post_meta = array();
		foreach ($zoho_result as $zkey => $zvalue){
		$contactid = $zvalue['CONTACTID'];
			$sql = "SELECT meta_value FROM ".$wpdb->postmeta." WHERE meta_value LIKE ".$contactid;
			$contactdb = $wpdb->get_var( $sql );
			if(empty($zvalue['First Name']) || $zvalue['First Name'] === '' || $zvalue['First Name'] === NULL || $zvalue['First Name'] == 'null'){
				$title = $zvalue['Last Name'];
			}else if(empty($zvalue['Last Name']) || $zvalue['Last Name'] === '' || $zvalue['Last Name']=== NULL || $zvalue['Last Name']== 'null'){
				$title = $zvalue['First Name'];
			}else{
				$title = $zvalue['First Name'].' '.$zvalue['Last Name'];
			}
			if($contactid!=$contactdb){
			$my_post = array(
			  'post_title'    => $title,
			  'post_status'   => 'publish',
			  'post_type' => 'contacts',
				'meta_input'   => $zvalue
				);
			 
			// Insert the post into the database
			wp_insert_post( $my_post );
			}
			
		}
	
	
}





/*add_filter( 'cron_schedules', 'leads_add_every_three_minutes' );
function leads_add_every_three_minutes( $schedules ) {
    $schedules['every_three_minutes'] = array(
            'interval'  => 180,
            'display'   => __( 'Every 3 Minutes', 'textdomain' )
    );
    return $schedules;
}

// Schedule an action if it's not already scheduled
if ( ! wp_next_scheduled( 'leads_add_every_three_minutes' ) ) {
    wp_schedule_event( time(), 'every_three_minutes', 'leads_add_every_three_minutes' );
}

// Hook into that action that'll fire every three minutes
add_action( 'leads_add_every_three_minutes', 'sync_leads_init' );

add_filter( 'cron_schedules', 'contacts_add_every_three_minutes' );
function contacts_add_every_three_minutes( $schedules ) {
    $schedules['every_three_minutes'] = array(
            'interval'  => 180,
            'display'   => __( 'Every 3 Minutes', 'textdomain' )
    );
    return $schedules;
}

// Schedule an action if it's not already scheduled
if ( ! wp_next_scheduled( 'contacts_add_every_three_minutes' ) ) {
    wp_schedule_event( time(), 'every_three_minutes', 'contacts_add_every_three_minutes' );
}

// Hook into that action that'll fire every three minutes
add_action( 'contacts_add_every_three_minutes', 'sync_contacts_init' );*/


}
?>