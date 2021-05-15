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

// -----------------------------------------
// CREATE ORDER
// -----------------------------------------

add_action('woocommerce_new_order', 'create_order_and_send_email', 10, 1);

function create_order_and_send_email ($order_id) {
    // your code
    error_log('Ordine creato > '.$order_id);
    send_email_woocommerce_style('lorenzo.dedonato@gmail.com', 'Nuovo Ordine POL', 'Testata', 'Messaggio');
}

// @email - Email address of the receiver
// @subject - Subject of the email
// @heading - Heading to place inside of the woocommerce template
// @message - Body content (can be HTML)
function send_email_woocommerce_style($email, $subject, $heading, $message) {
  
  // Get file attachments
  $attachments = array( WP_CONTENT_DIR . '/debug.log' );
  
  $headers = array(
    'Content-Type: text/html; charset=UTF-8',
    'From: Person Name <email@here.com>'
  );
  
  // Get woocommerce mailer from instance
  $mailer = WC()->mailer();

  // Wrap message using woocommerce html email template
  $wrapped_message = $mailer->wrap_message($heading, $message);

  // Create new WC_Email instance
  $wc_email = new WC_Email;

  // Style the wrapped message with woocommerce inline styles
  $html_message = $wc_email->style_inline($wrapped_message);

  // Send the email using wordpress mail function
  wp_mail( $email, $subject, $html_message, $headers, $attachments );

}

// -----------------------------------------
// SETTING PAGE
// -----------------------------------------

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
  // orders query
  $orders = wc_get_orders( array('numberposts' => -1) );

  // get 10 most recent order ids in date descending order
  /*
  $query = new WC_Order_Query( array(
    'limit' => 1,
    'orderby' => 'date',
    'order' => 'DESC',
    'return' => 'ids',
  ) );
  $orders = $query->get_orders();
  */

  // get latest 1 order
  /*
  $args = array(
    'limit' => 1,
  );
  $orders = wc_get_orders( $args );
  */

  ?>
  <div class="wrap">
      <h1><?php echo esc_html( get_admin_page_title() ); ?></h1><br>
      
      <a href="<?php echo admin_url( 'admin.php?page=woocommerce-order-export' ) ?>&action=download_csv&_wpnonce=<?php echo wp_create_nonce( 'download_csv' )?>" class="page-title-action"><?php _e('Export to CSV','woocommerce-order-export');?></a>
      
      <br><br>

      <table class="widefat fixed" cellspacing="0">
      <thead>
        <tr>
            <th id="columnname" class="manage-column column-columnname " scope="col">N° ordine</th>
            <th id="columnname" class="manage-column column-columnname " scope="col">Stato</th>
            <th id="columnname" class="manage-column column-columnname " scope="col">Totale</th>
            <th id="columnname" class="manage-column column-columnname " scope="col">Email</th>
            <th id="columnname" class="manage-column column-columnname " scope="col">Nome</th>
            <th id="columnname" class="manage-column column-columnname " scope="col">Cognome</th>
            <th id="columnname" class="manage-column column-columnname " scope="col">Indirizzo 1</th>
            <th id="columnname" class="manage-column column-columnname " scope="col">Indirizzo 2</th>
            <th id="columnname" class="manage-column column-columnname " scope="col">CAP</th>
            <th id="columnname" class="manage-column column-columnname " scope="col">Provincia</th>
            <th id="columnname" class="manage-column column-columnname " scope="col">Stato</th>
            <th id="columnname" class="manage-column column-columnname " scope="col">Telefono</th>
        </tr>
        </thead>
        <tbody>
        <?php
        foreach( $orders as $order ){
            //var_dump($order);
            //echo $order->get_id() . '<br>'; // The order ID
            //echo $order->get_status() . '<br>'; // The order status
            //echo $order->get_total() . '<br>'; // The order status
            ?>
            <tr valign="top">
              <th class="column-columnname " scope="row"><?php echo $order->get_id(); ?></th>
              <td class="column-columnname "><?php echo $order->get_status(); ?></td>
              <td class="column-columnname "><?php echo $order->get_total(); ?></td>
              <td class="column-columnname "><?php echo $order->get_billing_email(); ?></td>
              <td class="column-columnname "><?php echo $order->get_billing_first_name(); ?></td>
              <td class="column-columnname "><?php echo $order->get_billing_last_name(); ?></td>
              <td class="column-columnname "><?php echo $order->get_billing_address_1(); ?></td>
              <td class="column-columnname "><?php echo $order->get_billing_address_2(); ?></td>
              <td class="column-columnname "><?php echo $order->get_billing_postcode(); ?></td>
              <td class="column-columnname "><?php echo $order->get_billing_state(); ?></td>
              <td class="column-columnname "><?php echo $order->get_billing_country(); ?></td>
              <td class="column-columnname "><?php echo $order->get_billing_phone(); ?></td>
            </tr>
        <?php
        }
        ?>
        </tbody>
      </table>

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

