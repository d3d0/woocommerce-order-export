<?php
/*
Plugin Name: Woocommerce Order Export
Plugin URI: https://github.com/d3d0/woocommerce-order-export
Description: Export woocommerce orders
Author: Lorenzo De Donato
Version: 1.0.0
Author URI: http://www.lorenzodedonato.com
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

add_action('admin_menu', 'myplugin_register_options_page');
function myplugin_register_options_page() {
  add_options_page(
    'Woocommerce Order Export Settings', 
    'Woocommerce Order Export', 
    'manage_options', 
    'woocommerce-order-export', 
    'wporg_options_page_html');
}
function wporg_options_page_html() {
  // check user capabilities
  if ( ! current_user_can( 'manage_options' ) ) {
      return;
  }
  ?>
  <div class="wrap">
      <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
      <br>
      <a href="<?php echo admin_url( 'admin.php?page=woocommerce-order-export' ) ?>&action=download_csv&_wpnonce=<?php echo wp_create_nonce( 'download_csv' )?>" class="page-title-action"><?php _e('Export to CSV','woocommerce-order-export');?></a>
      <form action="options.php" method="post">
          <?php
          // output security fields for the registered setting "wporg_options"
          settings_fields( 'wporg_options' );
          // output setting sections and their fields
          // (sections are registered for "wporg", each field is registered to a specific section)
          do_settings_sections( 'wporg' );
          // output save settings button
          submit_button( __( 'Save Settings', 'textdomain' ) );
          ?>
      </form>
  </div>
  <?php
}

if ( isset($_GET['action'] ) && $_GET['action'] == 'download_csv' )  {
	add_action( 'admin_init', 'csv_export');
}

function csv_export() {

    // Check for current user privileges 
    if( !current_user_can( 'manage_options' ) ){ return false; }

    // Check if we are in WP-Admin
    if( !is_admin() ){ return false; }

    // Nonce Check
    $nonce = isset( $_GET['_wpnonce'] ) ? $_GET['_wpnonce'] : '';
    if ( ! wp_verify_nonce( $nonce, 'download_csv' ) ) {
        die( 'Security check error' );
    }
    
    ob_start();

    $domain = $_SERVER['SERVER_NAME'];
    $filename = 'users-' . $domain . '-' . time() . '.csv';
    
    $header_row = array(
        'Email',
        'Name'
    );
    $data_rows = array();
    global $wpdb;
    $sql = 'SELECT * FROM ' . $wpdb->users;
    $users = $wpdb->get_results( $sql, 'ARRAY_A' );
    foreach ( $users as $user ) {
        $row = array(
            $user['user_email'],
            $user['user_name']
        );
        $data_rows[] = $row;
    }
    $fh = @fopen( 'php://output', 'w' );
    fprintf( $fh, chr(0xEF) . chr(0xBB) . chr(0xBF) );
    header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
    header( 'Content-Description: File Transfer' );
    header( 'Content-type: text/csv' );
    header( "Content-Disposition: attachment; filename={$filename}" );
    header( 'Expires: 0' );
    header( 'Pragma: public' );
    fputcsv( $fh, $header_row );
    foreach ( $data_rows as $data_row ) {
        fputcsv( $fh, $data_row );
    }
    fclose( $fh );
    
    ob_end_flush();
    
    die();
}

add_action('init','plugin_init');
function plugin_init(){

  if (class_exists("Woocommerce")) {

    // -----------------------------------------
    // -----------------------------------------

    // Disable unique sku
    add_filter( 'wc_product_has_unique_sku', '__return_false' );

    // -----------------------------------------
    // -----------------------------------------

    // Enqueue scripts and styles
    add_action( 'wp_enqueue_scripts', 'slick_enqueue_scripts_styles' );
    function slick_enqueue_scripts_styles() {
    	//wp_enqueue_script( 'custom_js', plugin_dir_url(__FILE__) . 'assets/js/script.js', array( 'script' ), '1.6.0', true );
    	wp_enqueue_style( 'custom_css', plugin_dir_url(__FILE__) . 'assets/css/style.css');
    }
    
    // -----------------------------------------
    // -----------------------------------------

  }
  
}
