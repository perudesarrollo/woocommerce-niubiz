<?php
/*
Plugin Name: Pagos Niubiz
Plugin URI: #
Description: Módulo para pagos en línea mediante Visa.
Version: 1.2
Author: Visa
Author URI: https://github.com/perudesarrollo/woocommerce-niubiz
 */

define('VISA_API_SESSION_DEV', 'https://apitestenv.vnforapps.com/api.ecommerce/v2/ecommerce/token/session/%s');
define('VISA_API_SESSION', 'https://apiprod.vnforapps.com/api.ecommerce/v2/ecommerce/token/session/%s');
define('VISA_STATIC_JS_CHECKOUT_DEV', 'https://static-content-qas.vnforapps.com/v2/js/checkout.js?qa=true');
define('VISA_STATIC_JS_CHECKOUT', 'https://static-content.vnforapps.com/v2/js/checkout.js');
define('VISA_API_TOKENIZATION', 'https://%s.vnforapps.com/api.tokenization/api/v2/merchant/%d/query/%d');
define('VISA_API_SECURITY', 'https://%s.vnforapps.com/api.security/v1/security');
define('VISA_API_AUTHORIZATION', 'https://%s.vnforapps.com/api.authorization/v3/authorization/ecommerce/%d');
define('VISA_API_AUTHORIZATION_RETRIVE', 'https://%s.vnforapps.com/api.authorization/v3/retrieve/purchase/%d/%d');

include 'qas/librerias/funciones.php';
add_action('plugins_loaded', 'woocommerce_niubiz_init', 0);

