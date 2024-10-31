<?php
/*
  Plugin Name: Nifty Buy Button
  Plugin URI: http://www.niftycart.com/plugin.htm
  Description: You can easily add Buy Now Buttons for items you are selling on your WordPress site. You choose colors and size for the Buy Now button and paste the link you get from your NiftyCart.com shopping cart. You are selling online in minutes.
  Version: 3.5.4
  Author: NiftyCart.com
  Author URI: https://www.niftycart.com
  Text Domain: nifty-buy-button
  License: GPLv2 or later
  ----------------------------------------------------------------------
  Copyright 2019 Listening Software Inc.
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

function nbb_logger( $message ) {
	if ( WP_DEBUG === true ) {
		if ( is_array( $message ) || is_object( $message ) ) {
			error_log( print_r( $message, true ) );
		} else {
			error_log( $message );
		}
	}
}

define( 'NIFTY_BUY_BUTTON_VERSION', '3.5' );
define( 'NIFTY_BUY_VERSION_ROOT', dirname( __FILE__ ) );
define( 'NIFTY_BUY_BUTTON_PATH', plugin_dir_path( __FILE__ ) );
define( 'NIFTY_BUY_BUTTON_URL', plugins_url( '/', __FILE__ ) );
define( 'NBB_SECURE_CART_API_URI', 'https://www.myniftycart.com/api/api/Main/CatalogItemsByApiKey?apikey=' );
define( 'NBB_ORDER_NOW_BUTTON_API', 'https://www.myniftycart.com/ob' );

if ( !class_exists( 'Nifty_Buy_Button' ) ) {

	/**
	 * This class will set the plugin.
	 *
	 * @class Nifty_Buy_Button_Init
	 * @since 3.3
	 */
	class Nifty_Buy_Button {

		private $current_screen_pointers = array( );

		/**
		 * Set things up
		 *
		 * @since 3.3
		 */
		public function __construct() {

			$this->include_classes();

			//run on activation of plugin
			register_activation_hook( __FILE__, array( $this, 'on_nbb_activation' ) );
			//run on deactivation of plugin
			register_deactivation_hook( __FILE__, array( $this, 'on_nbb_deactivation' ) );

			// Register Nifty buy button on editor
			add_action( 'admin_head', array( $this, 'register_nbb_blurb_to_mce_editor' ) );

			// register admin menu
			add_action( 'admin_menu', array( $this, 'register_nbb_admin_menu' ) );
			add_shortcode( 'niftybuybutton', array( $this, 'do_nbb_nifty_buy_button_shortcode' ) );
			add_shortcode( 'niftycart', array( $this, 'do_niftycart_shortcode' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'show_welcome_message_pointers' ) );

			add_action( 'admin_footer', array( $this, 'load_js_files_footer' ) );
		}

		/**
		 * Include required files
		 *
		 * @since 3.3
		 */
		public function include_classes() {
			require_once 'include/nbb-settings.php';
		}

		/**
		 * Set the things on plugin activation
		 *
		 * @since 3.3
		 */
		public function on_nbb_activation() {
			// do nothing
		}

		/**
		 * Set the things on plugin deactivation
		 *
		 * @since 3.3
		 */
		public function on_nbb_deactivation() {
			// do nothing
		}

		/**
		 * Init process for registering our blurb to editor
		 *
		 * @return void
		 * @since 3.3
		 */
		public function register_nbb_blurb_to_mce_editor() {

			// Check if user has permission
			if ( !current_user_can( 'edit_posts' ) && !current_user_can( 'edit_pages' ) && true === (bool) get_user_option( 'rich_editing' ) ) {
				return;
			}

			$settings = get_option( NBB_Settings::NBB_SETTINGS_OPTION_NAME );
			if ( empty( $settings ) ) {
				return;
			}

			add_filter( 'mce_external_plugins', array( $this, 'register_nbb_tinymce_plugin_js' ) );
			//add_filter( 'mce_buttons', array( $this, 'push_nbb_blurb_on_tinymce' ) );

			// enqueue required scripts and styles
			wp_enqueue_style( 'nbb-main-style', NIFTY_BUY_BUTTON_URL . 'assets/css/nifty-buy-button.css', NIFTY_BUY_BUTTON_VERSION, true );
			wp_enqueue_script( 'nbb-nifty-buy-button-js', NIFTY_BUY_BUTTON_URL . 'assets/js/nifty-buy-button.js', array( 'jquery' ), NIFTY_BUY_BUTTON_VERSION, true );
			// get all products for nifty cart
			$catalog_api = NBB_SECURE_CART_API_URI . get_option( NBB_Settings::NBB_SETTINGS_OPTION_NAME );
			// Get the remote site
			$response = wp_remote_get( $catalog_api );

			// check for error
			if ( is_wp_error( $response ) ) {
				return sprintf( 'The URL %s could not be retrieved', $catalog_api );
			}

			// get the body
			$data = wp_remote_retrieve_body( $response );
			if ( !is_wp_error( $data ) ) {
				$data = json_decode( $data );

				if ( $data ) {
					foreach ( $data as $key => $product ) {
						$product->text = $product->Name;
						$product->value = (string) $product->ItemId;
						unset( $product->ItemId, $product->Name );
					}
				}
			}
			$data = (array) $data;

			/**
			 * Our popup contents will go here as TinyMCE does not allow select as multiple so we are showing
			 * custom HTML form so that its allows user to select multiple products..!
			 * 
			 * @since 3.4
			 */
			// here we require display none to prevent to show on listing posts/pages
			?>
			
			<?php /*
			<div id="nbb-link-backdrop" style="display: none;"></div>
			<div id="nbb-link-wrap" style="display: none;" class="wp-core-ui" role="dialog" aria-labelledby="link-modal-title">
				<form id="niftycart-form">
					<?php wp_nonce_field( 'internal-linking', '_ajax_linking_nonce', false ); ?>
					<h1 id="nbb-modal-title" style=""><?php _e( 'Nifty Cart Items' ) ?></h1>
					<button type="button" id="niftycart-upsert-close" class="niftycart-upsert-close"></button>
					<div id="link-selector">
						<div id="link-options">
							<div id="select-products">
								<label><span><?php _e( 'Select the product', 'nifty-buy-button' ); ?></span>
									<select class="niftycart-products" multiple="multiple" style="width: 330px;">
										<?php foreach ( $data as $product ): ?>
											<option value="<?php echo (int) $product->value; ?>"><?php echo esc_attr( $product->text ); ?></option>
										<?php endforeach; ?>
									</select>
									<p class="description"><?php _e( 'You can add multiple Product(s) to shortcode.', 'nifty-buy-button' ); ?></p>
								</label>
							</div>
						</div>
					</div>
					<div class="submitbox">
						<div id="niftycart-upsert-cancel" class="niftycart-upsert-cancel">
							<button type="button" class="button niftycart-upsert-close"><?php _e( 'Cancel' ); ?></button>
						</div>
						<div id="niftycart-upsert-update">
							<input type="button" value="<?php esc_attr_e( 'Add Shortcode', 'nifty-buy-button' ); ?>" class="button button-primary" id="niftycart-upsert-submit" name="wp-link-submit">
						</div>
					</div>
				</form>
			</div>
			*/?>
			
			<?php
		}

		/**
		 * Register TinyMCE plugin js
		 *
		 * @param array $plugin_array tinyMCE plugins
		 * @return array
		 * @since 3.3
		 */
		public function register_nbb_tinymce_plugin_js( $plugin_array ) {

			$plugin_array['nbbNiftyBuyButtomPlugin'] = NIFTY_BUY_BUTTON_URL . 'assets/js/nbb-mce-editor-plugin.js';
			return $plugin_array;
		}

		/**
		 * Push the button into default editor's buttons array
		 *
		 * @param array $button Default editor's buttons
		 * @return array
		 * @since 3.3
		 */
		public function push_nbb_blurb_on_tinymce( $buttons ) {
			array_push( $buttons, 'nbbNiftyBuyButtomPlugin' );
			return $buttons;
		}

		/**
		 * Register admin menu
		 *
		 * @since 3.3
		 */
		public function register_nbb_admin_menu() {
			add_menu_page(
					  __( 'Nifty Buy Button', 'nifty-buy-button' ),
					  __( 'Nifty Buy Button', 'nifty-buy-button' ),
					  'manage_options',
					  'nifty-buy-button',
					  array( 'NBB_Settings', 'nbb_nifty_buy_button_menu_page' ),
					  'dashicons-editor-kitchensink',
					  26
					  
					  
			);
			
			add_submenu_page(
						'nifty-buy-button',
					  __( 'Settings', 'nifty-buy-button' ),
					  __( 'Settings', 'nifty-buy-button' ),
					  'manage_options', 
					  'nifty-buy-button',
					  array( 'NBB_Settings', 'nbb_nifty_buy_button_menu_page' ),
					  26,
					  'dashicons-editor-kitchensink'
					  
			);
			
			add_submenu_page(
						'nifty-buy-button',
					  __( 'Generate Shortcode', 'nifty-buy-button-sg' ),
					  __( 'Generate Shortcode', 'nifty-buy-button-sg' ),
					  'manage_options', 
					  'nifty-buy-button-sg',
					  array( $this, 'generate_shortcode' ),
					  26,
					  'dashicons-editor-kitchensink'
					  
			);
		}
		
		/**
		 * Generate the products ShortCode
		 * 
		 * @param ''
		 * @return Print ShortCode
		 */
		public function generate_shortcode()
		{
			if (get_option(NBB_Settings::NBB_SETTINGS_OPTION_NAME)  == '') {
				echo '<br><br><br><strong>NiftyCart Secret Key is missing.</strong>';
				return ;
			}
			
			// get all products for nifty cart
			$catalog_api = NBB_SECURE_CART_API_URI . get_option( NBB_Settings::NBB_SETTINGS_OPTION_NAME );
			// Get the remote site
			$response = wp_remote_get( $catalog_api );

			// check for error
			if ( is_wp_error( $response ) ) {
				return sprintf( 'The URL %s could not be retrieved', $catalog_api );
			}

			// get the body
			$data = wp_remote_retrieve_body( $response );
			if ( !is_wp_error( $data ) ) {
				$data = json_decode( $data );

				if ( $data ) {
					foreach ( $data as $key => $product ) {
						$product->text = $product->Name;
						$product->value = (string) $product->ItemId;
						unset( $product->ItemId, $product->Name );
					}
				}
			}
			$data = (array) $data;
			
			?>
			<div id="nbb-link-wrap" style="display:block" class="wp-core-ui" role="dialog" aria-labelledby="link-modal-title">
				<form id="niftycart-form">
					<?php wp_nonce_field( 'internal-linking', '_ajax_linking_nonce', false ); ?>
					<h1 id="nbb-modal-title" style=""><?php _e( 'Nifty Cart Items' ) ?></h1>
					<button type="button" id="niftycart-upsert-close" class="niftycart-upsert-close"></button>
					<div id="link-selector">
						<div id="link-options">
							<div id="select-products">
								<label><span><?php _e( 'Select the product', 'nifty-buy-button' ); ?></span>
									<select class="niftycart-products" multiple="multiple" style="width: 330px;">
										<?php foreach ( $data as $product ): ?>
											<option value="<?php echo (int) $product->value; ?>"><?php echo esc_attr( $product->text ); ?></option>
										<?php endforeach; ?>
									</select>
									<p class="description"><?php _e( 'You can add multiple Product(s) to shortcode.', 'nifty-buy-button' ); ?></p>
								</label>
							</div>
						</div>
					</div>
					<div class="submitbox">
						<div id="niftycart-upsert-cancel" class="niftycart-upsert-cancel">
							<button type="button" class="button niftycart-upsert-close"><?php _e( 'Cancel' ); ?></button>
						</div>
						<div id="niftycart-upsert-update">
							<input type="button" value="<?php esc_attr_e( 'Add Shortcode', 'nifty-buy-button' ); ?>" class="button button-primary" id="niftycart-add-shortcode" name="wp-link-submit">
						</div>
					</div>
				</form>
			</div>
			
			<div id="get-shortcode">
				<p>
					<span style="font-weight:bold">ShortCode : </span>
					<span id="display-shortcode" class="display-shortcode"></span>
				</p>
				<br>
				<p> 
					<span id="regenerate-shortcode" class="button button-primary">Regenerate Shortcode</span>
					<span id="copy-shortcode" class="button button-primary">Copy Shortcode</span>
					<span id="copy-msg"></span>
				</p>
			</div>
			
			<script>
			jQuery(document).ready(function()
			{
				jQuery('#niftycart-add-shortcode').click(function () {
                    if( jQuery('.niftycart-products').select2('val') == '' ){
                        return false;
                    }
                    var niftycart_shortcode = '[niftycart products="' + jQuery('.niftycart-products').select2('val') + '"]';
					jQuery('.display-shortcode').html(niftycart_shortcode);
					jQuery('#get-shortcode').show();
                    jQuery('.niftycart-upsert-close').trigger('click');
                });
				
				
				jQuery('#regenerate-shortcode').click(function () {
					location.reload();
				});
				
				jQuery('#copy-shortcode').click(function () {
					// Create a "hidden" input
                      var aux = document.createElement("input");

                      aux.setAttribute("value", document.getElementById('display-shortcode').innerHTML);
                      // Append it to the body
                      document.body.appendChild(aux);
                      // Highlight its content
                      aux.select();
                      // Copy the highlighted text
                      document.execCommand("copy");
                      // Remove it from the body
                      document.body.removeChild(aux);
					  
					  jQuery("#copy-shortcode").html('Shortcode Copied!');
				});
			});
			
			</script>
			<?php
		}

		/**
		 * Return the product contents
		 * 
		 * @param array $atts
		 * @return HTML string
		 */
		public function do_nbb_nifty_buy_button_shortcode( $atts ) {
			$atts = shortcode_atts( array(
				 'url' => NBB_ORDER_NOW_BUTTON_API . '?id=10',
				 'align' => 'center',
				 'wrap' => 'no'
					  ), $atts, 'bartag' );

			if ( empty( $atts['url'] ) ) {
				$atts['url'] = NBB_ORDER_NOW_BUTTON_API . '?id=10';
			}
			$product_api = $atts['url'];
			$box_alignment = $atts['align'];

			$html = $clearfix = '';
			if ( 'center' === $box_alignment ) {
				$clearfix = 'clear: both;';
			}

			if ( $atts['wrap'] != 'yes' ) {
				$html .= "<div style='width:100%;$clearfix text-align:$box_alignment'>";
			}

			$html .= "<iframe class='my-iframe' src='$product_api' style='height: 435px;border:none;text-align:$box_alignment'>";
			$html .= '</iframe>';

			if ( $atts['wrap'] != 'yes' ) {
				$html .= '</div>';
			}

			return $html;
		}

		/**
		 * Process the chosen product into iFrame
		 * 
		 * @param array $atts
		 * @return HTML string
		 * @since 3.4
		 */
		public function do_niftycart_shortcode( $atts ) {
			$product_ids = explode( ",", $atts['products'] );

			$html = '';
			$html = apply_filters( 'nbb_before_niftycart_products', $html );
			
			foreach ( $product_ids as $key => $product_id ) {
				$html .= "<div style='width:276px;float:left;' class='nifty-products'>";
				$product_api = NBB_ORDER_NOW_BUTTON_API . '?id=' . $product_id;
				$html .= "<iframe id='nbb-niftycart-$key' class='my-iframe' src='$product_api' style='height: 460px;border:none;' onload=''>";
				$html .= '</iframe>';

				$html .= '</div>';
			}
			
			$html .= '';
			$html = apply_filters( 'nbb_after_niftycart_products', $html );
			
			return $html;
		}

		/**
		 * Retrieves pointers for the current admin screen. Use the 'owf_admin_pointers' hook to add your own pointers.
		 *
		 * @return array Current screen pointers
		 * @since 3.3
		 */
		private function get_current_screen_pointers() {
			$pointers = '';
			$screen = get_current_screen();
			$screen_id = $screen->id;

			$welcome_title = __( 'Welcome to Nifty buy button', 'nifty-buy-button' );
			$blurb_html = '<img src="' . NIFTY_BUY_BUTTON_URL . 'assets/images/nbb-logo.png" style="border:0px;" />';
			$welcome_message_1 = __( 'To get started, activate the plugin by providing a valid nifty cart secret key on Nifty buy button menu.', 'nifty-buy-button' );
			$welcome_message_2 = sprintf( __( "Once the activated you will see a blurb button %s in the Post Editor's TinyMCE toolbar.", 'nifty-buy-button' ), $blurb_html );
			$welcome_message_3 = __( 'Click on the blurb icon and start adding nifty product shortcode.', 'nifty-buy-button' );

			$default_pointers = array(
				 'plugins' => array(
					  'nbb_nifty_buy_button_install' => array(
							'target' => '#toplevel_page_nifty-buy-button',
							'content' => '<h3>' . $welcome_title . '</h3> <p>' . $welcome_message_1 . '</p><p>' . $welcome_message_2 . '</p><p>' .
							$welcome_message_3 . '</p>',
							'position' => array( 'edge' => 'left', 'align' => 'center' ),
					  )
				 )
			);

			if ( !empty( $default_pointers[$screen_id] ) ) {
				$pointers = $default_pointers[$screen_id];
			}

			return apply_filters( 'nbb_nifty_admin_pointers', $pointers, $screen_id );
		}

		/**
		 * Show the welcome message on plugin activation.
		 *
		 * @since 3.3
		 */
		public function show_welcome_message_pointers() {
			// Don't run on WP < 3.3
			if ( get_bloginfo( 'version' ) < '3.3' ) {
				return;
			}

			// only show this message to the users who can activate plugins
			if ( !current_user_can( 'activate_plugins' ) ) {
				return;
			}

			$pointers = $this->get_current_screen_pointers();
			// No pointers? Don't do anything
			if ( empty( $pointers ) || !is_array( $pointers ) ) {
				return;
			}

			// Get dismissed pointers.
			// Note : dismissed pointers are stored by WP in the "dismissed_wp_pointers" user meta.
			$dismissed = explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );
			$valid_pointers = array( );

			// Check pointers and remove dismissed ones.
			foreach ( $pointers as $pointer_id => $pointer ) {
				if ( in_array( $pointer_id, $dismissed ) || empty( $pointer ) || empty( $pointer_id ) || empty( $pointer['target'] ) || empty( $pointer['content'] ) ) {
					continue;
				}

				// Add the pointer to $valid_pointers array
				$valid_pointers[$pointer_id] = $pointer;
			}

			// No valid pointers? Stop here.
			if ( empty( $valid_pointers ) ) {
				return;
			}

			// Set our class variable $current_screen_pointers
			$this->current_screen_pointers = $valid_pointers;

			// Add our javascript to handle pointers
			add_action( 'admin_print_footer_scripts', array( $this, 'display_pointers' ) );

			// Add pointers style and javascript to queue.
			wp_enqueue_style( 'wp-pointer' );
			wp_enqueue_script( 'wp-pointer' );
		}

		/**
		 * Finally prints the javascript that'll make our pointers alive.
		 *
		 * @since 3.3
		 */
		public function display_pointers() {
			if ( !empty( $this->current_screen_pointers ) ) {
				?>
				<script type="text/javascript">// <![CDATA[
					jQuery( document ).ready( function( $ ) {
						if ( typeof ( jQuery().pointer ) != 'undefined' ) {
				<?php foreach ( $this->current_screen_pointers as $pointer_id => $data ): ?>
								$( '<?php echo $data['target'] ?>' ).pointer( {
									content: '<?php echo addslashes( $data['content'] ) ?>',
									position: {
										edge: '<?php echo addslashes( $data['position']['edge'] ) ?>',
										align: '<?php echo addslashes( $data['position']['align'] ) ?>'
									},
									close: function() {
										$.post( ajaxurl, {
											pointer: '<?php echo addslashes( $pointer_id ) ?>',
											action: 'dismiss-wp-pointer'
										} );
									}
								} ).pointer( 'open' );
				<?php endforeach; ?>
						}
					} );
					// ]]></script>
				<?php
			}
		}

		/**
		 * Enqueue select2 js and css
		 * 
		 * @since 3.4
		 */
		public function load_js_files_footer() {
			wp_enqueue_style( 'select2-style', NIFTY_BUY_BUTTON_URL . 'assets/css/select2/select2.css', false, NIFTY_BUY_BUTTON_VERSION, 'all' );
			wp_enqueue_script( 'select2-js', NIFTY_BUY_BUTTON_URL . 'assets/js/select2/select2.min.js', array( 'jquery' ), NIFTY_BUY_BUTTON_VERSION, true );
		}

	}

}

new Nifty_Buy_Button();
