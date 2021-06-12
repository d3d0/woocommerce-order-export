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
// SPEDIZIONE
// -----------------------------------------

// gratis sopra i 100 €
// gratis sopra i 50 kg

// -----------------------------------------------------
// WooCommerce total order weight column on orders page 
// https://gist.github.com/kloon/5299119
// -----------------------------------------------------

add_filter( 'manage_edit-shop_order_columns', 'woo_order_weight_column' );
function woo_order_weight_column( $columns ) {
  $columns['total_weight'] = __( 'Weight', 'woocommerce' );
	return $columns;
}

add_action( 'manage_shop_order_posts_custom_column', 'woo_custom_order_weight_column', 2 );
function woo_custom_order_weight_column( $column ) {
	global $post, $woocommerce, $the_order;

	if ( empty( $the_order ) || $the_order->get_id() !== $post->ID )
		$the_order = new WC_Order( $post->ID );

	if ( $column == 'total_weight' ) {
		$weight = 0;
		if ( sizeof( $the_order->get_items() ) > 0 ) {
			foreach( $the_order->get_items() as $item ) {
				if ( $item['product_id'] > 0 ) {
					$_product = $item->get_product();
					if ( ! $_product->is_virtual() ) {
            if ( $_product->get_weight() ) {
              $weight += $_product->get_weight() * $item['qty'];
            }
					}
				}
			}
		}
		if ( $weight > 0 ) {
			print $weight . ' ' . esc_attr( get_option('woocommerce_weight_unit' ) );
		} else {
			print 'N/A';
		}
	}
}

// -----------------------------------------
// SEND MAIL FUNCTION
// -----------------------------------------

