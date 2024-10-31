<?php
/*
 * Plugin Name:       Order Picking App
 * Description:       Make your life easier by using the Orderpicking App. You'll never be inefficient if the Orderpicking App is installed in your store. We assist you in all aspects of your webshop. From intelligent selecting to order packing, we have you covered. Connecting the Orderpicking App to your Woocommerce webshop is simple and quick. Within an hour, you'll be online with the Orderpicking App. You're able to pick and pack your orders three times faster and with greater accuracy.
 * Version:           1.6.7
 * Author:            Arture | PHP Professionals
 * Author URI:        http://arture.nl
 * Text Domain:       orderpickingapp
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'OPA_PLUGIN_DIR', trailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'OPA_PLUGIN_URL', trailingslashit( plugin_dir_url( __FILE__ ) ) );

function orderpickingapp_plugin_activation() {
    $home_url = str_replace('https://', '', home_url());
    $home_url = str_replace('http://', '', $home_url);

    $url = "https://statistics.orderpickingapp.com/wordpress/activation?";
    $params = array(
        'site'      => $home_url,
        'company'   => get_bloginfo('name'),
        'country'   => get_option('woocommerce_default_country'),
        'ip'        => $_SERVER['REMOTE_ADDR']
    );
    $url.= http_build_query($params, '', '&');

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_exec($ch);
    curl_close($ch);
}
register_activation_hook(__FILE__, 'orderpickingapp_plugin_activation');

function orderpickingapp_plugin_deactivation() {
    $home_url = str_replace('https://', '', home_url());
    $home_url = str_replace('http://', '', $home_url);

    $url = "https://statistics.orderpickingapp.com/wordpress/deactivation?";
    $params = array(
        'site'      => $home_url,
        'company'   => get_bloginfo('name'),
        'country'   => get_option('woocommerce_default_country'),
        'ip'        => $_SERVER['REMOTE_ADDR']
    );
    $url.= http_build_query($params, '', '&');

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_exec($ch);
    curl_close($ch);

    delete_option('orderpickingapp_apikey');
}
register_deactivation_hook(__FILE__, 'orderpickingapp_plugin_deactivation');

function run_orderpickingapp() {
	
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-orderpickingapp.php';

	$orderpickingapp_template = new OrderPickingApp();
    $orderpickingapp_template->run();
}
run_orderpickingapp();
