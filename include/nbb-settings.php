<?php
/*
 * Setting class
 *
 * @copyright   Copyright (c) 2016, Listen Softwares, Inc
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.3
 *
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
   exit;
}

/**
 * Register our settings
 * @class NBB_Settings
 *
 * @since 3.3
 */
class NBB_Settings {

   /**
    * @const string - Hold the option name for plugin
    */
   CONST NBB_SETTINGS_OPTION_NAME = 'nbb_nifty_cart_secret_key';

   /**
    * Set things up
    *
    * @since 3.3
    */
   public function __construct() {
      add_action( 'admin_init', array( $this, 'init_settings' ) );
   }

   /**
    * White list our options using the Settings API
    *
    * @since 3.3
    */
   public function init_settings() {
      register_setting( 'nbb-settings-nifty-cart', self::NBB_SETTINGS_OPTION_NAME, array( $this, 'validate_api_key' ) );
   }

   /**
    * Validate and sanitize api key
    *
    * @param string $api_key
    * @return string
    * @since 3.3
    */
   public function validate_api_key( $api_key ) {
      return sanitize_text_field( $api_key );
   }

   /**
    * Print admin main menu page contents.
    *
    * @since 3.3
    */
   public static function nbb_nifty_buy_button_menu_page() {
      $api_key = get_option( self::NBB_SETTINGS_OPTION_NAME, '' );
      ?>
      <div class="wrap">
          <h1><?php _e( 'Nifty Buy Button Settings', 'nifty-buy-button' ); ?></h1>
          <?php
          // display messages set via add_settings_error function
          settings_errors();
          ?>
          <form id="nbb_settings_form" method="post" action="options.php">
              <?php
              // adds nonce and option_page fields for the settings page
              settings_fields( 'nbb-settings-nifty-cart' );
              ?>
              <table class="form-table">
                  <tbody>
                      <tr>
                          <th scope="row">
                              <label for="nbb_api_key"><?php _e( 'NiftyCart Secret Key', 'nifty-buy-button' ); ?></label>
                          </th>
                          <td>
                              <input name="<?php echo self::NBB_SETTINGS_OPTION_NAME; ?>" type="text" id="nbb_api_key" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text" />
                              <p class="description" id="niftycart-description"></p>
                          </td>
                      </tr>
                  </tbody>
              </table>
              <?php submit_button(); ?>
          </form>
      </div>
      <?php
   }

}

new NBB_Settings();


