<?php

/**
 * Class Monetivo
 * @author Jakub Jasiulewicz <jjasiulewicz@monetivo.com>
 * @author Grzegorz Agaciński <gagacinski@monetivo.com>
 */
class WC_Monetivo extends WC_Payment_Gateway
{
    protected $plugin_file = 'monetivo/monetivo.php';
    protected $woocommerce;

    protected $suported_currencies = array('PLN');
    private $plugin_version = '1.1.0';
    private $token_cache = 600;

    public function __construct()
    {
        // import WooCommerce instance from globals
        $this->woocommerce = $GLOBALS['woocommerce'];

        // basic settings required to implement the gateway
        $this->id = 'monetivo';
        $this->icon = WC_MONETIVO_URI . 'images/mvo_logo_157x64.png';
        $this->has_fields = true;

        $this->method_title = __( 'monetivo', 'woocommerce' );
        $this->configuration_page = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $this->id );

        // load the settings.
        $this->init_settings();

        // define user set variables
        $this->title = (isset( $this->settings['title'] ) ? $this->settings['title'] : '');
        $this->description = (isset( $this->settings['description'] )) ? $this->settings['description'] : 'Zapłać przez monetivo';
        $this->app_token = (isset( $this->settings['mvo_app_token'] ) ? $this->settings['mvo_app_token'] : '');
        $this->login = (isset( $this->settings['mvo_login'] ) ? $this->settings['mvo_login'] : '');
        $this->password = (isset( $this->settings['mvo_password'] ) ? $this->settings['mvo_password'] : '');

        // setup admin options
        $this->init_form_fields();

        // display notice if plugin was not configured at all
        if ( empty( $this->app_token ) || empty( $this->login ) || empty( $this->password ) ) {
            $this->add_admin_notice( 'Wtyczka monetivo dla WooCommerce nie jest poprawnie skonfigurowana. <a href="' . $this->configuration_page . '">Przejdź do konfiguracji</a>' );
        }

        // display notice for the admin if plugin was configured using testing credentials
        if ( $this->enabled == 'yes' && ! empty( $this->login ) && ! empty( $this->password ) && false !== strpos( $this->app_token, 'test' ) ) {
            $this->add_admin_notice( 'Wtyczka monetivo dla WooCommerce została skonfigurowana w trybie testowym! Pamiętaj o zmianie danych logowania by móc przyjmować rzeczywiste płatności. Zmian możesz dokonać <a href="' . $this->configuration_page . '">tutaj</a>' );
        }

