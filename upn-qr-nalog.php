<?php
/**
 * @package UpnQr
 */

/*
Plugin Name: UPN QR Nalog
Plugin URI: http://senk.eu/
Description: Izpis UPN QR naloga za placevanje woocommerce storitev.
Version: 0.4
WC tested up to: 5.9
Author: shenk
Author URI: http://senk.eu/
Text Domain: upn-qr
*/

if (!defined('ABSPATH')) {
    die;
}

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

    $uqHook = get_option('uq_hook', 'woocommerce_thankyou');
		if (empty($uqHook)) {
			$uqHook = 'woocommerce_thankyou';
		}
		
		$uqPosition = get_option('uq_position', 10);
		if ($uqPosition !== 0 && empty($uqPosition)) {
			$uqPosition = 10;
		}

		add_action(
		  $uqHook,
		  array($this, 'output'),
		  $uqPosition
		);

    add_filter('woocommerce_bacs_account_fields', array($this, 'bacs_fields'), 10, 2);
	}

	public function scripts() {
		if (is_order_received_page()) {
			wp_enqueue_style('uq-nalog', plugins_url('public/css/style.css', __FILE__));
			wp_enqueue_script('qr-library', plugins_url('lib/qrcodegen.js', __FILE__), array(), false, true);
			wp_enqueue_script('qr-script', plugins_url('inc/generate-qr.js', __FILE__), array('jquery', 'qr-library'), false, true);
		}
	}
	
	public function output($orderId) {
    // $isQrPhp = true;
    // $hideBigQr = true;
    // $hideTitles = true;
    // $isPng = true;
		include dirname(__FILE__) . '/templates/upn.php';
  }
  
  public function bacs_fields($account_fields, $order_id) {
    $account_fields['reference'] = array(
      'label' => __('Referenca', 'upn-qr'),
      'value' => esc_attr(get_option('uq_model')) . ' ' . str_replace('%id%', $order_id, esc_attr(get_option('uq_sklic')))
    );
    
    $account_fields['purpose'] = array(
      'label' => __('Namen plačila', 'upn-qr'),
      'value' => str_replace('%id%', $order_id, esc_attr(get_option('uq_namen')))
    );
    
    $account_fields['purpose_code'] = array(
      'label' => __('Koda namena', 'upn-rq'),
      'value' => esc_attr(get_option('uq_koda'))
    );
    
    return $account_fields;
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
		register_setting('uqoptions', 'uq_namen');
		add_settings_field(
			'uq_namen',
			'Namen plačila',
			array($this, 'settingsNamen'),
			'uqoptions',
			'uqoptions_section'
		);
		register_setting('uqoptions', 'uq_koda');
		add_settings_field(
			'uq_koda',
			'Koda namena',
			array($this, 'settingsKoda'),
			'uqoptions',
			'uqoptions_section'
		);
		register_setting('uqoptions', 'uq_model');
		add_settings_field(
			'uq_model',
			'Model reference',
			array($this, 'settingsModel'),
			'uqoptions',
			'uqoptions_section'
		);
		register_setting('uqoptions', 'uq_sklic');
		add_settings_field(
			'uq_sklic',
			'Sklic reference',
			array($this, 'settingsSklic'),
			'uqoptions',
			'uqoptions_section'
    );
    register_setting('uqoptions', 'uq_hook');
		add_settings_field(
			'uq_hook',
			'Napredno: Akcijska kljuka',
			array($this, 'settingsHook'),
			'uqoptions',
			'uqoptions_section'
		);
    register_setting('uqoptions', 'uq_position');
		add_settings_field(
			'uq_position',
			'Napredno: Zaporedje prikaza',
			array($this, 'settingsPosition'),
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
		echo '<p class="description">Največ 33 znakov.</p>';
	}
	
	public function settingsUlica() {
		$value = esc_attr(get_option('uq_ulica'));
		echo '<input type="text" class="regular-text" id="uq_ulica" name="uq_ulica" value="' . $value . '">';
		echo '<p class="description">Največ 33 znakov.</p>';
	}
	
	public function settingsKraj() {
		$value = esc_attr(get_option('uq_kraj'));
		echo '<input type="text" class="regular-text" id="uq_kraj" name="uq_kraj" value="' . $value . '">';
		echo '<p class="description">Največ 33 znakov.</p>';
	}
	
	public function settingsNamen() {
		$value = esc_attr(get_option('uq_namen'));
		echo '<input type="text" class="regular-text" id="uq_namen" name="uq_namen" value="' . $value . '">';
		echo '<p class="description">Vstavi %id% za izpis IDja naročila. Največ 42 znakov.</p>';
	}
	
	public function settingsKoda() {
		$value = esc_attr(get_option('uq_koda'));
		echo '<input type="text" class="regular-text" id="uq_koda" name="uq_koda" value="' . $value . '">';
		echo '<p class="description">4 črke.</p>';
	}
	
	public function settingsModel() {
		$value = esc_attr(get_option('uq_model'));
		echo '<input type="text" class="regular-text" id="uq_model" name="uq_model" value="' . $value . '">';
		echo '<p class="description">4 znaki.</p>';
	}
	
	public function settingsSklic() {
		$value = esc_attr(get_option('uq_sklic'));
		echo '<input type="text" class="regular-text" id="uq_sklic" name="uq_sklic" value="' . $value . '">';
		echo '<p class="description">Vstavi %id% za izpis IDja naročila. Največ 22 znakov.</p>';
  }

  public function settingsHook() {
		$value = esc_attr(get_option('uq_hook'));
		echo '<input type="text" class="regular-text" id="uq_hook" name="uq_hook" value="' . $value . '">';
		echo '<p class="description">Izberi kje naj se na potrditveni strani pokažeta QR in položnica. Default: "woocommerce_thankyou"</p>';
	}
  
  public function settingsPosition() {
		$value = esc_attr(get_option('uq_position'));
		echo '<input type="number" min="0" class="regular-text" id="uq_position" name="uq_position" value="' . $value . '">';
		echo '<p class="description">Izberi kje naj se na potrditveni strani pokažeta QR in položnica. Default: 10</p>';
	}
}

$UpnQrNalog = UpnQrNalog::getInstance();
$UpnQrNalog->init();