// @email   - Email address of the receiver
// @subject - Subject of the email
// @heading - Heading to place inside of the woocommerce template
// @message - Body content (can be HTML)
function send_email_woocommerce_style($email, $subject, $heading, $message, $order_id) {

  error_log( "### Invio mail con allegato > $order_id", 0 );
  
  // Get file attachments
  $attachments = array( WP_CONTENT_DIR . '/plugins/woocommerce-order-export/tracciati/'. $order_id .'txt' );
  $attachments = array( WP_CONTENT_DIR . '/debug.log' );
  // error_log( "### Allegato > $attachments", 0 );
  
  // logistica@combitras.com
  $headers = array(
    'Content-Type: text/html; charset=UTF-8',
    'From: Premiata Officina Lugaresi <info@premiataofficinalugaresi.com>'
  );
  $headers = array('Content-Type: text/html; charset=UTF-8');
  
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
// ORDER STATUS COMPLETE > OK!
// https://squelchdesign.com/web-design-newbury/woocommerce-detecting-order-complete-on-order-completion/
// -----------------------------------------

add_action( 'woocommerce_order_status_completed', 'mysite_woocommerce_order_status_completed', 10, 1 );
function mysite_woocommerce_order_status_completed( $order_id ) {

    error_log( "### Stato ordine completato > $order_id", 0 );
    
    // -----------------------------------------
    // INVIO MAIL A COMBITRAS
    // -----------------------------------------
    $order = new WC_Order( $order_id );

    $messaggio = '';
    
    // mittente
    $messaggio .= '<strong>Mittente: </strong>' . PHP_EOL . PHP_EOL;
    $messaggio .= 'Primo Spirits di Federico Lugaresi' . PHP_EOL . PHP_EOL;
    $messaggio .= '<hr>';

    // destinatario
    $messaggio .= '<strong>Destinatario: </strong>' . PHP_EOL;

    // shipping
    $messaggio .= $order->get_shipping_first_name(). ' ' .$order->get_shipping_last_name() . PHP_EOL;
    $messaggio .= $order->get_shipping_company() . PHP_EOL;
    $messaggio .= $order->get_shipping_address_1() . PHP_EOL;
    if ($order->get_shipping_address_2() != '')  $messaggio .= $order->get_shipping_address_2() . PHP_EOL;
    $messaggio .= $order->get_shipping_postcode() . ' ' . $order->get_shipping_city() . ' ' . $order->get_shipping_state() . PHP_EOL;
    $messaggio .= $order->get_shipping_country() . PHP_EOL . PHP_EOL;

    // billing
    if ($order->get_formatted_shipping_address() == NULL) {
      $messaggio .= $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() . PHP_EOL;
      $messaggio .= $order->get_billing_company() . PHP_EOL;
      $messaggio .= $order->get_billing_address_1() . PHP_EOL;
      if ($order->get_billing_address_2() != '') { $messaggio .= $order->get_billing_address_2() . PHP_EOL; }
      $messaggio .= $order->get_billing_postcode() . ' ' . $order->get_billing_city() . ' ' . $order->get_billing_state() . PHP_EOL;
      $messaggio .= $order->get_billing_country() . PHP_EOL . PHP_EOL;
    }
    $messaggio .= '<hr>';

    // lista prodotti
    $messaggio .= '<strong>Lista prodotti: </strong>' . PHP_EOL . PHP_EOL;
    if ( sizeof( $order->get_items() ) > 0 ) {
			foreach( $order->get_items() as $item ) {
				if ( $item['product_id'] > 0 ) {
					$_product = $item->get_product();
					if ( ! $_product->is_virtual() ) {
            if ( $_product->get_name() ) {
              $prodottoNome = $_product->get_name();
            }
            $messaggio .= $item->get_name() . ' | '; // Get the item name (product name)
            $messaggio .= 'Quantità: ' . $item->get_quantity() . PHP_EOL; // Get the item quantity
					}
				}
			}
    }

    // ID ordine
    $ordine = 'Nuovo Ordine Primo Spirits - ID ' . $order_id;

    // invio mail
    error_log( "### Invio mail > $order_id", 0 );
    send_email_woocommerce_style('info@premiataofficinalugaresi.com', $ordine, $ordine, $messaggio, $order_id);
}

// -----------------------------------------
// THANK YOU > OK!
// -----------------------------------------

add_action('woocommerce_thankyou', 'enroll_student', 10, 1);
function enroll_student( $order_id ) {
    
    error_log('### Thank you page ordine > '.$order_id);

    // -----------------------------------------
    // CREO IL TRACCIATO E LO SALVO
    // -----------------------------------------
    csv_txt_save($order_id);

    /*
      if ( ! $order_id )
          return;
      
      // Getting an instance of the order object
      $order = wc_get_order( $order_id );

      if($order->is_paid())
          $paid = 'yes';
      else
          $paid = 'no';

      // iterating through each order items (getting product ID and the product object / work for simple and variable products)
      foreach ( $order->get_items() as $item_id => $item ) {

          if( $item['variation_id'] > 0 ){
              $product_id = $item['variation_id']; // variable product
          } else {
              $product_id = $item['product_id']; // simple product
          }

          // Get the product object
          $product = wc_get_product( $product_id );

      }

      // Ouptput some data
      echo '<p>Order ID: '. $order_id . ' — Order Status: ' . $order->get_status() . ' — Order is paid: ' . $paid . '</p>';
    */
}

// -----------------------------------------
// PRE PAYMENT COMPLETE > TEST ???
// fired before the order is saved
// -----------------------------------------

add_action('woocommerce_pre_payment_complete', 'pre_payment_complete', 10, 1);

function pre_payment_complete() {
    error_log('### Pre pagamento completato > '.$order_id);
    //send_email_woocommerce_style('lorenzo.dedonato@gmail.com', 'Pagamento Completato POL', 'Testata', 'Messaggio');
}

// -----------------------------------------
// PAYMENT COMPLETE > TEST ???
// fired when the payment is completed
// -----------------------------------------

add_action('woocommerce_payment_complete', 'payment_complete', 10, 1);

function payment_complete() {
    error_log('### Pagamento completato > '.$order_id);
    //send_email_woocommerce_style('lorenzo.dedonato@gmail.com', 'Pagamento Completato POL', 'Testata', 'Messaggio');
}

// -----------------------------------------
// CREATE ORDER > OK!
// -----------------------------------------

add_action('woocommerce_new_order', 'create_order_and_send_email', 10, 1);

function create_order_and_send_email ($order_id) {
    error_log('### Ordine creato > '.$order_id);
    //csv_txt_export(); // NO > genera errore ajax!!!
    //send_email_woocommerce_style('lorenzo.dedonato@gmail.com', 'Nuovo Ordine POL', 'Testata', 'Messaggio');
}

// -----------------------------------------
// CSV TXT SAVE > OK!
// -----------------------------------------

function calcola_stringa($stringa, $lunghezza) {
    $stringLength = strlen($stringa); // calcolo lunghezza stringa
    $stringaCalcolataTemp = $stringa; // inizializzo string
    $stringaCalcolata = substr($stringaCalcolataTemp, 0, $lunghezza); ; // imposto lunghezza MAX CAMPO
    $stringaCalcolata .= str_repeat(' ', $lunghezza - $stringLength); // aggiungo spazi in base a lunghezza MAX CAMPO
    return $stringaCalcolata;
}
function calcola_numero($numero, $lunghezza, $decimali) {
    $numWhole = floor($numero); // numero intero
    error_log('numero intero ----------'.$numWhole);

    // calcolo frazione
    $numFractionTemp = $numero - $numWhole; // .00
    $numFractionTemp = round($numFractionTemp,2,PHP_ROUND_HALF_UP); // arrotondo al secondo decimale per eccesso
    error_log('frazione inizio'.$numFractionTemp);
    $numFractionTemp = str_replace("0.","",$numFractionTemp); // pulisco il zero punto (0.) dai decimali
    error_log('frazione temp'.$numFractionTemp);
    $numFraction = str_replace(".","",$numFractionTemp); // pulisco il punto dai decimali
    error_log('frazione temp'.$numFraction);
    if($numFraction == 0 && $decimali > 1) $numFraction = '00'; // se è 0 e ha 2 decimali => 00
    if(strlen($numFraction) == 1 && $decimali > 1) $numFraction = strval($numFraction) . '0'; // se lunghezza frazione == 1 e ha 2 decimali => 00
    error_log('frazione fine'.$numFraction);

    // calcolo intero
    $numLength = strlen($numWhole); // calcolo lunghezza numero
    $numZeros = str_repeat('0', $lunghezza - $numLength); // calcolo zeri da aggiungere
    $numeroCalcTemp = substr_replace($numWhole,$numZeros,0,0); // inizializzo numero e aggiungo ZERI in base a lunghezza campo
    $numeroCalc = substr($numeroCalcTemp, 0, $lunghezza + $decimali); ; // imposto lunghezza MAX CAMPO CON DECIMALI
    
    // calcolo numero
    if($decimali > 0) $numeroCalc .= str_repeat(''.$numFraction.'', 1); // aggiungo decimali in base a lunghezza MAX CAMPO
    error_log('numero calcolato ----------'.$numeroCalc);

    return $numeroCalc;
}

function csv_txt_save($order_id) {

    error_log('Inizio esportazione ordine!');

    // -----------------------------------------
    // -----------------------------------------

    $domain = $_SERVER['SERVER_NAME'];

    // -----------------------------------------
    // TXT EXPORT
    // -----------------------------------------

    //$filename = 'ordini-' . $domain . '-' . time() . '.txt';
    $filename = 'tracciati/'.$order_id . '.txt';
    $content = '';

    // get latest 1 order
    $args = array( 
      'limit' => 1, 
    );
    $orders = wc_get_orders( $args );

    // orders foreach
    foreach( $orders as $order ){

      // calcolo peso colli
      $weight = 0;
      if ( sizeof( $order->get_items() ) > 0 ) {
        foreach( $order->get_items() as $item ) {
          if ( $item['product_id'] > 0 ) {
            $_product = $item->get_product();
            if ( ! $_product->is_virtual() ) {
              if ( $_product->get_weight() ) {
                $weight += $_product->get_weight() * $item['qty'];

                // COLLI FINITI
                // - box primo amore > 4,47 kg
                // - bianchina da 24 > 9,25 kg
                // - barattolo di latta > 12 = 1,0 kg / 6 = 0,74 kg
                // - 6 bottiglie uguali (stesso prodotto) > 8 kg

                // BOTTIGLIE
                // il peso va calcolato solo se vengono acquistate le bottiglie
                // peso di una bottiglia = ...
                // peso di una latta = 1,2 kg
                // media > 1,2 + 0,23 + 0,3 = 1,75 kg MEDIA PESO FINITO DI UNA BOTTIGLIA
                // media > ... + 0,23 + 0,3 = .... kg MEDIA PESO FINITO DI UNA LATTA
                // 1 bottiglia > 0,304 + 0,293 kg = 0,6 kg
                // 2 bottiglie > 0,414 + (2 x 0,293) kg = 1,414 kg
                // 3 bottiglie > 0,554 + (3 x 0.293) kg = 1,433 kg  

                // 1 bottiglia > 1
                // 2, 4 bottiglie > 2
                // 3,6,9 bottiglie > 3
                // 5 bottiglie > 2 + 3
                // 7 bottiglie > 2 da 3 + 1
                // 8 bottiglie > 2 da 3 + 2
                // 10 bottiglie > 2 da 3 + 2 da 2

                // if( $_product->get_name() == 'Gin Primo') {
                //  $weight += 0.304;
                //  if(sizeof( $order->get_items() % 2 != 0) {}
                //  if(sizeof( $order->get_items() % 3 != 0) {}
                // }
                // if( $_product->get_name() == 'Barattolo di latta') {
                //   $weight += 0.352;
                // }

              }
            }
          }
        }
      }

      // generazione txt per tracciato
      $content1 = '';
      $content1 .= '0';                                     // * Imporre fisso a [0]: Cliente Mittente
      $content1 .= str_repeat('0', 16);                     // * Riferimenti liberi del Cliente Mittente
          $nomeTemp = $order->get_billing_first_name(). ' ' .$order->get_billing_last_name(); // * Nome
          $nome = calcola_stringa($nomeTemp, 30);
      $content1 .= $nome; // *
          $indirizzoTemp = $order->get_billing_address_1();   // * Indirizzo di consegna della spedizione
          $indirizzo = calcola_stringa($indirizzoTemp, 30);
      $content1 .= $indirizzo; // *
          $capTemp = $order->get_billing_postcode();          // C.A.P . della Località di destinazione
          $cap = calcola_stringa($capTemp, 5);
      $content1 .= $cap;
          $localitaTemp = $order->get_billing_city();         // * Località di destinazione della merce
          $localita = calcola_stringa($localitaTemp, 20); 
      $content1 .= $localita; // *
          $provinciaTemp = $order->get_billing_state();       // * Provincia o Distretto di destinazione
          $provincia = calcola_stringa($provinciaTemp, 4); 
      $content1 .= $provincia; // *
          $statoTemp = ' ';                                   // * Sigla internazionale dello Stato Estero
          $stato = calcola_stringa($statoTemp, 3); 
      $content1 .= $stato; // *
          $portoTemp = 'F';                                   // * Porto [F]:Franco o Porto [A]:Assegnato > TODO
          $porto = calcola_stringa($portoTemp, 1); 
      $content1 .= $porto; // *
          $colliTemp = $order->get_item_count();              // * Numero globale dei Colli della Spedizione
          $colli = calcola_numero($colliTemp, 5, 0); 
      $content1 .= $colli; // *
          $pesoTemp = $weight;                                // * Peso reale della merce espresso in Chili > TODO
          $peso = calcola_numero($pesoTemp, 6, 1); 
      $content1 .= $peso; // *
      $content1 .= '00000';                                   // Metri Cubi rilevati sulla spedizione
          $valoreTemp = $order->get_total();                  // Valore della merce espressa in Euro €
          $valore = calcola_numero($valoreTemp, 9, 2); 
      $content1 .= $valore; // *
          $importoAssegno = calcola_numero('0', 9, 2);        // Importo dell’eventuale Contrassegno > 11
      $content1 .= $importoAssegno;                            
      $content1 .= 'M';                                       // A carico [M]ittente o [D]estinatario > 1 > ???
          $prescrizione = calcola_stringa('', 30);            // Prescrizione obbligatoria del Corriere d'inoltro > 30
      $content1 .= $prescrizione;
          $naturaMerce = calcola_stringa('', 16);             // * Descrizione della natura della merce > 16
      $content1 .= $naturaMerce; // *                  
      $content1 .= '0'; // *                                  // * Tipo servizio [0]: Normale [1] Espresso > 1
          $disposizioni = calcola_stringa('', 30);            // Dispos. Mittente > Annotazioni da riportare in Bolla > 30
      $content1 .= $disposizioni;
          $consegnaTass = calcola_numero('0', 8, 0);          // Data di consegna Tassativa per il Vettore > 8
      $content1 .= $consegnaTass;
          $marcaIniziale = calcola_numero('0', 7, 0);         // Primo codice di marcatura del Mittente > 7
      $content1 .= $marcaIniziale;
          $marcaFinale = calcola_numero('0', 7, 0);           // Ultimo codice di marcatura del Mittente > 7
      $content1 .= $marcaFinale;
          $raggruppamento = calcola_stringa('', 30);          // Chiave di raggruppamento delle bolle > 30
      $content1 .= $raggruppamento;



      // generazione txt normale
      $content .= 'RIFERIMENTI-MITTENTE ';
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

      // LOG FILE DI TESTO
      // error_log('Ordine > '.$content);
    }

    // error_log('Informazioni ordine salvate!');

    // -----------------------------------------
    // scrittura in cartella plugin
    // -----------------------------------------
    
    $file = plugin_dir_path( __FILE__ ) . '/' . $filename; 
    $fp = fopen( $file, "wb" ); 
    if( $fp == false ){
        error_log('Errore creazione file!');
    }
    else{
        error_log('File testo scritto con successo!');
        //fwrite($fp,$content);
        fwrite($fp,$content1);
        fflush($fp);
        fclose($fp);
    }

    // -----------------------------------------
    // -----------------------------------------
    
    error_log('Fine esportazione ordine!');
}

// -----------------------------------------
// CSV TXT EXPORT
// -----------------------------------------

if ( isset($_GET['action'] ) && $_GET['action'] == 'download_csv' )  {
	add_action( 'admin_init', 'csv_txt_export');
}

function csv_txt_export() {
    error_log('Esportazione ordine!');

    // Check for current user privileges 
    //if( !current_user_can( 'manage_options' ) ){ return false; }

    // Check if we are in WP-Admin
    //if( !is_admin() ){ return false; }

    // Nonce Check
    // $nonce = isset( $_GET['_wpnonce'] ) ? $_GET['_wpnonce'] : '';
    // if ( ! wp_verify_nonce( $nonce, 'download_csv' ) ) {
    //     die( 'Security check error' );
    // }

    // -----------------------------------------
    // -----------------------------------------

    $domain = $_SERVER['SERVER_NAME'];

    // -----------------------------------------
    // CSV EXPORT
    // -----------------------------------------
    
    /* 
    ob_start();
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
    ob_end_flush();
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

    error_log('Informazioni ordine salvate!');
    
    // -----------------------------------------
    // download
    // -----------------------------------------

    /*
    ob_start();
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
    ob_end_flush();
    */

    // -----------------------------------------
    // scrittura in cartella plugin
    // -----------------------------------------

    
    $file = plugin_dir_path( __FILE__ ) . '/' . $filename; 
    $fp = fopen( $file, "wb" ); 
    if( $fp == false ){
        error_log('Errore creazione file!');
    }
    else{
        error_log('Scrittura file testo!');
        fwrite($fp,$content);
        fflush($fp);
        fclose($fp);
    }

    // -----------------------------------------
    // -----------------------------------------
    
    error_log('Fine export!');

    //die();
    
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
      <!--
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
      -->
  </div>
  <?php
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