function woocommerce_niubiz_init()
{
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    class WC_Niubiz extends WC_Payment_Gateway
    {
        public function __construct()
        {
            $this->id = 'visanet';
            $this->icon = plugins_url('/images/' . ($this->get_option('iconimage') ? $this->get_option('iconimage') : 'visa.png'), __FILE__);
            $this->medthod_title = 'Visa';
            $this->has_fields = false;

            $this->init_form_fields();
            $this->init_settings();

            $this->title = $this->settings['title'];
            $this->merchant_id = $this->settings['merchant_id'];
            $this->accesskey = $this->settings['accesskey'];
            $this->secretkey = $this->settings['secretkey'];

            $this->merchant_id_en = $this->settings['merchant_id_en'];
            $this->accesskey_en = $this->settings['accesskey_en'];
            $this->secretkey_en = $this->settings['secretkey_en'];

            $this->ambiente = $this->settings['ambiente'];
            $this->url_logo = $this->settings['url_logo'];
            $this->url_tyc = $this->settings['url_tyc'];
            $this->url_to = $this->settings['url_to'];

            $this->msg['message'] = "";
            $this->msg['class'] = "";

            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
            //add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'save_account_details' ) );
            add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
            //

        }

        /** INCIO DE FORMULARIO DE CONFIGURACION */

        function init_form_fields()
        {

            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Activado/Desactivado', 'fabro'),
                    'type' => 'checkbox',
                    'label' => __('Activar Módulo.', 'fabro'),
                    'default' => 'no'),
                'title' => array(
                    'title' => __('Título:', 'fabro'),
                    'type' => 'text',
                    'description' => __('Título que verán los usuarios.', 'fabro'),
                    'default' => __('Visa', 'fabro')),
                'multicomercio' => array(
                    'title' => __('Modo Multicomercio', 'fabro'),
                    'type' => 'checkbox',
                    'label' => __('Activar modo Multicomercio.', 'fabro'),
                    'default' => 'no'),
                'recurrence' => array(
                    'title' => __('Pago Recurrente', 'fabro'),
                    'type' => 'checkbox',
                    'label' => __('Activar pagos recurrentes.', 'fabro'),
                    'default' => 'no'),
                'recurrencetype' => array(
                    'title' => __('Tipo de recurrencia', 'fabro'),
                    'type' => 'select',
                    'options' => array("FIXED" => "FIJO", "VARIABLE" => "VARIABLE", "FIXEDINITIAL" => "INICIAL FIJA", "VARIABLEINITIAL" => "VARIABLE INICIAL"),
                    'description' => __('Indica el tipo de recurrencia a mostrar en el formulario de pagos'),
                ),
                'recurrencefrequency' => array(
                    'title' => __('Frecuencia', 'fabro'),
                    'type' => 'select',
                    'options' => array("MONTHLY" => "MENSUAL", "QUARTERLY" => "TRIMESTRAL", "BIANNUAL" => "SEMESTRAL", "ANNUAL" => "ANUAL"),
                    'description' => __('Indica la frecuencia para pagos recurrentes.'),
                ),
                'recurrencemaxamount' => array(
                    'title' => __('Importe máximo como recurrente', 'fabro'),
                    'type' => 'text',
                    'description' => __('Importe máximo a cargar como pago recurrente'),
                ),
                'recurrenceamount' => array(
                    'title' => __('Monto a pagar en recurrente', 'fabro'),
                    'type' => 'text',
                    'description' => __(' Aplica cuando el Tipo de recurrencia es FIXED y FIXEDINITIAL'),
                ),
                'ambiente' => array(
                    'title' => __('Ambiente'),
                    'type' => 'select',
                    'options' => array("dev" => "Desarrollo", "prd" => "Produccion"),
                    'description' => "Ambiente (Desarrollo/Producción).",
                ),
                'merchant_id' => array(
                    'title' => __('Merchant ID Soles', 'fabro'),
                    'type' => 'text',
                    'description' => __('ID de Comercio Soles'),
                ),
                'accesskey' => array(
                    'title' => __('Usuario ID Soles', 'fabro'),
                    'type' => 'text',
                    'description' => __('Usuario proporcionado por Visanet.', 'fabro'),
                ),
                'secretkey' => array(
                    'title' => __('Contraseña del ID Soles', 'fabro'),
                    'type' => 'text',
                    'description' => __('Contraseña del ID proporcionada por Visanet.'),
                ),

                /* DOLARES */

                'merchant_id_en' => array(
                    'title' => __('Merchant ID Dólares', 'fabro'),
                    'type' => 'text',
                    'description' => __('ID de Comercio Dólares'),
                ),
                'accesskey_en' => array(
                    'title' => __('Usuario ID Dólares', 'fabro'),
                    'type' => 'text',
                    'description' => __('Usuario proporcionado por Visanet.', 'fabro'),
                ),
                'secretkey_en' => array(
                    'title' => __('Contraseña del ID Dólares', 'fabro'),
                    'type' => 'text',
                    'description' => __('Contraseña del ID proporcionada por Visanet.'),
                ),

                /*FIN DOLARES */

                'url_logo' => array(
                    'title' => __('URL de Logo', 'fabro'),
                    'type' => 'text',
                    'description' => __('URL de la imagen de su logo.', 'fabro'),
                ),
                'url_tyc' => array(
                    'title' => __('URL de Terminos y Condiciones', 'fabro'),
                    'type' => 'text',
                    'description' => __('URL de Terminos y Condiciones', 'fabro'),
                ),
                'url_to' => array(
                    'title' => __('URL de TimeOut', 'fabro'),
                    'type' => 'text',
                    'description' => __('URL de la pagina donde se redirigirá cuando haya vencido el formulario', 'fabro'),
                ),
            );
        }

        public function admin_options()
        {
            echo '<h3>' . __('Visa', 'fabro') . '</h3>';
            echo '<p>' . __('Visanet Perú permite realizar pagos con tarjeta Visa.') . '</p>';
            echo '<table class="form-table">';
            $this->generate_settings_html();
            echo '</table>';

        }

        function payment_fields()
        {
            if ($this->description) {
                echo wpautop(wptexturize($this->description));
            }

        }

        function receipt_page($order)
        {
            echo '<p>' . __('Haga click en el botón para realizar su pago mediante Visanet Perú.', 'fabro') . '</p>';
            echo $this->generate_visanet_form($order);
        }

        /** GENERAR BOTON DE PAGO **/

        public function generate_visanet_form($order_id)
        {

            global $woocommerce, $product;
            $current_user = wp_get_current_user();
            $order = new WC_Order($order_id);
            $order_data = (object) $order->get_data();
            $txnid = $order_id . '_' . date("ymds");
            $amount = $order_data->total;
            $productinfo = "Order $order_id";
            $sessionToken = get_guid();

            $moneda = get_post_meta($order_id, "_order_currency", true);
            $vars = get_option("woocommerce_visanet_settings");
            $order_items = $order->get_items();

            if ($vars['recurrence'] == "yes") {
                $data_recurrence = "TRUE";
                $data_recurrenceamount = $vars['recurrencemaxamount'];
                $data_recurrencetype = $vars['recurrencetype'];
                $data_recurrenceamount = $vars['recurrenceamount'];
                $data_recurrencefrequency = $vars['recurrencefrequency'];
            } else {
                $data_recurrence = "FALSE";
                $data_recurrenceamount = $vars['recurrencemaxamount'];
                $data_recurrencetype = $vars['recurrencetype'];
                $data_recurrenceamount = $vars['recurrenceamount'];
                $data_recurrencefrequency = $vars['recurrencefrequency'];
            }

            if ($moneda == "USD") {
                if ($vars['multicomercio'] == "yes") {
                    foreach ($order_items as $item) {
                        $product_id = $item['product_id'];
                    }

                    $merid = get_post_meta($product_id, '_codigo_comercio_dolares', true);
                    $accessk = get_post_meta($product_id, '_access_key_dolares', true);
                    $seckey = get_post_meta($product_id, '_secret_key_dolares', true);
                    $logopago = get_post_meta($product_id, '_img_logoprd', true);
                } else {
                    $merid = $this->merchant_id_en;
                    $accessk = $this->accesskey_en;
                    $seckey = $this->secretkey_en;
                    $logopago = $this->url_logo;
                }

            } else {
                if ($vars['multicomercio'] == "yes") {
                    foreach ($order_items as $item) {
                        $product_id = $item['product_id'];
                    }
                    $merid = get_post_meta($product_id, '_codigo_comercio_soles', true);
                    $accessk = get_post_meta($product_id, '_access_key_soles', true);
                    $seckey = get_post_meta($product_id, '_secret_key_soles', true);
                    $logopago = get_post_meta($product_id, '_img_logoprd', true);
                } else {
                    $merid = $this->merchant_id;
                    $accessk = $this->accesskey;
                    $seckey = $this->secretkey;
                    $logopago = $this->url_logo;
                }
            }

            $key = generateToken($this->ambiente, $accessk, $seckey);
            error_log("Visa::Securitykey::Key:: {$key}");

            // Capturamos IP del Cliente
            $sessionBody = [
                "amount" => $amount,
                "clientIp" => $order_data->customer_ip_address,
                'email' => $current_user->user_email,
                'type_document' => 'DNI',//@$order->get_meta('tipo_dni'),
                'number_document' => '43526502',//@$order->get_meta('billing_dni'),
                'order_id' => $order_id,
                'date_register' => date("Y-m-d H:i:s"),
            ];
            $sessionKey = generateSesion($this->ambiente, $merid, $key, $sessionBody);
            error_log("Visa::create_token::SessionKey::: {$sessionKey}");

            update_post_meta($order_id, '_sessionKey', $sessionKey);
            update_post_meta($order_id, '_order_key', $key);
            $entorno = $this->ambiente;
            $arrayPost = ["sessionToken" => $sessionToken, "merchantId" => $merid, "entorno" => $entorno, "amount" => $amount, "key" => $key];

            $arrayPost_json = json_encode($arrayPost);
            error_log("Visa::arrayPost:: {$arrayPost_json}");

            update_post_meta($order_id, '_sessionToken', $sessionToken);
            update_post_meta($order_id, '_visanetLang', get_language());

            /* ENCRIPTAMOS ID DE ORDEN PARA PASARLO POR URL DE MANERA MAS SEGURA */
            update_post_meta($order_id, '_orderUrl', $_SERVER["REQUEST_URI"]);
            $secret_key = 'fabricio_vela';
            $secret_iv = 'fabricio_vela';

            $output = false;
            $encrypt_method = "AES-256-CBC";
            $key2 = hash('sha256', $secret_key);
            $iv = substr(hash('sha256', $secret_iv), 0, 16);
            $numorden = base64_encode(openssl_encrypt($order_id, $encrypt_method, $key2, 0, $iv));
            /* FIN */

            error_log("Visa::hash::numorden:: {$numorden}");

            $retorno = home_url() . "/wp-admin/admin-ajax.php?action=visanet&hash=" . $numorden;

            $urlpost = ($this->ambiente == "dev") ? VISA_STATIC_JS_CHECKOUT_DEV : VISA_STATIC_JS_CHECKOUT;
            return "<form action=\"$retorno\" method='post'>
                    <script src=\"$urlpost\"
                        data-sessiontoken=\"$sessionKey\"
                        data-merchantid=\"$merid\"
						data-channel=\"web\"
                        data-buttonsize=\"\"
                        data-buttoncolor=\"#9E2126\"
                        data-merchantlogo =\"$logopago\"
                        data-merchantname=\"\"
                        data-formbuttoncolor=\"#9E2126\"
                        data-showamount=\"\"
                        data-purchasenumber=\"$order_id\"
                        data-amount=\"$amount\"
                        data-cardholdername=\"" . $order_data->billing['first_name'] . "\"
                        data-cardholderlastname=\"" . $order_data->billing['last_name'] . "\"
                        data-cardholderemail=\"" . $current_user->user_email . "\"
                        data-usertoken=\"" . $current_user->user_email . "\"
                        data-recurrence=\"" . $data_recurrence . "\"
                        data-recurrencefrequency=\"" . $data_recurrencefrequency . "\"
                        data-recurrencetype=\"" . $data_recurrencetype . "\"
                        data-recurrenceamount=\"" . $data_recurrenceamount . "\"
                        data-recurrencemaxamount=\"" . $data_recurrenceamount . "\"
                        data-documenttype=\"0\"
                        data-documentid=\"\"
                        data-beneficiaryid=\"\"
                        data-productid=\"\"
                        data-phone=\"\"
						data-timeouturl=\"" . $this->url_to . "\"
                    /></script>
                </form>";
        }

        /** PROCESAR PAGO **/

        function process_payment($order_id)
        {
            global $woocommerce;
            $order = new WC_Order($order_id);

            return array(
                'result' => 'success',
                'redirect' => $order->get_checkout_payment_url(true),
            );
        }

        /** PAGINA DE RETORNO **/

        function thankyou_page($order_id)
        {

            $order = new WC_Order($order_id);

            $datos = get_post_meta($order_id, '_visanetRetorno', true);

            $ambiente = get_post_meta($order_id, '_order_ambiente', true);
            $key = get_post_meta($order_id, '_order_key', true);
            $merid = get_post_meta($order_id, '_order_merchantid', true);
            $accessk = get_post_meta($order_id, '_order_accessk', true);
            $seckey = get_post_meta($order_id, '_order_seckey', true);

            //die('ambiente: '.$ambiente.'<br/>key: '.$key.'<br/>merid: '.$merid.'<br/>accessk: '.$accessk.'<br/>seckey: '.$seckey);
            //$transactionToken = $_POST['transactionToken'];
            //$datos = authorization($ambiente, $key, $merid, $transactionToken,$accessk,$seckey);
            //update_post_meta($order_id, '_visanetRetorno', $datos);

            $moneda = get_post_meta($order_id, '_order_currency', true);
            $cliente = get_post_meta($order_id, '_billing_first_name', true) . " " . get_post_meta($order_id, '_billing_last_name', true);
            $sal = json_decode($datos, true);
            

            $order_data = (object) $order->get_data();
            $sessionBody = [
                "amount" => 12.35,
                "clientIp" => $order_data->customer_ip_address,
                'email' => $order_data->billing['email'],
                'type_document' => @$order->get_meta('tipo_dni'),
                'number_document' => @$order->get_meta('billing_dni'),
                'order_id' => $order_id,
                'date_register' => date("Y-m-d H:i:s"),
            ];
            echo "<pre>";   
            $url = home_url();         
            print_r($url);
            // print_r($sessionBody);
            print_r($sal);
            echo "</pre>";
            // echo $sessionKey = generateSesion($ambiente, $merid, $key, $sessionBody);

            $moneda = get_post_meta($order_id, '_order_currency', true);
            //var_dump($sal);
            if ($moneda == "PEN") {

                if (isset($sal['dataMap']['ACTION_CODE']) && $sal['dataMap']['ACTION_CODE'] == "000") {
                    echo "<b>Cliente: </b>" . $cliente . "</br>";
                    echo "<b>Fecha y Hora: </b>" . date("Y-m-d H:i:s", ($sal['header']['ecoreTransactionDate'] / 1000)) . "</br>";
                    echo "<b>Tarjeta: </b>" . $sal['dataMap']['CARD'] . "</br>";
                    echo "<b>Moneda: </b>" . $moneda . "</br>";
                } else {
                    $fecha = str_split($sal['data']['TRANSACTION_DATE'], 2);
                    echo "<b>Nro. Orden: </b>" . $order_id . "</br>";
                    echo "<b>Fecha y Hora: </b>" . $fecha[0] . "-" . $fecha[1] . "-" . $fecha[2] . " " . $fecha[3] . ":" . $fecha[4] . ":" . $fecha[5] . "</br>";
                    echo "<b>Motivo: </b>" . $sal['data']['ACTION_DESCRIPTION'] . "</br>";
                    echo "<b>Moneda: </b>" . $moneda . "</br>";

                }
                echo '<a href="' . $this->url_tyc . '" target="_blank">Ver Términos y Condiciones</a><br/>';
                echo "<input type ='button' onclick='window.print();' class='button-shop-product' value='Imprimir'>";
            } elseif ($moneda == "USD") {
                if (isset($sal['dataMap']['ACTION_CODE']) && $sal['dataMap']['ACTION_CODE'] == "000") {
                    echo "<b>Customer: </b>" . $cliente . "</br>";
                    echo "<b>Date: </b>" . date("y-m-d H:i:s", ($sal['header']['ecoreTransactionDate'] / 1000)) . "</br>";
                    echo "<b>Credit Card Number: </b>" . $sal['dataMap']['CARD'] . "</br>";
                    echo "<b>Currency: </b>" . $moneda . "</br>";
                } else {
                    echo "<b>Order ID: </b>" . $order_id . "</br>";
                    $fecha = str_split($sal['data']['TRANSACTION_DATE'], 2);
                    echo "<b>Date: </b>" . $fecha[0] . "-" . $fecha[1] . "-" . $fecha[2] . " " . $fecha[3] . ":" . $fecha[4] . ":" . $fecha[5] . "</br>";
                    echo "<b>Reason: </b>" . getMotivo($sal['data']['ACTION_CODE']) . "</br>";
                    echo "<b>Currency: </b>" . $moneda . "</br>";
                }
                echo '<a href="' . $this->url_tycen . '" target="_blank">Our Terms and Conditions</a><br/>';
                echo "<input type ='button' onclick='window.print();' class='button-shop-product' value='Print'>";
            }
        }

        function showMessage($content)
        {
            return '<div class="box ' . $this->msg['class'] . '-box">' . $this->msg['message'] . '</div>' . $content;
        }
    }
    /**
     * AGREGAMOS EL MÉTODO DE PAGO VISANET
     **/
    function woocommerce_add_visanet($methods)
    {
        $methods[] = 'WC_Niubiz';
        return $methods;
    }

    /** AGREGAMOS WP-AJAX ACTIONS PARA AUTORIZACION Y RESPUESTA DE PAGO **/

    add_action('wp_ajax_visanet', 'check_visanet_response');
    add_action('wp_ajax_nopriv_visanet', 'check_visanet_response');

    /* Autorizacion */

    add_action('wp_ajax_visanetAcciones', 'visanet_acciones');
    add_action('wp_ajax_nopriv_visanetAcciones', 'visanet_acciones');

    add_filter('woocommerce_payment_gateways', 'woocommerce_add_visanet');

    /** RETORNO VISANET Y VERIFICA SI EL PAGO FUE EXITOSO **/
    function check_visanet_response()
    {
        global $woocommerce;
        $current_user = wp_get_current_user();
        if ($_POST) {

            /* DESENCRIPTAMOS */
            $hash = $_GET['hash'];
            $secret_key = 'fabricio_vela';
            $secret_iv = 'fabricio_vela';

            $output = false;
            $encrypt_method = "AES-256-CBC";
            $key2 = hash('sha256', $secret_key);
            $iv = substr(hash('sha256', $secret_iv), 0, 16);
            $order_id = openssl_decrypt(base64_decode($hash), $encrypt_method, $key2, 0, $iv);

            $order = new WC_Order($order_id);
            $vars = get_option("woocommerce_visanet_settings");
            $transactionToken = $_POST['transactionToken'];
            //$sessionToken = recupera_sessionToken();
            $sessionToken = get_post_meta($order_id, '_sessionToken', true);
            $moneda = get_post_meta($order_id, '_order_currency', true);
            $order_items = $order->get_items();
            if ($moneda == "USD") {
                if ($vars['multicomercio'] == "yes") {
                    foreach ($order_items as $item) {
                        $product_id = $item['product_id'];
                    }

                    $merid = get_post_meta($product_id, '_codigo_comercio_dolares', true);
                    $accessk = get_post_meta($product_id, '_access_key_dolares', true);
                    $seckey = get_post_meta($product_id, '_secret_key_dolares', true);
                } else {
                    $merid = $vars['merchant_id_en'];
                    $accessk = $vars['accesskey_en'];
                    $seckey = $vars['secretkey_en'];
                }

            } else {
                if ($vars['multicomercio'] == "yes") {
                    foreach ($order_items as $item) {
                        $product_id = $item['product_id'];
                    }
                    $merid = get_post_meta($product_id, '_codigo_comercio_soles', true);
                    $accessk = get_post_meta($product_id, '_access_key_soles', true);
                    $seckey = get_post_meta($product_id, '_secret_key_soles', true);
                } else {
                    $merid = $vars['merchant_id'];
                    $accessk = $vars['accesskey'];
                    $seckey = $vars['secretkey'];
                }
            }
            $key = get_post_meta($order_id, '_order_key', true);
            $order = new WC_Order($order_id);
            $amount = $order->total;
            $respuesta = authorization($vars["ambiente"], $key, $amount, $merid, $transactionToken, $order_id, $moneda);

            //die(var_dump($respuesta));
            //die(var_dump($respuesta).'<br/>ambiente: '.$vars["ambiente"].'<br/>key: '.$key.'<br/>amount: '.$amount.'<br/>merid: '.$merid.'<br/>transactionToken: '.$transactionToken.'<br/>accessk: '.$accessk.'<br/>seckey: '.$seckey);
            $sal = json_decode($respuesta, true);
            update_post_meta($order_id, '_visanetRetorno', $respuesta);
            //var_dump($respuesta);
            if ($order) {

                $note = 'Pago via Visanet : ' . $msg_auth . "\n";
                $note .= 'Codigo Autorización: ' . $sal['order']['authorizationCode'] . "\n";
                $note .= 'Codigo Accion: ' . $sal['dataMap']['ACTION_CODE'] . "\n";
                $note .= 'Num Tarjeta: ' . $sal['dataMap']['CARD'] . "\n";

                $order->add_order_note($note);
                update_post_meta($order_id, '_order_ambiente', $vars["ambiente"]);

                update_post_meta($order_id, '_order_merchantid', $merid);
                update_post_meta($order_id, '_order_accessk', $accessk);
                update_post_meta($order_id, '_order_seckey', $seckey);

                update_user_meta($current_user->ID, '_visanet_usertoken', $sal['order']['tokenId']);

                if ($sal['dataMap']['ACTION_CODE'] == "000") { // autorizada
                    $order->update_status('completed');
                    $order->reduce_order_stock();
                    $woocommerce->cart->empty_cart();
                } else {
                    $order->update_status('failed');
                }
                $moneda = get_post_meta($order_id, '_order_currency', true);

                $url = get_post_meta($order_id, '_orderUrl', true);
                $parsear = explode('/', $url);
                $rever = array_reverse($parsear);
                $prefijo = $rever[3];
                if ($moneda == "PEN") {
                    $url = site_url('index.php/' . $prefijo . '/order-received/' . $order_id . '/?key=' . $order->order_key);
                } else {
                    $url = site_url('/' . $prefijo . '/order-received/' . $order_id . '/?key=' . $order->order_key);
                }
                //http://{website}/checkout/order-received/{purchaseOperationNumber}/?key={HTTPSessionId}

                wp_redirect($url);
                exit();
                //var_dump($sal);
            } else {
                echo 'Número de pedido no válido';
            }
        } else {
            echo 'No se recibio post.';
        }

        die();
    }

    /** SE EJECUTAN LAS ACCIONES DE DEPOSITO, CANCELACION Y ANULACION **/
    function visanet_acciones()
    {
        global $woocommerce;
        $order_id = sanitize_key($_GET['ordernumber']);
        $order = new WC_Order($order_id);
        echo true;
    }

    /** CAJA DE ACCIONES VISANET **/

    add_action('add_meta_boxes', 'MY_order_meta_boxes');
    function MY_order_meta_boxes()
    {
        add_meta_box(
            'woocommerce-order-visanetacciones',
            __('Acciones Visa'),
            'order_meta_box_visaccciones',
            'shop_order',
            'side',
            'default'
        );

    }

    function order_meta_box_visaccciones()
    {
        global $woocommerce, $post, $product;
        $dir = plugin_dir_path(__FILE__);
        $order_id = $post->ID;
        $order = new WC_Order($order_id);
        $retorno = get_post_meta($order_id, '_visanetRetorno', true);
        $vars = get_option("woocommerce_visanet_settings");
        $ambiente = ($vars['ambiente'] == "dev") ? 'devapi' : 'api';
        if ($order->has_status('completed') || $order->has_status('refunded')) {
            /* Comprobamos estado actual */
            $data = array("comment" => "");
            $data_string = json_encode($data);
            $merchant_id = get_post_meta($order_id, '_order_merchantid', true);
            $api_key = get_post_meta($order_id, '_order_accessk', true);
            $password = get_post_meta($order_id, '_order_seckey', true);
            $ch = curl_init();

            $token = generateToken($vars['ambiente'], $vars['accesskey'], $vars['secretkey']);
            $url = sprintf(VISA_API_AUTHORIZATION_RETRIVE, $ambiente, $merchant_id, $order_id);
            $response = getRequest($url, $token);
            $response = json_decode($response);
            $url = home_url();
            echo '<p><b>Pedido:</b> #' . @$response->order->purchaseNumber . '<br/>';
            echo '<b>Código de Comercio:</b> ' . $merchant_id . '<br/>';
            if (isset($response->order)) {
                echo '<b>Estado Actual:</b> ' . $response->dataMap->STATUS . '<br/><br/></p>';
                echo "<script type='text/javascript' src='" . plugin_dir_url(__FILE__) . "visanet.js'></script>";
                switch ($response->dataMap->STATUS) {
                    case "Confirmed":
                        echo '<button id="vn" type="button" class="button save_order button-primary" onclick="acciones(\'' . $url . '\', \'1\', \'' . $order_id . '\', \'' . $response->order->purchaseNumber . '\', \'' . $merchant_id . '\', \'' . $api_key . '\', \'' . $password . '\');">Depositar</button>';
                        echo '<br><br><button id="vn" type="button" class="button save_order button-primary" onclick="acciones(\'' . $url . '\', \'3\', \'' . $order_id . '\', \'' . $response->order->purchaseNumber . '\', \'' . $merchant_id . '\', \'' . $api_key . '\', \'' . $password . '\');">Anular</button>';
                        break;
                    case "DEPOSITADO":
                        echo '<button id="vn" type="button" class="button save_order button-primary" onclick="acciones(\'' . $url . '\', \'2\', \'' . $order_id . '\', \'' . $response->order->purchaseNumber . '\', \'' . $merchant_id . '\', \'' . $api_key . '\', \'' . $password . '\');">Cancelar Deposito</button>';
                        break;
                    case "ANULADO":
                        echo "<p style='color:#d60000; font-weight:bold;'>Sin acciones disponibles, el pedido se encuentra Anulado.</p>";
                        break;
                    default:
                        echo '';
                        break;
                }
            } else {
                echo "<pre>";
                print_r($response);
                echo "</pre>";
            }
        }
    }

    /** AGREGAMOS LAS OPCIONES ADICIONALES PARA LOS PRODUCTOS **/

    add_action('woocommerce_product_write_panel_tabs', 'tabVisanet');

    function tabVisanet()
    {
        ?>
        <li class="custom_tab"><a href="#tabVisanet"> <?php _e('Visa', 'woocommerce');?></a></li>
        <?php
}

    function tabVisanet_product_tab_content()
    {
        global $post;

        // Note the 'id' attribute needs to match the 'target' parameter set above
        ?><div id='tabVisanet' class='panel woocommerce_options_panel'><?php
?>
        <div class='options_group'>
            <h2>Configuración para <b>Soles</b></h2>
            <?php
woocommerce_wp_text_input(array(
            'id' => '_codigo_comercio_soles',
            'label' => __('Codigo Comercio', 'woocommerce'),
            'desc_tip' => 'true',
            'description' => __('Ingrese su código de comercio de Visanet para Soles'),
            'type' => 'text',
            'custom_attributes' => array(
                'placeholder' => 'Codigo Comercio Soles',
            ),
        ));

        woocommerce_wp_text_input(array(
            'id' => '_access_key_soles',
            'label' => __('Access Key', 'woocommerce'),
            'desc_tip' => 'true',
            'description' => __('Ingrese su Access Key ID de Visanet para Soles'),
            'type' => 'text',
            'custom_attributes' => array(
                'placeholder' => 'Access Key',
            ),
        ));

        woocommerce_wp_text_input(array(
            'id' => '_secret_key_soles',
            'label' => __('Secret Key', 'woocommerce'),
            'desc_tip' => 'true',
            'description' => __('Ingrese su Secret Key ID de Visanet para Soles'),
            'type' => 'text',
            'custom_attributes' => array(
                'placeholder' => 'Secret Key',
            ),
        ));

        ?>
            <hr>
            <h2>Configuración para <b>Dólares</b></h2>
                <?php
woocommerce_wp_text_input(array(
            'id' => '_codigo_comercio_dolares',
            'label' => __('Codigo Comercio', 'woocommerce'),
            'desc_tip' => 'true',
            'description' => __('Ingrese su código de comercio de Visanet para Dólares'),
            'type' => 'text',
            'custom_attributes' => array(
                'placeholder' => 'Codigo Comercio Soles',
            ),
        ));

        woocommerce_wp_text_input(array(
            'id' => '_access_key_dolares',
            'label' => __('Access Key', 'woocommerce'),
            'desc_tip' => 'true',
            'description' => __('Ingrese su Access Key ID de Visanet para Dólares'),
            'type' => 'text',
            'custom_attributes' => array(
                'placeholder' => 'Access Key',
            ),
        ));

        woocommerce_wp_text_input(array(
            'id' => '_secret_key_dolares',
            'label' => __('Secret Key', 'woocommerce'),
            'desc_tip' => 'true',
            'description' => __('Ingrese su Secret Key ID de Visanet para Dólares'),
            'type' => 'text',
            'custom_attributes' => array(
                'placeholder' => 'Secret Key',
            ),
        ));
        ?>

                <hr>
                <h2><b>Logo</b></h2>
                <?php
woocommerce_wp_text_input(array(
            'id' => '_img_logoprd',
            'label' => __('Logo de Producto', 'woocommerce'),
            'desc_tip' => 'true',
            'description' => __('URL del logo que será mostrado en el Popup de Visa al momento de comprar este producto.'),
            'type' => 'text',
            'custom_attributes' => array(
                'placeholder' => 'URL Logo Producto',
            ),
        ));
        ?>
        </div>

        </div><?php
}

    add_filter('woocommerce_product_data_panels', 'tabVisanet_product_tab_content'); // WC 2.6 and up
    function save_giftcard_option_fields($post_id)
    {

        $woocommerce_codigo_comercio_soles = $_POST['_codigo_comercio_soles'];
        if (!empty($woocommerce_codigo_comercio_soles)) {
            update_post_meta($post_id, '_codigo_comercio_soles', esc_attr($woocommerce_codigo_comercio_soles));
        }

        $woocommerce_access_key_soles = $_POST['_access_key_soles'];
        if (!empty($woocommerce_access_key_soles)) {
            update_post_meta($post_id, '_access_key_soles', esc_attr($woocommerce_access_key_soles));
        }

        $woocommerce_secret_key_soles = $_POST['_secret_key_soles'];
        if (!empty($woocommerce_secret_key_soles)) {
            update_post_meta($post_id, '_secret_key_soles', esc_attr($woocommerce_secret_key_soles));
        }

        $woocommerce_codigo_comercio_dolares = $_POST['_codigo_comercio_dolares'];
        if (!empty($woocommerce_codigo_comercio_dolares)) {
            update_post_meta($post_id, '_codigo_comercio_dolares', esc_attr($woocommerce_codigo_comercio_dolares));
        }

        $woocommerce_access_key_dolares = $_POST['_access_key_dolares'];
        if (!empty($woocommerce_access_key_soles)) {
            update_post_meta($post_id, '_access_key_soles', esc_attr($woocommerce_access_key_soles));
        }

        $woocommerce_secret_key_dolares = $_POST['_secret_key_dolares'];
        if (!empty($woocommerce_secret_key_dolares)) {
            update_post_meta($post_id, '_secret_key_dolares', esc_attr($woocommerce_secret_key_dolares));
        }

        $woocommerce_img_logoprd = $_POST['_img_logoprd'];
        if (!empty($woocommerce_img_logoprd)) {
            update_post_meta($post_id, '_img_logoprd', esc_attr($woocommerce_img_logoprd));
        }

    }
    add_action('woocommerce_process_product_meta_simple', 'save_giftcard_option_fields');
    add_action('woocommerce_process_product_meta_variable', 'save_giftcard_option_fields');

    function visanet_multicomercio_empty_cart($valid, $product_id, $quantity)
    {
        $vars = get_option("woocommerce_visanet_settings");
        if ($vars['multicomercio'] == "yes") {
            if (!empty(WC()->cart->get_cart()) && $valid) {
                WC()->cart->empty_cart();
                wc_add_notice('Sólo se admite 1 producto en su carro de compras. Se ha reemplazado el producto anterior por este.', 'notice');
            }
        }

        return $valid;

    }
    add_filter('woocommerce_add_to_cart_validation', 'visanet_multicomercio_empty_cart', 10, 3);
}