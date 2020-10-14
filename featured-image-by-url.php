<?php
/**
 * Plugin Name:       Featured Image by URL
 * Plugin URI:        https://wordpress.org/plugins/featured-image-by-url/
 * Description:       This plugin allows to use an external URL Images as Featured Image for your post types. Includes support for Product Gallery (WooCommece).
 * Version:           1.1.7
 * Author:            Knawat
 * Author URI:        https://www.knawat.com/?utm_source=wordpress.org&utm_medium=social&utm_campaign=WordPress%20Image%20by%20URL
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       featured-image-by-url
 * Domain Path:       /languages
 *
 * @package     Featured_Image_By_URL
 * @author      Dharmesh Patel <dspatel44@gmail.com>
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) exit;

if( ! class_exists( 'Featured_Image_By_URL' ) ):

/**
* Main Featured Image by URL class
*/
class Featured_Image_By_URL{
	
	/** Singleton *************************************************************/
	/**
	 * Featured_Image_By_URL The one true Featured_Image_By_URL.
	 */
	private static $instance;

    /**
     * Main Featured Image by URL Instance.
     * 
     * Insure that only one instance of Featured_Image_By_URL exists in memory at any one time.
     * Also prevents needing to define globals all over the place.
     *
     * @since 1.0.0
     * @static object $instance
     * @uses Featured_Image_By_URL::setup_constants() Setup the constants needed.
     * @uses Featured_Image_By_URL::includes() Include the required files.
     * @uses Featured_Image_By_URL::laod_textdomain() load the language files.
     * @see run_knawatfibu()
     * @return object| Featured Image by URL the one true Featured Image by URL.
     */
	public static function instance() {
		if( ! isset( self::$instance ) && ! (self::$instance instanceof Featured_Image_By_URL ) ) {
			self::$instance = new Featured_Image_By_URL;
			self::$instance->setup_constants();

			add_action( 'plugins_loaded', array( self::$instance, 'load_textdomain' ) );

			self::$instance->includes();
			self::$instance->admin  = new Featured_Image_By_URL_Admin();
			self::$instance->common = new Featured_Image_By_URL_Common();

		}
		return self::$instance;	
	}

	/** Magic Methods *********************************************************/

	/**
	 * A dummy constructor to prevent Featured_Image_By_URL from being loaded more than once.
	 *
	 * @since 1.0.0
	 * @see Featured_Image_By_URL::instance()
	 * @see run_knawatfibu()
	 */
	private function __construct() { /* Do nothing here */ }

	/**
	 * A dummy magic method to prevent Featured_Image_By_URL from being cloned.
	 *
	 * @since 1.0.0
	 */
	public function __clone() { _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'featured-image-by-url' ), '1.1.4' ); }

	/**
	 * A dummy magic method to prevent Featured_Image_By_URL from being unserialized.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() { _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'featured-image-by-url' ), '1.1.4' ); }


	/**
	 * Setup plugins constants.
	 *
	 * @access private
	 * @since 1.0.0
	 * @return void
	 */
	private function setup_constants() {

		// Plugin version.
		if( ! defined( 'KNAWATFIBU_VERSION' ) ){
			define( 'KNAWATFIBU_VERSION', '1.1.4' );
		}

		// Plugin folder Path.
		if( ! defined( 'KNAWATFIBU_PLUGIN_DIR' ) ){
			define( 'KNAWATFIBU_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		}

		// Plugin folder URL.
		if( ! defined( 'KNAWATFIBU_PLUGIN_URL' ) ){
			define( 'KNAWATFIBU_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		}

		// Plugin root file.
		if( ! defined( 'KNAWATFIBU_PLUGIN_FILE' ) ){
			define( 'KNAWATFIBU_PLUGIN_FILE', __FILE__ );
		}

		// Options
		if( ! defined( 'KNAWATFIBU_OPTIONS' ) ){
			define( 'KNAWATFIBU_OPTIONS', 'knawatfibu_options' );
		}

		// gallary meta key
		if( ! defined( 'KNAWATFIBU_WCGALLARY' ) ){
			define( 'KNAWATFIBU_WCGALLARY', '_knawatfibu_wcgallary' );
		}

	}

	/**
	 * Include required files.
	 *
	 * @access private
	 * @since 1.0.0
	 * @return void
	 */
	private function includes() {
		require_once KNAWATFIBU_PLUGIN_DIR . 'includes/class-featured-image-by-url-admin.php';
		require_once KNAWATFIBU_PLUGIN_DIR . 'includes/class-featured-image-by-url-common.php';
	}

	
	/**
	 * Loads the plugin language files.
	 * 
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public function load_textdomain(){

		load_plugin_textdomain(
			'featured-image-by-url',
			false,
			basename( dirname( __FILE__ ) ) . '/languages'
		);
	
	}
	
}

endif; // End If class exists check.

/**
 * The main function for that returns Featured_Image_By_URL
 *
 * The main function responsible for returning the one true Featured_Image_By_URL
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $knawatfibu = run_knawatfibu(); ?>
 *
 * @since 1.0.0
 * @return object|Featured_Image_By_URL The one true Featured_Image_By_URL Instance.
 */
function run_knawatfibu() {
	return Featured_Image_By_URL::instance();
}

// Get Featured_Image_By_URL Running.
$GLOBALS['knawatfibu'] = run_knawatfibu();
