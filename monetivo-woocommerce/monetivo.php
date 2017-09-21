<?php
/**
 *
 * monetivo Woocommerce payment module
 *
 * @author monetivo
 *
 * Plugin Name: monetivo Woocommerce payment gateway
 * Description: Bramka płatności Monetivo do WooCommerce.
 * Author: monetivo
 * Author URI: https://monetivo.com
 * Version: 1.1.1
 */

if (!defined( 'ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Activation hook
 */
function activate_monetivo_woocommerce()
{

    if (is_admin() && current_user_can('activate_plugins'))
    {
        //checking if Woocommerce plugin is installed

        if ( ! is_plugin_active('woocommerce/woocommerce.php'))
        {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die( 'Wtyczka monetivo wymaga instalacji oraz aktywacji Woocommerce.', 'Monetivo', ['back_link' => true] );
            return;
        }

        // checking if curl extension is installed
        if ( ! extension_loaded('curl') || !function_exists('curl_init') )
        {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die( 'Wtyczka wymaga instalacji rozszerzenia curl na serwerze', 'Monetivo', ['back_link' => true] );
            return;
        }

        return;
    }

}

/**
 * Deactivation hook
 */
function uninstall_monetivo_woocommerce() {
    delete_option('woocommerce_monetivo_settings');
    delete_transient('mvo_wc_auth_token');
    wp_cache_flush();
}

/**
 * Initialization hook
 */
function load_monetivo_woocommerce()
{
    if(!class_exists('WC_Payment_Gateway'))
        return;

    require_once plugin_dir_path(__FILE__) . 'includes/monetivo/monetivo-php/autoload.php';
    require_once plugin_dir_path(__FILE__) . 'includes/class-wc-monetivo.php';
    add_filter('woocommerce_payment_gateways', function($methods) {
        $methods[] = 'WC_Monetivo';
        return $methods;
    });
}

register_activation_hook(__FILE__, 'activate_monetivo_woocommerce');
register_uninstall_hook(__FILE__, 'uninstall_monetivo_woocommerce');
add_action('plugins_loaded', 'load_monetivo_woocommerce', 0);
define('WC_MONETIVO_PLUGIN_PATH', __FILE__);
define('WC_MONETIVO_URI', plugin_dir_url(__FILE__));



