<?php
/**
 * @package UpnQr
 */

/*
Plugin Name: UPN QR Nalog
Plugin URI: http://senk.eu/
Description: Izpis UPN QR naloga za placevanje woocommerce storitev.
Version: 0.1
Author: shenk
Author URI: http://senk.eu/
Text Domain: upn-qr
*/

if (!defined('ABSPATH')) {
    die;
}

define('UQ__PLUGIN_PATH', plugin_dir_path(__FILE__));
define('UQ__PLUGIN_URL', plugin_dir_url(__FILE__));

class UpnQrNalog {
	static $instance = false;

	public static function getInstance() {
		if (!self::$instance) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	public function init() {
		add_action('admin_init', array($this, 'settings'));
		add_action('admin_menu', array($this, 'submenu'));
		add_action('wp_enqueue_scripts', array($this, 'scripts'), 15);
		add_action('woocommerce_thankyou', array($this, 'output'), 15);
	}

	public function scripts() {
		if (is_order_received_page()) {
			wp_enqueue_style('uq-nalog-css', plugins_url('public/css/style.css', __FILE__));
			wp_enqueue_script('qr-library', plugins_url('lib/qrcodegen.js', __FILE__), array(), false, true);
			wp_enqueue_script('qr-script', plugins_url('inc/generate-qr.js', __FILE__), array('jquery', 'qr-library'), false, true);
		}
	}
	
	public function output($orderId) {
		include dirname(__FILE__) . '/templates/upn.php';
	}
	
	public function submenu() {
		add_submenu_page(
			'woocommerce',
			'UPN QR Nastavitve',
			'UPN QR Nastavitve',
			'manage_options',
			'upn-qr-nastavitve',
			array($this, 'submenuCb')
		);
	}
	
	public function submenuCb() {
		if (!current_user_can('manage_options')) {
			return;
		}
		?>
			<div class="wrap">
				<h1><?php echo esc_html(get_admin_page_title()); ?></h1>
				<form action="options.php" method="post">
					<?php
					settings_fields('uqoptions');
					do_settings_sections('uqoptions');
					submit_button('Shrani');
					?>
				</form>
			</div>
		<?php
	}
	
	public function settings() {
		add_settings_section(
			'uqoptions_section',
			__('Nastavitve', 'upn-qr'),
			array($this, 'settingsSectionCb'),
			'uqoptions'
		);
		
		// Fields
		register_setting('uqoptions', 'uq_iban');
		add_settings_field(
			'uq_iban',
			'IBAN',
			array($this, 'settingsIban'),
			'uqoptions',
			'uqoptions_section'
		);
		register_setting('uqoptions', 'uq_ime');
		add_settings_field(
			'uq_ime',
			'Ime',
			array($this, 'settingsIme'),
			'uqoptions',
			'uqoptions_section'
		);
		register_setting('uqoptions', 'uq_ulica');
		add_settings_field(
			'uq_ulica',
			'Ulica',
			array($this, 'settingsUlica'),
			'uqoptions',
			'uqoptions_section'
		);
		register_setting('uqoptions', 'uq_kraj');
		add_settings_field(
			'uq_kraj',
			'Kraj',
			array($this, 'settingsKraj'),
			'uqoptions',
			'uqoptions_section'
		);
	}
	
	public function settingsSectionCb() {}
	
	public function settingsIban() {
		$value = esc_attr(get_option('uq_iban'));
		echo '<input type="text" class="regular-text" id="uq_iban" name="uq_iban" value="' . $value . '">';
	}
	
	public function settingsIme() {
		$value = esc_attr(get_option('uq_ime'));
		echo '<input type="text" class="regular-text" id="uq_ime" name="uq_ime" value="' . $value . '">';
	}
	
	public function settingsUlica() {
		$value = esc_attr(get_option('uq_ulica'));
		echo '<input type="text" class="regular-text" id="uq_ulica" name="uq_ulica" value="' . $value . '">';
	}
	
	public function settingsKraj() {
		$value = esc_attr(get_option('uq_kraj'));
		echo '<input type="text" class="regular-text" id="uq_kraj" name="uq_kraj" value="' . $value . '">';
	}
}

$UpnQrNalog = UpnQrNalog::getInstance();
$UpnQrNalog->init();