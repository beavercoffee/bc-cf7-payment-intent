<?php
/*
Author: Beaver Coffee
Author URI: https://beaver.coffee
Description: Pay with Contact Form 7.
Domain Path:
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Network: true
Plugin Name: BC CF7 Payment Intent
Plugin URI: https://github.com/beavercoffee/bc-cf7-payment-intent
Requires at least: 5.7
Requires PHP: 5.6
Text Domain: bc-cf7-payment-intent
Version: 1.7.17
*/

if(defined('ABSPATH')){
    require_once(plugin_dir_path(__FILE__) . 'classes/class-bc-cf7-payment-intent.php');
    require_once(plugin_dir_path(__FILE__) . 'classes/class-bc-payment-intent.php');
    BC_CF7_Payment_Intent::get_instance(__FILE__);
}
