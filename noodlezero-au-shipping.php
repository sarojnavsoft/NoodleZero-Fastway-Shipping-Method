<?php

/**
 * Plugin Name: WooCommerce Australia Fastway Shipping Method
 * Plugin URI: https://sk8.tech/
 * Description: Fastway Couriers currently operates across key metropolitan and regional locations across Australia, offering a low cost and fast courier delivery service. Franchise opportunities also available.
 * Version: 1.0.5
 * Author: SK8Tech
 * Author URI: https://sk8.tech/
 * License: GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Domain Path: /lang
 * Text Domain: sk8tech-fastwayau
 */

if (!defined('WPINC')) {
	die;
}

if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
	return false;
}

if (get_option("fastway_error") !== false) {
	add_action('admin_notices', 'fastway_au_api_error');
}

function fastway_au_curl_error() {
	$class = 'notice notice-error';
	$message = __('PHP Curl extension was not enabled', 'sk8tech-fastwayau');
	printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
}

function fastway_au_api_error() {
	$class = 'notice notice-error';
	$error = get_option("fastway_error");
	if (!empty($error)) {
		$message = __($error, 'sk8tech-fastwayau');
		printf('<div class="%1$s"><p>Fastway: %2$s</p></div>', esc_attr($class), esc_html($message));

	}

}

