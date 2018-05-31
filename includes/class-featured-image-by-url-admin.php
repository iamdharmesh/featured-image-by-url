<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package     Featured_Image_By_URL
 * @subpackage  Featured_Image_By_URL/admin
 * @copyright   Copyright (c) 2018, Knawat
 * @since       1.0.0
 */
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * The admin-specific functionality of the plugin.
 *
 * @package     Featured_Image_By_URL
 * @subpackage  Featured_Image_By_URL/admin
 */
class Featured_Image_By_URL_Admin {

	public $image_meta_url = '_knawatfibu_url';
	public $image_meta_alt = '_knawatfibu_alt';

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( is_admin() ){
			add_action( 'add_meta_boxes', array( $this, 'knawatfibu_add_metabox' ), 10, 2 );
			add_action( 'save_post', array( $this, 'knawatfibu_save_image_url_data' ), 10, 2 );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles') );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts') );
			add_action( 'admin_menu', array( $this, 'knawatfibu_add_options_page' ) );
			add_action( 'admin_init', array( $this, 'knawatfibu_settings_init' ) );
			// Add & Save Product Variation Featured image by URL.
			add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'knawatfibu_add_product_variation_image_selector' ), 10, 3 );
			add_action( 'woocommerce_save_product_variation', array( $this, 'knawatfibu_save_product_variation_image' ), 10, 2 );
		}
	}

	/**
	 * Add Meta box for Featured Image by URL.
	 *
	 * @since 1.0
	 * @return void
	 */
	function knawatfibu_add_metabox( $post_type, $post ) {
		
		$options = get_option( KNAWATFIBU_OPTIONS );
		$disabled_posttypes = isset( $options['disabled_posttypes'] ) ? $options['disabled_posttypes']  : array();
		if( in_array( $post_type, $disabled_posttypes ) ){
			return;
		}

		add_meta_box( 'knawatfibu_metabox',
						__('Featured Image by URL', 'featured-image-by-url' ), 
						array( $this, 'knawatfibu_render_metabox' ),
						$this->knawatfibu_get_posttypes(),
						'side',
						'low'
					);

		add_meta_box( 'knawatfibu_wcgallary_metabox',
						__('Product gallery by URLs', 'featured-image-by-url' ), 
						array( $this, 'knawatfibu_render_wcgallary_metabox' ),
						'product',
						'side',
						'low'
					);

	}

	/**
	 * Render Meta box for Featured Image by URL.
	 *
	 * @since 1.0
	 * @return void
	 */
	function knawatfibu_render_metabox(  $post ) {
		
		$image_meta = $this->knawatfibu_get_image_meta(  $post->ID );

		// Include Metabox Template.
		include KNAWATFIBU_PLUGIN_DIR .'templates/knawatfibu-metabox.php';

	}

	/**
	 * Render Meta box for Product gallary by URLs
	 *
	 * @since 1.0
	 * @return void
	 */
	function knawatfibu_render_wcgallary_metabox(  $post ) {
		
		// Include WC Gallary Metabox Template.
		include KNAWATFIBU_PLUGIN_DIR .'templates/knawatfibu-wcgallary-metabox.php';

	}

	/**
	 * Load Admin Styles.
	 *
	 * Enqueues the required admin styles.
	 *
	 * @since 1.0
	 * @param string $hook Page hook
	 * @return void
	 */
	function enqueue_admin_styles( $hook ) {
		
		$css_dir = KNAWATFIBU_PLUGIN_URL . 'assets/css/';
	 	wp_enqueue_style('knawatfibu-admin', $css_dir . 'featured-image-by-url-admin.css', false, "" );
		
	}

	/**
	 * Load Admin Scripts.
	 *
	 * Enqueues the required admin scripts.
	 *
	 * @since 1.0
	 * @param string $hook Page hook
	 * @return void
	 */
	function enqueue_admin_scripts( $hook ) {

		$js_dir  = KNAWATFIBU_PLUGIN_URL . 'assets/js/';
		wp_register_script( 'knawatfibu-admin', $js_dir . 'featured-image-by-url-admin.js', array('jquery' ) );
		$knawat_strings = array(
			'invalid_image_url' => __('Error in Image URL', 'featured-image-by-url'),
		);
		wp_localize_script( 'knawatfibu-admin', 'knawatfibujs', $knawat_strings );
		wp_enqueue_script( 'knawatfibu-admin' );

	}

	/**
	 * Add Meta box for Featured Image by URL.
	 *
	 * @since 1.0
	 * @return void
	 */
	function knawatfibu_save_image_url_data( $post_id, $post ) {

		$cap = $post->post_type === 'page' ? 'edit_page' : 'edit_post';
		if ( ! current_user_can( $cap, $post_id ) || ! post_type_supports( $post->post_type, 'thumbnail' ) || defined( 'DOING_AUTOSAVE' ) ) {
			return;
		}

		if( isset( $_POST['knawatfibu_url'] ) ){
			global $knawatfibu;
			// Update Featured Image URL
			$image_url = isset( $_POST['knawatfibu_url'] ) ? esc_url( $_POST['knawatfibu_url'] ) : '';
			$image_alt = isset( $_POST['knawatfibu_alt'] ) ? wp_strip_all_tags( $_POST['knawatfibu_alt'] ): '';

			if ( $image_url != '' ){
				if( get_post_type( $post_id ) == 'product' ){
					$img_url = get_post_meta( $post_id, $this->image_meta_url , true );
					if( is_array( $img_url ) && isset( $img_url['img_url'] ) && $image_url == $img_url['img_url'] ){
							$image_url = array(
								'img_url' => $image_url,
								'width'	  => $img_url['width'],
								'height'  => $img_url['height']
							);
					}else{
						$imagesize = @getimagesize( $image_url );
						$image_url = array(
							'img_url' => $image_url,
							'width'	  => isset( $imagesize[0] ) ? $imagesize[0] : '',
							'height'  => isset( $imagesize[1] ) ? $imagesize[1] : ''
						);
					}
				}

				update_post_meta( $post_id, $this->image_meta_url, $image_url );
				if( $image_alt ){
					update_post_meta( $post_id, $this->image_meta_alt, $image_alt );
				}
			}else{
				delete_post_meta( $post_id, $this->image_meta_url );
				delete_post_meta( $post_id, $this->image_meta_alt );
			}
		}

		if( isset( $_POST['knawatfibu_wcgallary'] ) ){
			// Update WC Gallery
			$knawatfibu_wcgallary = isset( $_POST['knawatfibu_wcgallary'] ) ? $_POST['knawatfibu_wcgallary'] : '';
			if( empty( $knawatfibu_wcgallary ) || $post->post_type != 'product' ){
				return;
			}

			$old_images = $knawatfibu->common->knawatfibu_get_wcgallary_meta( $post_id );
			if( !empty( $old_images ) ){
				foreach ($old_images as $key => $value) {
					$old_images[$value['url']] = $value;
				}
			}

			$gallary_images = array();
			if( !empty( $knawatfibu_wcgallary ) ){
				foreach ($knawatfibu_wcgallary as $knawatfibu_gallary ) {
					if( isset( $knawatfibu_gallary['url'] ) && $knawatfibu_gallary['url'] != '' ){
						$gallary_image = array();
						$gallary_image['url'] = $knawatfibu_gallary['url'];

						if( isset( $old_images[$gallary_image['url']]['width'] ) && $old_images[$gallary_image['url']]['width'] != '' ){
							$gallary_image['width'] = isset( $old_images[$gallary_image['url']]['width'] ) ? $old_images[$gallary_image['url']]['width'] : '';
							$gallary_image['height'] = isset( $old_images[$gallary_image['url']]['height'] ) ? $old_images[$gallary_image['url']]['height'] : '';

						}else{
							$imagesizes = @getimagesize( $knawatfibu_gallary['url'] );
							$gallary_image['width'] = isset( $imagesizes[0] ) ? $imagesizes[0] : '';
							$gallary_image['height'] = isset( $imagesizes[1] ) ? $imagesizes[1] : '';
						}

						$gallary_images[] = $gallary_image;
					}
				}
			}

			if( !empty( $gallary_images ) ){
				update_post_meta( $post_id, KNAWATFIBU_WCGALLARY, $gallary_images );
			}else{
				delete_post_meta( $post_id, KNAWATFIBU_WCGALLARY );
			}
		}
	}

	/**
	 * Get Image metadata by post_id
	 *
	 * @since 1.0
	 * @return void
	 */
	function knawatfibu_get_image_meta( $post_id, $is_single_page = false ){
		
		$image_meta  = array();

		$img_url = get_post_meta( $post_id, $this->image_meta_url, true );
		$img_alt = get_post_meta( $post_id, $this->image_meta_alt, true );
		
		if( is_array( $img_url ) && isset( $img_url['img_url'] ) ){
			$image_meta['img_url'] 	 = $img_url['img_url'];	
		}else{
			$image_meta['img_url'] 	 = $img_url;
		}
		$image_meta['img_alt'] 	 = $img_alt;
		if( ( 'product_variation' == get_post_type( $post_id ) || 'product' == get_post_type( $post_id ) ) && $is_single_page ){
			if( isset( $img_url['width'] ) ){
				$image_meta['width'] 	 = $img_url['width'];
				$image_meta['height'] 	 = $img_url['height'];
			}else{

				if( isset( $image_meta['img_url'] ) && $image_meta['img_url'] != '' ){
					$imagesize = @getimagesize( $image_meta['img_url'] );
					$image_url = array(
						'img_url' => $image_meta['img_url'],
						'width'	  => isset( $imagesize[0] ) ? $imagesize[0] : '',
						'height'  => isset( $imagesize[1] ) ? $imagesize[1] : ''
					);
					update_post_meta( $post_id, $this->image_meta_url, $image_url );
					$image_meta = $image_url;	
				}				
			}
		}
		return $image_meta;
	}

	/**
	 * Adds Settings Page
	 *
	 * @since 1.0
	 * @return array
	 */
	function knawatfibu_add_options_page() {
		 add_options_page( __('Featured Image by URL', 'featured-image-by-url' ), __('Featured Image by URL', 'featured-image-by-url' ), 'manage_options', 'knawatfibu', array( $this, 'knawatfibu_options_page_html' ) );
	}

	/**
	 * Settings Page HTML
	 *
	 * @since 1.0
	 * @return array
	 */
	function knawatfibu_options_page_html() {
		// check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				// output security fields for the registered setting "knawatfibu"
				settings_fields( 'knawatfibu' );
				
				// output setting sections and their fields
				do_settings_sections( 'knawatfibu' );
				
				// output save settings button
				submit_button( 'Save Settings' );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Register custom settings, Sections & fields
	 *
	 * @since 1.0
	 * @return array
	 */
	function knawatfibu_settings_init() {
		register_setting( 'knawatfibu', KNAWATFIBU_OPTIONS );
 
		add_settings_section(
			'knawatfibu_section',
			__( 'Settings', 'featured-image-by-url' ),
			array( $this, 'knawatfibu_section_callback' ),
			'knawatfibu'
		);
 
		// register a new field in the "knawatfibu_section" section, inside the "knawatfibu" page
		add_settings_field(
			'disabled_posttypes',
			__( 'Disable Post types', 'featured-image-by-url' ),
			array( $this, 'disabled_posttypes_callback' ),
			'knawatfibu',
			'knawatfibu_section',
			array(
				'label_for' => 'disabled_posttypes',
				'class' 	=> 'knawatfibu_row',
			)
		);

		add_settings_field(
			'resize_images',
			__( 'Display Resized Images', 'featured-image-by-url' ),
			array( $this, 'resize_images_callback' ),
			'knawatfibu',
			'knawatfibu_section',
			array(
				'label_for' => 'resize_images',
				'class' 	=> 'knawatfibu_row',
			)
		);
	}

	/**
	 * Callback function for knawatfibu section.
	 *
	 * @since 1.0
	 * @return array
	 */
	function knawatfibu_section_callback( $args ) {
		// Do some HTML here.
	}

	/**
	 * Callback function for disabled_posttypes field.
	 *
	 * @since 1.0
	 * @return array
	 */
	function disabled_posttypes_callback( $args ) {
		// get the value of the setting we've registered with register_setting()
		global $wp_post_types;
		
		$options = get_option( KNAWATFIBU_OPTIONS );
		$post_types = $this->knawatfibu_get_posttypes( true );
		$disabled_posttypes = isset( $options['disabled_posttypes'] ) ? $options['disabled_posttypes']  : array();

		if( !empty( $post_types ) ){
			foreach ($post_types as $key => $post_type ) {
				?>
				<label for="<?php echo $key; ?>" style="display: block;">
		            <input name="<?php echo KNAWATFIBU_OPTIONS.'['. esc_attr( $args['label_for'] ).']'; ?>[]" class="disabled_posttypes" id="<?php echo $key; ?>" type="checkbox" value="<?php echo $key; ?>" <?php if( in_array( $key, $disabled_posttypes ) ){ echo 'checked="checked"'; } ?> >
		            <?php echo $posttype_title = isset( $wp_post_types[$key]->label ) ? $wp_post_types[$key]->label : ucfirst( $key); ?>
		        </label>
				<?php
			}
		}
		?>
		<p class="description">
			<?php esc_html_e( 'Please check checkbox for posttypes on which you want to disable Featured image by URL.', 'featured-image-by-url' ); ?>
		</p>

		<?php
	}

	/**
	 * Callback function for resize_images field.
	 *
	 * @since 1.0
	 * @return array
	 */
	function resize_images_callback( $args ) {
		// get the value of the setting we've registered with register_setting()
		$options = get_option( KNAWATFIBU_OPTIONS );
		$resize_images = isset( $options['resize_images'] ) ? $options['resize_images']  : false;
		?>
		<label for="resize_images">
			<input name="<?php echo KNAWATFIBU_OPTIONS.'['. esc_attr( $args['label_for'] ).']'; ?>" type="checkbox" value="1" id="resize_images" <?php if ( !defined( 'JETPACK__VERSION' ) ) { echo 'disabled="disabled"'; }else{ if( $resize_images ){ echo 'checked="checked"'; } } ?>>
			<?php esc_html_e( 'Enable display resized images for image sizes like thumbnail, medium, large etc..', 'featured-image-by-url' ); ?>
			
		</label>
		<p class="description">
			<?php esc_html_e( 'You need Jetpack plugin installed & connected  for enable this functionality.', 'featured-image-by-url' ); ?>
		</p>

		<?php
	}

	/**
	 * Get Post Types which supports KnawatFIFU
	 *
	 * @since 1.0
	 * @return array
	 */
	function knawatfibu_get_posttypes( $raw = false ) {

		$post_types = array_diff( get_post_types( array( 'public'   => true ), 'names' ), array( 'nav_menu_item', 'attachment', 'revision' ) );
		if( !empty( $post_types ) ){
			foreach ( $post_types as $key => $post_type ) {
				if( !post_type_supports( $post_type, 'thumbnail' ) ){
					unset( $post_types[$key] );
				}
			}
		}
		if( $raw ){
			return $post_types;	
		}else{
			$options = get_option( KNAWATFIBU_OPTIONS );
			$disabled_posttypes = isset( $options['disabled_posttypes'] ) ? $options['disabled_posttypes']  : array();
			$post_types = array_diff( $post_types, $disabled_posttypes );
		}

		return $post_types;
	}

	/**
	 * Render Featured image by URL in Product variation
	 *
	 * @return void
	 */
	public function knawatfibu_add_product_variation_image_selector( $loop, $variation_data, $variation ){
		$knawatfibu_url = '';
		if( isset( $variation_data['_knawatfibu_url'][0] ) ){
			$knawatfibu_url = $variation_data['_knawatfibu_url'][0];
			$knawatfibu_url = maybe_unserialize( $knawatfibu_url );
			if( is_array( $knawatfibu_url ) ){
				$knawatfibu_url = $knawatfibu_url['img_url'];
			}
		}
		?>
		<div id="knawatfibu_product_variation_<?php echo $variation->ID; ?>" class="knawatfibu_product_variation form-row form-row-first">
			<label for="knawatfibu_pvar_url_<?php echo $variation->ID; ?>">
				<strong><?php _e('Product Variation Image by URL', 'featured-image-by-url') ?></strong>
			</label>

			<div id="knawatfibu_pvar_img_wrap_<?php echo $variation->ID; ?>" class="knawatfibu_pvar_img_wrap" style="<?php if( $knawatfibu_url == '' ){ echo 'display:none'; } ?>" >
				<span href="#" class="knawatfibu_pvar_remove" data-id="<?php echo $variation->ID; ?>"></span>
				<img id="knawatfibu_pvar_img_<?php echo $variation->ID; ?>" class="knawatfibu_pvar_img" data-id="<?php echo $variation->ID; ?>" src="<?php echo $knawatfibu_url; ?>" />
			</div>
			<div id="knawatfibu_url_wrap_<?php echo $variation->ID; ?>" style="<?php if( $knawatfibu_url != '' ){ echo 'display:none'; } ?>" >
				<input id="knawatfibu_pvar_url_<?php echo $variation->ID; ?>" class="knawatfibu_pvar_url" type="text" name="knawatfibu_pvar_url[<?php echo $variation->ID; ?>]" placeholder="<?php _e('Product Variation Image URL', 'featured-image-by-url'); ?>" value="<?php echo $knawatfibu_url; ?>"/>
				<a id="knawatfibu_pvar_preview_<?php echo $variation->ID; ?>" class="knawatfibu_pvar_preview button" data-id="<?php echo $variation->ID; ?>">
					<?php _e( 'Preview', 'featured-image-by-url' ); ?>
				</a>
			</div>
		</div>
		<?php
	}

	/**
	 * Save Featured image by URL for Product variation
	 *
	 * @return void
	 */
	public function knawatfibu_save_product_variation_image( $variation_id, $i ){

		$image_url = isset( $_POST['knawatfibu_pvar_url'][$variation_id] ) ? esc_url( $_POST['knawatfibu_pvar_url'][$variation_id] ) : '';
		if( $image_url != '' ){
			$img_url = get_post_meta( $variation_id, $this->image_meta_url , true );
			if( is_array( $img_url ) && isset( $img_url['img_url'] ) && $image_url == $img_url['img_url'] ){
					$image_url = array(
						'img_url' => $image_url,
						'width'	  => $img_url['width'],
						'height'  => $img_url['height']
					);
			}else{
				$imagesize = @getimagesize( $image_url );
				$image_url = array(
					'img_url' => $image_url,
					'width'	  => isset( $imagesize[0] ) ? $imagesize[0] : '',
					'height'  => isset( $imagesize[1] ) ? $imagesize[1] : ''
				);
			}
			update_post_meta( $variation_id, $this->image_meta_url, $image_url );
		}else{
			delete_post_meta( $variation_id, $this->image_meta_url );
		}
	}
}