        // handle settings validation/update
        if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options') );
        } else {
            add_action( 'woocommerce_update_options_payment_gateways', array($this, 'process_admin_options') );
        }

        // disable gateway on list if currency is unsupported
        add_filter( 'woocommerce_available_payment_gateways', array($this, 'disable_gateway'), 1 );
        if ( ! $this->is_valid_for_use() ) {
            $this->enabled = 'no';
        }

        // callback
        add_action( 'woocommerce_api_wc_monetivo', array($this, 'handle_callbacks') );

        add_filter( 'payment_fields', array($this, 'payment_fields') );
    }

    /** Checks several conditions if this gateway can be enabled
     * @param $gateway_list
     * @return mixed
     */
    public function disable_gateway( $gateway_list )
    {
        // check if currency is supported
        if ( isset( $gateway_list['monetivo'] ) && ! in_array( get_woocommerce_currency(), $this->suported_currencies ) ) {
            unset( $gateway_list['monetivo'] );
        }

        // check if required settings are saved
        if ( empty( $this->app_token ) || empty( $this->login ) || empty( $this->password ) ) {
            unset( $gateway_list['monetivo'] );
        }

        return $gateway_list;
    }

    /** Initialize API client
     * @param null $app_token
     * @param null $login
     * @param null $password
     * @return \Monetivo\MerchantApi
     * @throws \Monetivo\Exceptions\MonetivoException
     */
    public function init_api_client( $app_token = null, $login = null, $password = null )
    {
        // fill default settings
        if ( empty( $app_token ) ) {
            $app_token = $this->get_option( 'mvo_app_token' );
        }
        if ( empty( $login ) ) {
            $login = $this->get_option( 'mvo_login' );
        }
        if ( empty( $password ) ) {
            $password = $this->get_option( 'mvo_password' );
        }

        $client = new Monetivo\MerchantApi( $app_token );
        // set platform name with format: woocommerce-<WP_version>-<WC_version>-<Plugin_version>
        $client->setPlatform( sprintf( 'monetivo-woocommerce-%s-%s-%s', get_bloginfo( 'version' ), WOOCOMMERCE_VERSION, $this->plugin_version ) );

        // use custom API endpoint if provided as env variable
        $custom_endpoint = getenv( 'MONETIVO_API_ENDPOINT' );
        if ( ! empty( $custom_endpoint ) ) {
            $client->setBaseAPIEndpoint( $custom_endpoint );
        }

        // override API endpoint if sandbox mode was enabled as env variable or test mode was enabled in settings
        $sandbox_mode = getenv( 'MONETIVO_API_SANDBOX' );
        if ( $sandbox_mode ) {
            $client->setSandboxMode();
        }

        // save curl requests to the log if debug was enabled
        if ( WP_DEBUG ) {
            $client->enableLogging( WP_CONTENT_DIR . '/debug.log' );
        }

        // authenticate and save auth_token in Transient API cache
        if ( WP_DEBUG || false === ($auth_token = get_transient( 'mvo_wc_auth_token' )) ) {
            $auth_token = $client->auth( $login, $password );
            set_transient( 'mvo_wc_auth_token', $auth_token, $this->token_cache );
        }
        $client->setAuthToken( $auth_token );

        return $client;
    }

    /**
     * Initializes form fields
     */
    public function init_form_fields()
    {
        $this->form_fields = include __DIR__ . '/form-fields.php';
    }

    /**
     * Validates admin settings and display errors
     */
    public function process_admin_options()
    {
        if ( version_compare( WOOCOMMERCE_VERSION, '2.6.4', '>=' ) ) {
            $this->validate_settings_fields();
            if ( ! empty( $this->errors ) ) {
                $this->display_errors();
            }
        }
        parent::process_admin_options();
    }

    /**
     * Displays errors while saving admin settings (if any)
     */
    public function display_errors()
    {
        foreach ($this->errors as $v) {
            echo '<div class="error">' . __( 'Błąd', 'monetivo' ) . ': ' . $v . '</div>';
        }
        echo '<script type="text/javascript">jQuery(document).ready(function () {jQuery(".updated").remove();});</script>';
    }

    /** Validates settings
     * @param bool $form_fields
     */
    public function validate_settings_fields( $form_fields = false )
    {
        if ( ! $form_fields ) {
            $form_fields = $this->get_form_fields();
        }

        $prefix = $this->plugin_id . $this->id . '_';
        $settings_fields = array('mvo_app_token', 'mvo_login', 'mvo_password');

        // sanitizing fields
        foreach ($settings_fields as $field) {
            if ( isset( $_POST[$prefix . $field] ) ) {
                $_POST[$prefix . $field] = sanitize_text_field( $_POST[$prefix . $field] ) ;
            }
        }

        // checking required settings

        if ( empty( $_POST[$prefix . 'mvo_app_token'] ) ) {
            $this->errors['mvo_app_token'] = 'Token aplikacji jest wymagany';
        }

        if ( empty( $_POST[$prefix . 'mvo_login'] ) ) {
            $this->errors['mvo_login'] = 'Login użytkownika jest wymagany';
        }

        if ( empty( $_POST[$prefix . 'mvo_password'] ) ) {
            $this->errors['mvo_password'] = 'Hasło użytkownika jest wymagane';
        }

        if ( ! empty( $this->errors ) ) {
            // disable gateway
            update_option( $prefix . 'enabled', 'no' );
            unset( $_POST[$prefix . 'enabled'] );
            return;
        }

        // clear auth token
        delete_transient( 'mvo_wc_auth_token' );

        // trying to establish connection to API with provided settings; we are trying to makes sure that the gateway is configured properly - if not it is automatically disabled
        try {
            $client = $this->init_api_client( $_POST[$prefix . 'mvo_app_token'], $_POST[$prefix . 'mvo_login'], $_POST[$prefix . 'mvo_password'] );
            $client->call( 'get', 'auth/check_token' );
        } catch ( \Monetivo\Exceptions\MonetivoException $exception ) {
            $this->write_log( $exception );
            if ( $exception->getHttpCode() === 401 ) {
                $this->errors['mvo_wc_app_token'] = 'Dane logowania są nieprawidłowe';
            } else {
                $this->errors['mvo_wc_app_token'] = 'Wystąpił błąd połączenia (' . $exception->getHttpCode() . ')';
            }

            // disable gateway
            update_option( $prefix . 'enabled', 'no' );
            unset( $_POST[$prefix . 'enabled'] );
        } catch ( Exception $exception ) {
            $this->write_log( $exception );
            $this->errors['mvo_wc_app_token'] = 'Wystąpił błąd';
            // disable gateway
            update_option( $prefix . 'enabled', 'no' );
            unset( $_POST[$prefix . 'enabled'] );
        }
    }

    /**
     * Checks if this gateway is enabled and available in the user's country.
     * @return bool
     */
    public function is_valid_for_use()
    {
        return in_array( get_woocommerce_currency(), apply_filters( 'woocommerce_monetivo_supported_currencies', $this->suported_currencies ) );
    }

    /**
     * Shows admin options
     */
    public function admin_options()
    {
        if ( ! $this->is_valid_for_use() ) {
            echo "<div class=\"inline error\"><p><strong>" . _e( 'Gateway Disabled', 'woocommerce' ) . "</strong>:" . _e( 'Wybrana waluta nie jest wspierana.', 'monetivo' ) . "</p></div>";
        } else {
            echo '<h3>' . __( 'Bramka płatności monetivo', 'monetivo' ) . '</h3>';
            echo '<table class="form-table">';
            // Generate the HTML For the settings form.
            $this->generate_settings_html();
            echo '</table>';
        }
    }

    /**
     * Handles callbacks
     */
    public function handle_callbacks()
    {
        if ( ! empty( $_POST['identifier'] ) ) {
            try {
                // obtain details about transaction from Monetivo
                $transaction = $this->init_api_client()->transactions()->details( $_POST['identifier'] );
                $order = new WC_Order( $transaction['order_data']['order_id'] );

                // complete transaction if status is ACCEPTED
                if ( $transaction['status'] == \Monetivo\Api\Transactions::TRAN_STATUS_ACCEPTED ) {
                    $order->add_order_note( __( 'Odebrano wpłatę z monetivo '.$transaction['identifier'], 'woocommerce' ) );
                    $order->payment_complete();
                    status_header( 200 );
                    exit;
                }
            } catch ( Exception $e ) {
                $this->write_log( $e->getMessage() );
            }
            status_header( 500 );
            exit;
        }

        if ( isset( $_GET['order_id'] ) ) {
            $order = new WC_Order( $_GET['order_id'] );

            if ( $order->status == 'failed' ) {
                $this->add_notice( __( 'Błąd płatności: ', 'monetivo' ) . __( 'Przepraszamy, ale Twoja transakcja nie została przeprowadzona pomyślnie, prosimy spróbować ponownie.', 'monetivo' ), 'error' );

                wp_redirect( $order->get_cancel_order_url_raw() );
            } elseif ( $order->status == 'completed' || $order->status == 'processing' ) {
                $this->woocommerce->cart->empty_cart();
                wp_redirect( $this->get_return_url( $order ) );

            } else {
                $this->add_notice(
                    __( 'Dziękujemy za dokonanie zakupu. Twoje zamówienie zostanie zrealizowane po zaksięgowaniu płatności.', 'monetivo' ),
                    'notice'
                );

                wp_redirect( $this->get_return_url( $order ) );
            }
        }
    }


    /** Registers new payment
     * @param int $order_id
     * @return array
     */
    public function process_payment( $order_id )
    {
        $order = new WC_Order( $order_id );

        // prepare amount
        $amount = str_replace( ',', '.', $order->get_total() );
        $amount = number_format( $amount, 2, '.', '' );

        $return_url = add_query_arg( array('wc-api' => 'WC_Monetivo', 'order_id' => $order_id), home_url( '/' ) );
        $notify_url = $return_url;

        // prepare order description
        $desc = __( 'Zamówienie', 'monetivo' ) . ' #' . $order->get_order_number() . ', ' . $order->billing_first_name . ' ' . $order->billing_last_name . ', ' . date( 'Ymdhi' );
        $this->write_log( $notify_url );
        $params = array(
            'order_data' => [
                'description' => $desc,
                'order_id' => $order_id],
            'buyer' => [
                'name' => $order->billing_first_name . ' ' . $order->billing_last_name,
                'email' => $order->billing_email],
            'language' => 'pl',
            'currency' => strtoupper( $order->get_order_currency() ),
            'amount' => $amount,
            'return_url' => $return_url, // GET requests
            'notify_url' => $notify_url // POST requests, handling notification
        );

        // get selected channel
        $channel_id = filter_input(INPUT_POST, 'mvo_channel');
        if($channel_id)
            $params = array_merge($params,array('channel_id' => $channel_id, 'accept_terms' => '1'));

        try {
            // create transaction in Monetivo
            $transaction = $this->init_api_client()->transactions()->create( $params );
            // note the returned identifier; can be helpful in some situations
            $order->add_order_note('Wygenerowano transakcję: '. $transaction['identifier']);
            // Mark as on-hold
            $order->update_status('on-hold', __('Oczekiwanie na płatność monetivo', 'woocommerce'));
            // Reduce stock levels
            $order->reduce_order_stock();
        } catch ( Exception $exception ) {
            $this->write_log( $exception );
            // something went wrong
            wc_add_notice( __( 'Wystąpił błąd, prosimy spróbować ponownie.', 'woocommerce' ), 'error' );
            $order->add_order_note( 'Payment failed' );
            return;
        }

        // return URL where user is redirected to make a payment
        return array('result' => 'success', 'redirect' => $transaction['redirect_url']);
    }


    /** Adds channel selection to checkout page
     * @return void
     */
    public function payment_fields()
    {
        try {
            $paymentChannels = $this->init_api_client()->paymentChannels()->listing( 'PLN' );
            unset($paymentChannels['httpCode']);

            function orderBy($a, $b)
            {
                return $a['order_by'] - $b['order_by'];
            }
            usort($paymentChannels,'orderBy');

            $mvoChannels = '<input type="hidden" name="mvo_channel" id="mvo_channel" />
                <style> div.mvo_box { background-color:#fff;margin-right:0.5em;margin-bottom:0.5em;display:inline-flex;padding:0.5em;border:2px solid #e4e4e4 }
                 div.mvo_box:hover,div.mvo_selected { border-color:#000;cursor: pointer; } 
                 div.mvo_box > img { width:129px;height:65px; } </style><script> var selectMvoChannel = function(channel_id, input) {
                   var x = document.getElementsByClassName("mvo_box");
                    var i;
                    for (i = 0; i < x.length; i++) {
                        x[i].classList.remove(\'mvo_selected\');
                    }
                    input.classList.add(\'mvo_selected\');
                    document.getElementById(\'mvo_channel\').value = channel_id;
                 }; </script>';
            foreach ($paymentChannels as $pc)
            {
                $mvoChannels .= '
                <div class="mvo_box" onclick="selectMvoChannel('.$pc['channel_id'].', this);">
                <img src="'.str_replace('.png','@2x.png',$pc['img']).'" alt="'.$pc['name'].'" title="'.$pc['name'].'" />
                </div>';
            }
            $mvoChannels .= '<p style="text-align: right;font-weight: bold;font-size:0.7em;">powered by <a href="https://monetivo.com" target="_blank" rel="noopener">monetivo</a>. just like that<i>!</i></p>';
            echo $mvoChannels;

        } catch ( Exception $exception ) {
            $this->write_log( $exception );
            // something went wrong
            echo '';
        }
    }

    /** Adds admin notice
     * @param $message
     * @param string $class
     */
    public function add_admin_notice( $message, $class = 'notice notice-error' )
    {
        $func = function () use ( $class, $message ) {
            $message = __( $message, 'sample-text-domain' );

            printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );
        };
        add_action( 'admin_notices', $func );
    }

    /** Adds notice
     * @param $message
     * @param $type
     */
    public function add_notice( $message, $type )
    {
        if ( $type == 'error' && method_exists( $this->woocommerce, 'add_error' ) ) {
            $this->woocommerce->add_error( $message );
        } elseif ( in_array( $type, array('success', 'notice') ) && method_exists( $this->woocommerce, 'add_message' ) ) {
            $this->woocommerce->add_message( $message );
        } else {
            wc_add_notice( $message, $type );
        }
    }

    /** Writes messages to log
     * @param $log
     */
    public function write_log( $log )
    {
        if ( ! WP_DEBUG ) {
            return;
        }

        if ( is_array( $log ) || is_object( $log ) ) {
            error_log( 'monetivo: ' . print_r( $log, true ) );
        } else {
            error_log( 'monetivo: ' . $log );
        }

    }
}