function fastway_au_shipping_method() {
	if (!class_exists('Fastway_Au_Shipping_Method')) {
		class Fastway_Au_Shipping_Method extends WC_Shipping_Method {
			var $api_key, $pickup_rfcode, $support_type, $custom_local_parcel_price;
			public function __construct($instance_id = 0) {
				$this->id = 'fastway_au';
				$this->instance_id = absint($instance_id);
				$this->method_title = __('Fastway AU', 'sk8tech-fastwayau');
				$this->method_description = __('Fastway Couriers currently operates across key metropolitan and regional locations across Australia, offering a low cost and fast courier delivery service. Franchise opportunities also available.<br/><strong style="color:red">Currency Of Shipping Price Is In Australian Dollar<s/trong><br/><strong style="color:black">Support URL: <a href="https://sk8.tech/" target="_blank">https://sk8.tech/</a></strong><br/><strong style="color:black">Plugin URL: <a href="https://github.com/SK8-PTY-LTD/NoodleZero-Australia-Fastway-Shipping-Method/" target="_blank">Github Repo</a></strong><br/><a href="http://au.api.fastway.org/latest/docs/page/GetAPIKey.html" target="_blank" style="font-weight:bold;">Get Fastway API Key</a> ', 'sk8tech-fastwayau');
				$this->availability = 'including';
				$this->countries = array(
					'AU',
				);
				$this->init();
				$this->enabled = isset($this->settings['enabled']) ? $this->settings['enabled'] : 'yes';
				$this->title = isset($this->settings['title']) ? $this->settings['title'] : __('Fastway AU Shipping', 'sk8tech-fastwayau');
				$this->combo = isset($this->settings['combo']) ? $this->settings['combo'] : __('20', 'sk8tech-fastwayau');
				$this->api_key = $this->settings['api_key'];

				if (empty($this->api_key)) {
					$this->api_key = "b5056fe957ea82692b615808cfd881bc";
				}

				$this->pickup_rfcode = $this->settings['pickup_rfcode'];
				$this->support_type = $this->settings['support_type'];

				$this->twocombozip = isset($this->settings['twocombozip']) ? $this->settings['twocombozip'] : __('', 'sk8tech-fastwayau');

				$this->custom_white_zone_parcel_price = $this->settings['custom_white_zone_parcel_price'];
				$this->custom_red_zone_parcel_price = $this->settings['custom_red_zone_parcel_price'];
				$this->custom_orange_zone_parcel_price = $this->settings['custom_orange_zone_parcel_price'];
				$this->custom_green_zone_parcel_price = $this->settings['custom_green_zone_parcel_price'];
				$this->custom_white_zone_parcel_price = $this->settings['custom_white_zone_parcel_price'];
				$this->custom_grey_zone_parcel_price = $this->settings['custom_grey_zone_parcel_price'];

				$this->custom_nat_a2_satchel_price = $this->settings['custom_nat_a2_satchel_price'];
				$this->custom_nat_a3_satchel_price = $this->settings['custom_nat_a3_satchel_price'];
				$this->custom_nat_a4_satchel_price = $this->settings['custom_nat_a4_satchel_price'];
				$this->custom_nat_a5_satchel_price = $this->settings['custom_nat_a5_satchel_price'];

				$this->custom_local_satchel_price = $this->settings['custom_local_satchel_price'];
				$this->custom_pink_parcel_price = $this->settings['custom_pink_parcel_price'];
				$this->custom_lime_parcel_price = $this->settings['custom_lime_parcel_price'];
				$this->custom_local_parcel_price = $this->settings['custom_local_parcel_price'];
				$this->custom_parcel_excess_price = $this->settings['custom_parcel_excess_price'];

			}

			function init() {
				$this->init_form_fields();
				$this->init_settings();
				add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
			}
			function init_form_fields() {

				$rfcode = array("" => "Please Select");
				$api_key = "";
				$formsetting = get_option("woocommerce_fastway_au_settings");

				if (is_array($formsetting) && count($formsetting) > 0) {
					$api_key = $formsetting["api_key"];
				}

				if (empty($api_key)) {
					$api_key = "b5056fe957ea82692b615808cfd881bc";
				}

				$rfcode = get_option("rfcode");

				if (!empty($rfcode)) {

					$rfcode = unserialize($rfcode);

				} else {

					if (!empty($api_key)) {
						if (!is_callable('curl_init')) {
							add_action('admin_notices', 'fastway_au_curl_error');
						}

						$url = "http://au.api.fastway.org/latest/psc/listrfs?CountryCode=1&api_key=" . $api_key;

						$handle = curl_init($url);

						curl_setopt($handle, CURLOPT_VERBOSE, true);
						curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
						curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
						curl_setopt($handle, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Accept: application/json'));

						$content = curl_exec($handle);
						$result = json_decode($content); // show target page
						$fastway_error = get_option("fastway_error");

						if (isset($result->error)) {
							if ($fastway_error !== false) {
								update_option("fastway_error", $result->error);
							} else {

								$deprecated = null;
								$autoload = 'no';
								add_option("fastway_error", $result->error, $deprecated, $autoload);
							}
						} else {
							if ($fastway_error !== false) {
								update_option("fastway_error", "");
							} else {

								$deprecated = null;
								$autoload = 'no';
								add_option("fastway_error", "", $deprecated, $autoload);
							}
						}

						if (is_array($result->result)) {
							if (count($result->result) > 0) {
								foreach ($result->result as $v) {
									$rfcode[$v->FranchiseCode] = $v->FranchiseName . "( " . $v->Add1 . " " . $v->Add2 . " " . $v->Add3 . " " . $v->Add4 . " )";
								}
							}
						}

					}

					add_option("rfcode", serialize($rfcode), null, 'no');
				}

				$this->form_fields = array(
					'enabled' => array(
						'title' => __('Enable', 'sk8tech-fastwayau'),
						'type' => 'checkbox',
						'description' => __('Enable this shipping.', 'sk8tech-fastwayau'),
						'default' => 'yes',
					),
					'title' => array(
						'title' => __('Title', 'sk8tech-fastwayau'),
						'type' => 'text',
						'description' => __('Title to be display on site', 'sk8tech-fastwayau'),
						'default' => __('Fastway AU Shipping', 'sk8tech-fastwayau'),
					),
					'combo' => array(
						'title' => __('Combo', 'sk8tech-fastwayau'),
						'type' => 'number',
						'description' => __('No. of products to allow free shipping', 'sk8tech-fastwayau'),
						'default' => 20,
					),
					'twocombozip' => array(
						'title' => __('Two Combo ZIPs', 'sk8tech-fastwayau'),
						'type' => 'text',
						'description' => __('The ZIPs to enjoy free shipping when more than two combo is purchased. Use , to seperate', 'sk8tech-fastwayau'),
						'default' => "",
					),
					'api_key' => array(
						'title' => __('API Key', 'sk8tech-fastwayau'),
						'type' => 'password',
						'description' => __('<a href="http://au.api.fastway.org/latest/docs/page/GetAPIKey.html" target="_blank" style="font-weight:bold;">Get Your Own Fastway API Key</a> or leave as empty', 'sk8tech-fastwayau'),
						'default' => __('', 'sk8tech-fastwayau'),
					),
					'pickup_rfcode' => array(
						'title' => __('Default Franchise', 'sk8tech-fastwayau'),
						'type' => 'select',
						'description' => __('Options will be presented after API Key was filled and saved ', 'sk8tech-fastwayau'),
						'default' => __('', 'sk8tech-fastwayau'),
						'options' => $rfcode,
					),
					'support_type' => array(
						'title' => __('Service Type', 'sk8tech-fastwayau'),
						'type' => 'select',
						'description' => __('', 'sk8tech-fastwayau'),
						'default' => __('', 'sk8tech-fastwayau'),
						'options' => array("" => "All", "Parcel" => "Parcel", "Satchel" => "Satchel"),
					),
					'custom_local_parcel_price' => array(
						'title' => __('Custom Local Parcel Price', 'sk8tech-fastwayau'),
						'type' => 'decimal',
						'description' => __('', 'sk8tech-fastwayau'),
						'default' => __('', 'sk8tech-fastwayau'),
					),
					'custom_lime_parcel_price' => array(
						'title' => __('Custom Lime Parcel Price', 'sk8tech-fastwayau'),
						'type' => 'decimal',
						'description' => __('', 'sk8tech-fastwayau'),
						'default' => __('', 'sk8tech-fastwayau'),
					),
					'custom_pink_parcel_price' => array(
						'title' => __('Custom Pink Parcel Price', 'sk8tech-fastwayau'),
						'type' => 'decimal',
						'description' => __('', 'sk8tech-fastwayau'),
						'default' => __('', 'sk8tech-fastwayau'),
					),
					'custom_red_zone_parcel_price' => array(
						'title' => __('Custom Red Zone Parcel Price', 'sk8tech-fastwayau'),
						'type' => 'decimal',
						'description' => __('', 'sk8tech-fastwayau'),
						'default' => __('', 'sk8tech-fastwayau'),
					),

					'custom_orange_zone_parcel_price' => array(
						'title' => __('Custom Orange Zone Parcel Price', 'sk8tech-fastwayau'),
						'type' => 'decimal',
						'description' => __('', 'sk8tech-fastwayau'),
						'default' => __('', 'sk8tech-fastwayau'),
					),

					'custom_green_zone_parcel_price' => array(
						'title' => __('Custom Green Zone Parcel Price', 'sk8tech-fastwayau'),
						'type' => 'decimal',
						'description' => __('', 'sk8tech-fastwayau'),
						'default' => __('', 'sk8tech-fastwayau'),
					),

					'custom_white_zone_parcel_price' => array(
						'title' => __('Custom White Zone Parcel Price', 'sk8tech-fastwayau'),
						'type' => 'decimal',
						'description' => __('', 'sk8tech-fastwayau'),
						'default' => __('', 'sk8tech-fastwayau'),
					),

					'custom_grey_zone_parcel_price' => array(
						'title' => __('Custom Grey Zone Parcel Price', 'sk8tech-fastwayau'),
						'type' => 'decimal',
						'description' => __('', 'sk8tech-fastwayau'),
						'default' => __('', 'sk8tech-fastwayau'),
					),
					'custom_parcel_excess_price' => array(
						'title' => __('Custom Parcel Excess Price', 'sk8tech-fastwayau'),
						'type' => 'decimal',
						'description' => __('', 'sk8tech-fastwayau'),
						'default' => __('', 'sk8tech-fastwayau'),
					),
					'custom_local_satchel_price' => array(
						'title' => __('Custom Local Satchel Price', 'sk8tech-fastwayau'),
						'type' => 'decimal',
						'description' => __('', 'sk8tech-fastwayau'),
						'default' => __('', 'sk8tech-fastwayau'),
					),
					'custom_nat_a5_satchel_price' => array(
						'title' => __('Custom National A5 Satchel Price', 'sk8tech-fastwayau'),
						'type' => 'decimal',
						'description' => __('', 'sk8tech-fastwayau'),
						'default' => __('', 'sk8tech-fastwayau'),
					),
					'custom_nat_a4_satchel_price' => array(
						'title' => __('Custom National A4 Satchel Price', 'sk8tech-fastwayau'),
						'type' => 'decimal',
						'description' => __('', 'sk8tech-fastwayau'),
						'default' => __('', 'sk8tech-fastwayau'),
					),
					'custom_nat_a3_satchel_price' => array(
						'title' => __('Custom National A3 Satchel Price', 'sk8tech-fastwayau'),
						'type' => 'decimal',
						'description' => __('', 'sk8tech-fastwayau'),
						'default' => __('', 'sk8tech-fastwayau'),
					),
					'custom_nat_a2_satchel_price' => array(
						'title' => __('Custom National A2 Satchel Price', 'sk8tech-fastwayau'),
						'type' => 'decimal',
						'description' => __('', 'sk8tech-fastwayau'),
						'default' => __('', 'sk8tech-fastwayau'),
					),

				);

			}
			public function calculate_shipping($package = array()) {

				$weight = 0;
				$cost = 0;
				$country = $package["destination"]["country"];

				if ($country != "AU") {
					return;
				}

				foreach ($package['contents'] as $item_id => $values) {
					$_product = $values['data'];
					$weight = $weight + $_product->get_weight() * $values['quantity'];
				}

				$weight = wc_get_weight($weight, 'kg');

				if ($weight == 0 || $weight > 25) {
					return;
				}

				$d_suburb = urlencode($package["destination"]["city"]);
				$d_postcode = urlencode($package["destination"]["postcode"]);
				$d_state = urlencode($package["destination"]["state"]);

				if (empty($this->pickup_rfcode) || empty($this->api_key)) {
					return;
				}
				if (empty($d_suburb) || empty($d_postcode) || empty($d_state)) {
					return;
				}

				/**
				 * Auto decide FastWay branch from Address
				 * @author Jack
				 */
				$final_rfcode = $this->pickup_rfcode;
				if ($d_state == "Victoria" || $d_state == "VIC") {
					// All packages destinationed to Victoria should be delivered from Melbourne. Therefore calculate the delivery fee from MEL
					$final_rfcode = "MEL";
				} else if ($d_state == "NSW" ||
					$d_state == "TAS" ||
					$d_state == "ACT") {
					// All packages destinationed to Victoria should be delivered from Melbourne. Therefore calculate the delivery fee from SYD
					$final_rfcode = "SYD";
				} else if ($d_state == "QLD") {
					// All packages destinationed to Victoria should be delivered from Melbourne. Therefore calculate the delivery fee from BRI
					$final_rfcode = "BRI";
				} else {
					// All other places, no delivery offered
					return;
				}

				// Sample Reuqest: http://au.api.fastway.org/v3/psc/lookup/SYD/Ultimo/2007/20?api_key=
				$handle = curl_init($url);
				$url = "http://au.api.fastway.org/v3/psc/lookup/" . $final_rfcode . "/" . $d_suburb . "/" . $d_postcode . "/" . ($weight) . "?api_key=" . $this->api_key;
				$url = str_replace('+', '%20', $url);
				$content = file_get_contents($url);

				$result = json_decode($content); // show target page

				$fastway_error = get_option("fastway_error");

				if (isset($result->error)) {
					if ($fastway_error !== false) {
						update_option("fastway_error", $result->error);

					} else {

						$deprecated = null;
						$autoload = 'no';
						add_option("fastway_error", $result->error, $deprecated, $autoload);
					}
				}
				if (isset($result->result)) {

					if ($fastway_error !== false) {
						update_option("fastway_error", "");

					} else {

						$deprecated = null;
						$autoload = 'no';
						add_option("fastway_error", "", $deprecated, $autoload);
					}
					$parcel_price = 999999;
					$satchel_price = 999999;
					$excess_package = 0;

					$item_count = WC()->cart->get_cart_contents_count();

					if (count($result->result->services) > 0) {

						foreach ($result->result->services as $k => $r) {

							if ($r->type == "Parcel") {

								$tmp_price = "";
								$exc_price = $this->custom_parcel_excess_price;

								if ($r->name == "Local") {
									$tmp_price = $this->custom_local_parcel_price;
								} else {
									if ($r->labelcolour == "LIME") {
										$tmp_price = $this->custom_lime_parcel_price;
									} else if ($r->labelcolour == "PINK") {
										$tmp_price = $this->custom_pink_parcel_price;
									} else if ($r->labelcolour == "RED") {
										$tmp_price = $this->custom_red_zone_parcel_price;
									} else if ($r->labelcolour == "ORANGE") {
										$tmp_price = $this->custom_orange_zone_parcel_price;
									} else if ($r->labelcolour == "GREEN") {
										$tmp_price = $this->custom_green_zone_parcel_price;
									} else if ($r->labelcolour == "WHITE") {
										$tmp_price = $this->custom_white_zone_parcel_price;
									} else if ($r->labelcolour == "GREY") {
										$tmp_price = $this->custom_grey_zone_parcel_price;
									}
								}

								if (is_numeric($tmp_price)) {
									$exc = $r->excess_labels_required;

									if ($exc > 0) {
										if (is_numeric($exc_price) && !empty($exc_price)) {
											$tmp_price = $tmp_price + ($exc_price * $exc);
										} else {
											$tmp_price = $tmp_price + $r->excess_label_price_normal;
										}
									}

									if ($parcel_price > $tmp_price) {
										$parcel_price = $tmp_price;
									}
								}

								if ($parcel_price > $r->totalprice_normal && !is_numeric($tmp_price)) {
									$parcel_price = $r->totalprice_normal;
								}
							}
							if ($r->type == "Satchel") {

								$tmp_price = "";
								if ($r->labelcolour == "SAT-LOC-A3") {
									$tmp_price = $this->custom_local_satchel_price;
								} else
								if ($r->labelcolour == "SAT-NAT-A2") {
									$tmp_price = $this->custom_nat_a2_satchel_price;
								} else
								if ($r->labelcolour == "SAT-NAT-A3") {
									$tmp_price = $this->custom_nat_a3_satchel_price;
								} else
								if ($r->labelcolour == "SAT-NAT-A4") {
									$tmp_price = $this->custom_nat_a4_satchel_price;
								} else
								if ($r->labelcolour == "SAT-NAT-A5") {
									$tmp_price = $this->custom_nat_a5_satchel_price;
								}

								if (is_numeric($tmp_price)) {
									if ($satchel_price > $tmp_price) {
										$satchel_price = $tmp_price;
									}
								}

								if ($satchel_price > $r->totalprice_normal && !is_numeric($tmp_price)) {
									$satchel_price = $r->totalprice_normal;
								}
							}
						}

						if (empty($this->support_type) || $this->support_type == "Parcel") {
							if ($parcel_price != 999999) {

								/**
								 * Use Postcode/ZIP to determine if this method applies
								 * @author Jack
								 */
								$postcode = $package["destination"]["postcode"];

								if (strstr($this->twocombozip, $postcode) === TRUE) {
									// The Shipping post code is not found in the pre-configured zip area.
									// FREE SHIPPING only if item counts exceeds two combo

									if ($item_count >= 2 * $this->combo) {

										$rate = array(
											'id' => $this->id . "-parcel",
											'label' => "FREE! " . $this->title . " - Parcel (" . $result->result->delivery_timeframe_days . " Days) ",
											'cost' => 0,
											'taxes' => false,
										);

										$this->add_rate($rate);

									} else {

										$rate = array(
											'id' => $this->id . "-parcel",
											'label' => $this->title . " - Parcel (" . $result->result->delivery_timeframe_days . " Days) ",
											'cost' => $parcel_price,
											'taxes' => false,
										);

										$this->add_rate($rate);

									}

								} else {
									// The Shipping post code is not found in the pre-configured zip area.
									// FREE SHIPPING only if item counts exceeds one combo

									if ($item_count >= 1 * $this->combo) {

										$rate = array(
											'id' => $this->id . "-parcel",
											'label' => "FREE! " . $this->title . " - Parcel (" . $result->result->delivery_timeframe_days . " Days) ",
											'cost' => 0,
											'taxes' => false,
										);

										$this->add_rate($rate);

									} else {

										$rate = array(
											'id' => $this->id . "-parcel",
											'label' => $this->title . " - Parcel (" . $result->result->delivery_timeframe_days . " Days) ",
											'cost' => $parcel_price,
											'taxes' => false,
										);

										$this->add_rate($rate);

									}
								}
							}
						}

						if (empty($this->support_type) || $this->support_type == "Satchel") {
							if ($satchel_price != 999999) {
								$rate = array(
									'id' => $this->id . "-satchel",
									'label' => $this->title . " - Satchel  (" . $result->result->delivery_timeframe_days . " Days) ",
									'cost' => $satchel_price,
									'taxes' => false,
								);

								$this->add_rate($rate);
							}
						}

					}
				}
			}
		}
	}
}

add_action('woocommerce_shipping_init', 'fastway_au_shipping_method');

function add_fastway_au_shipping_method($methods) {
	$methods[] = 'Fastway_Au_Shipping_Method';
	return $methods;
}

add_filter('woocommerce_shipping_methods', 'add_fastway_au_shipping_method');