// -----------------------------------------
// CSV EXPORT
// -----------------------------------------

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
    
    // -----------------------------------------
    // -----------------------------------------

    ob_start();

    $domain = $_SERVER['SERVER_NAME'];

    // -----------------------------------------
    // CSV EXPORT
    // -----------------------------------------
    
    /*
    $filename = 'ordini-' . $domain . '-' . time() . '.csv';
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
    */

    // -----------------------------------------
    // TXT EXPORT
    // -----------------------------------------

    $filename = 'ordini-' . $domain . '-' . time() . '.txt';
    $content = '';

    // get latest 1 order
    $args = array( 
      'limit' => 1, 
    );
    $orders = wc_get_orders( $args );

    // orders foreach
    foreach( $orders as $order ){
      $content .= 'RIFERIMENTI-MITTENTE ' . PHP_EOL;
        $content .= 'TIPO-MITTENTE ' . '0' . PHP_EOL;     // * Imporre fisso a [0]: Cliente Mittente
        $content .= 'RIFERIMENTI-MITTENTE ' . PHP_EOL;    // * Riferimenti liberi del Cliente Mittente
      $content .= 'CAMPI-DESTINATARIO ' . PHP_EOL;
        $content .= 'NOME-DESTINATARIO ' . $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() . PHP_EOL; // *
        $content .= 'INDIRIZZO-DESTINATARIO ' . $order->get_billing_address_1() . PHP_EOL;  // * Indirizzo di consegna della spedizione
        $content .= 'AVVIAMENTO-POSTALE ' . $order->get_billing_postcode() . PHP_EOL;       // C.A.P . della Località di destinazione
        $content .= 'LOCALITA-DESTINATARIO ' . $order->get_billing_city() . PHP_EOL;        // * Località di destinazione della merce
        $content .= 'PROVINCIA-DESTINATARIO ' . $order->get_billing_state() . PHP_EOL;      // * Provincia o Distretto di destinazione
        $content .= 'STATO-ESTERO ' . PHP_EOL;                                              // * Sigla internazionale dello Stato Estero
      $content .= 'DATI-SPEDIZIONE ' . PHP_EOL;
        $content .= 'TIPO-PORTO ' . 'F' . PHP_EOL;                        // * Porto [F]:Franco o Porto [A]:Assegnato > TODO
        $content .= 'NUMERO-COLLI ' . $order->get_item_count() . PHP_EOL; // * Numero globale dei Colli della Spedizione
        $content .= 'PESO-EFFETTIVO ' . PHP_EOL;                          // * Peso reale della merce espresso in Chili > TODO
        $content .= 'METRI-CUBI ' . PHP_EOL;                              // Metri Cubi rilevati sulla spedizione
        $content .= 'VALORE-MERCE ' . $order->get_total() . PHP_EOL;      // Valore della merce espressa in Euro €
      $content .= 'CONTRASSEGNO ' . PHP_EOL;
        $content .= 'IMPORTO-ASSEGNO ' . PHP_EOL;         // Importo dell’eventuale Contrassegno
        $content .= 'PROVVIGIONE-ASSEGNO ' . PHP_EOL;     // A carico [M]ittente o [D]estinatario
      $content .= 'CAMPI-DIVERSI ' . PHP_EOL;
        $content .= 'CORRIERE-PRESCRITTO ' . PHP_EOL;     // Prescrizione obbligatoria del Corriere d'inoltro
        $content .= 'DESCRIZIONE-MERCE ' . PHP_EOL;       // * Descrizione della natura della merce > TODO
        $content .= 'TIPO-SERVIZIO ' . PHP_EOL;           // * [0]: Normale [1] Espresso > TODO
      $content .= 'ANNOTAZIONI-MITTENTE ' . PHP_EOL;
        $content .= 'DISPOSIZIONI-MITTENTE ' . PHP_EOL;   // Annotazioni da riportare in Bolla
        $content .= 'CONSEGNA-TASSATIVA ' . PHP_EOL;      // Data di consegna Tassativa per il Vettore
        $content .= 'MARCA-INIZIALE ' . PHP_EOL;          // Primo codice di marcatura del Mittente
        $content .= 'MARCA-FINALE ' . PHP_EOL;            // Ultimo codice di marcatura del Mittente
        $content .= 'RAGGRUPPAMENTO ' . PHP_EOL;          // Chiave di raggruppamento delle bolle
      $content .= 'PRESCRIZIONI-MITTENTE ' . PHP_EOL;
        $content .= 'PREAVVISO-TELEFONICO ' . PHP_EOL;                          // [*] - E’ richiesto il preavviso telefonico
        $content .= 'NUMERO-TELEFONO ' . $order->get_billing_phone() . PHP_EOL; // N. Telefono da utilizzare per il preavviso
        $content .= 'SPONDA-IDRAULICA ' . PHP_EOL;                              // [*] - E’ richiesto l’utilizzo di Sponda idraulica
        $content .= 'CENTRO-STORICO ' . PHP_EOL;                                // [*] - Consegna da effettuare in Centro Storico
        $content .= 'GRANDE-DISTRIBUZIONE ' . PHP_EOL;                          // [*] - Consegna da effettuare presso una GDO
        $content .= 'PORTO-DOGANA ' . PHP_EOL;                                  // [*] - Consegna da effettuare in area doganale
        $content .= 'MERCE-LUNGA ' . PHP_EOL;                                   // Lunghezza della merce espressa in Centimetri
        $content .= 'CONSEGNA-AL-PIANO ' . PHP_EOL;                             // [*] - E’ prevista la consegna al piano
        $content .= 'GIORNO-CHIUSURA ' . PHP_EOL;                               // Giorno di chiusura: [1]:Lunedì ... [7]:Domenica
        $content .= 'MODALITA-CHIUSURA ' . PHP_EOL;                             // [M]attino - [P]omeriggio - [T]utto il giorno
      $content .= 'CAMPI-DISPONIBILI ' . PHP_EOL;
        $content .= 'STATO-PARTITA-IVA ' . PHP_EOL;
        $content .= 'PARTITA-IVA-DESTINATARIO ' . PHP_EOL;
        $content .= 'BARCODE-DDT-MITTENTE ' . PHP_EOL;
        $content .= 'NUMERO-BANCALI ' . PHP_EOL;
        $content .= 'TIPO-BANCALI ' . PHP_EOL;
        $content .= 'PERSONALIZZAZIONI ' . PHP_EOL;
      
      /*
      $content .= $order->get_billing_email();
      */
      error_log('Ordine > '.$content);
    }
    
    //$fh = fopen("myText.txt","wb");
    $fh = @fopen( 'php://output', 'w' );
    fprintf( $fh, chr(0xEF) . chr(0xBB) . chr(0xBF) );
    header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
    header( 'Content-Description: File Transfer' );
    header( 'Content-type: text/plain' );
    header( "Content-Disposition: attachment; filename={$filename}" );
    header( 'Expires: 0' );
    header( 'Pragma: public' );
    fwrite($fh,$content);
    fclose($fh);

    // -----------------------------------------
    // -----------------------------------------
    
    ob_end_flush();
    
    die();
}

// -----------------------------------------
// INIT PLUGIN
// -----------------------------------------

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
