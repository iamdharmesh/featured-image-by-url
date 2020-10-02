<?php
/**
 * Common functions class for Featured Image by URL.
 *
 * @link       http://knawat.com/
 * @since      1.0.0
 *
 * @package    Featured_Image_By_URL
 * @subpackage Featured_Image_By_URL/includes
 */
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class Featured_Image_By_URL_Common {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		
		add_filter( 'post_thumbnail_html', array( $this, 'knawatfibu_overwrite_thumbnail_with_url' ), 999, 5 );
		add_filter( 'woocommerce_structured_data_product', array( $this, 'knawatfibu_woo_structured_data_product_support' ), 99, 2 );
		add_filter( 'facebook_for_woocommerce_integration_prepare_product', array( $this, 'knawatfibu_facebook_for_woocommerce_support' ), 99, 2 );
		add_filter( 'shopzio_product_image_from_id', array( $this, 'knawatfibu_shopzio_product_image_url' ), 10, 2 );

		if( !is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ){
			add_action( 'init', array( $this, 'knawatfibu_set_thumbnail_id_true' ) );
			add_filter( 'wp_get_attachment_image_src', array( $this, 'knawatfibu_replace_attachment_image_src' ), 10, 4 );
			add_filter( 'woocommerce_product_get_gallery_image_ids', array( $this, 'knawatfibu_set_customized_gallary_ids' ), 99, 2 );
			// Product Variation image Support
			add_filter( 'woocommerce_available_variation', array( $this, 'knawatfibu_woocommerce_available_variation' ), 99, 3 );
		}
		// Add WooCommerce Product listable Thumbnail Support for Woo 3.5 or greater
		add_action( 'admin_init', array( $this, 'knawatfibu_woo_thumb_support' ) );

		$options = get_option( KNAWATFIBU_OPTIONS );
		$resize_images = isset( $options['resize_images'] ) ? $options['resize_images']  : false;
		if( !$resize_images ){
			add_filter( 'knawatfibu_user_resized_images', '__return_false' );
		}

		// Fix the issue of images not appearing .. 
		// solved here : https://wordpress.org/support/topic/doesnt-work-with-woocommerce-3-6-0/#post-11490338
		add_filter('woocommerce_product_get_image_id', array( $this, 'knawatfibu_woocommerce_36_support'), 99, 2);
	}

	/**
	 * Fix getting the correct url for product image.
	 *
	 * @return value
	 */
	function knawatfibu_woocommerce_36_support( $value, $product){
		global $knawatfibu;
		$product_id = $product->get_id();
		if(!empty($product_id) && !empty($knawatfibu)){
			$post_type = get_post_type( $product_id );
			$image_data = $knawatfibu->admin->knawatfibu_get_image_meta( $product_id );
			if ( isset( $image_data['img_url'] ) && $image_data['img_url'] != '' ){
				return $product_id;
			}
		}
		return $value;
	}
	

	/**
	 * add filters for set '_thubmnail_id' true if post has external featured image.
	 *
	 * @since 1.0
	 * @return void
	 */
	function knawatfibu_set_thumbnail_id_true(){
		global $knawatfibu;
		foreach ( $knawatfibu->admin->knawatfibu_get_posttypes() as $post_type ) {
			add_filter( "get_{$post_type}_metadata", array( $this, 'knawatfibu_set_thumbnail_true' ), 10, 4 );
		}
	}

	/**
	 * Set '_thubmnail_id' true if post has external featured image.
	 *
	 * @since 1.0
	 * @return void
	 */
	function knawatfibu_set_thumbnail_true( $value, $object_id, $meta_key, $single ){

		global $knawatfibu;
		$post_type = get_post_type( $object_id );
		if( $this->knawatfibu_is_disallow_posttype( $post_type ) ){
			return $value;
		}

		if ( $meta_key == '_thumbnail_id' ){
			$image_data = $knawatfibu->admin->knawatfibu_get_image_meta( $object_id );
			if ( isset( $image_data['img_url'] ) && $image_data['img_url'] != '' ){
				if( $post_type == 'product_variation' ){
					if( !is_admin() ){
						return $object_id;
					}else{
						return $value;
					}
				}
				return $object_id;
			}
		}
		return $value;
	}

	/**
	 * Get Overwrited Post Thumbnail HTML with External Image URL
	 *
	 * @since 1.0
	 * @return string
	 */
	function knawatfibu_overwrite_thumbnail_with_url( $html, $post_id, $post_image_id, $size, $attr ){

		global $knawatfibu;
		if( $this->knawatfibu_is_disallow_posttype( get_post_type( $post_id ) ) ){
			return $html;
		}

		if( is_singular( 'product' ) && ( 'product' == get_post_type( $post_id ) || 'product_variation' == get_post_type( $post_id ) ) ){
			return $html;
		}
		
		$image_data = $knawatfibu->admin->knawatfibu_get_image_meta( $post_id );
		
		if( !empty( $image_data['img_url'] ) ){
			$image_url 		= $image_data['img_url'];

			// Run Photon Resize Magic.
			if( apply_filters( 'knawatfibu_user_resized_images', true ) ){
				$image_url = $this->knawatfibu_resize_image_on_the_fly( $image_url, $size );	
			}

			$image_alt	= ( $image_data['img_alt'] ) ? 'alt="'.$image_data['img_alt'].'"' : '';
			$classes 	= 'external-img wp-post-image ';
			$classes   .= ( isset($attr['class']) ) ? $attr['class'] : '';
			$style 		= ( isset($attr['style']) ) ? 'style="'.$attr['style'].'"' : '';

			$html = sprintf('<img src="%s" %s class="%s" %s />', 
							$image_url, $image_alt, $classes, $style);
		}
		return $html;
	}

	/**
	 * Get Resized Image URL based on main Image URL & size
	 *
	 * @since 1.0
	 * @param string $image_url Full image URL
	 * @param string $size      Image Size
	 *
	 * @return string
	 */
	public function knawatfibu_resize_image_on_the_fly( $image_url, $size = 'full' ){
		if( $size == 'full' || empty( $image_url )){
			return $image_url;
		}

		if( !class_exists( 'Jetpack_PostImages' ) || !defined( 'JETPACK__VERSION' ) ){
			return $image_url;
		}

		/**
		 * Photon doesn't support query strings so we ignore image url with query string.
		 */
		$parsed = parse_url( $image_url );
		if( isset( $parsed['query'] ) && $parsed['query'] != '' ){
			return $image_url;
		}

		$image_size = $this->knawatfibu_get_image_size( $size );
		
		if( !empty( $image_size ) && !empty( $image_size['width'] ) ){
			$width = (int) $image_size['width'];
			$height = (int) $image_size['height'];

			if ( $width < 1 || $height < 1 ) {
				return $image_url;
			}

			// If WPCOM hosted image use native transformations
			$img_host = parse_url( $image_url, PHP_URL_HOST );
			if ( '.files.wordpress.com' == substr( $img_host, -20 ) ) {
				return add_query_arg( array( 'w' => $width, 'h' => $height, 'crop' => 1 ), set_url_scheme( $image_url ) );
			}

			// Use Photon magic
			if( function_exists( 'jetpack_photon_url' ) ) {
				if( isset( $image_size['crop'] ) && $image_size['crop'] == 1 ){
					return jetpack_photon_url( $image_url, array( 'resize' => "$width,$height" ) );
				}else{
					return jetpack_photon_url( $image_url, array( 'fit' => "$width,$height" ) );
				}
			}
			//$image_url = Jetpack_PostImages::fit_image_url ( $image_url, $image_size['width'], $image_size['height'] );
		}
		
		//return it.
		return $image_url;
	}

	/**
	 * Get size information for all currently-registered image sizes.
	 *
	 * @global $_wp_additional_image_sizes
	 * @uses   get_intermediate_image_sizes()
	 * @return array $sizes Data for all currently-registered image sizes.
	 */
	function knawatfibu_get_image_sizes() {
		global $_wp_additional_image_sizes;
		$sizes = array();
		foreach ( get_intermediate_image_sizes() as $_size ) {
			if ( in_array( $_size, array('thumbnail', 'medium', 'medium_large', 'large') ) ) {
				$sizes[ $_size ]['width']  = get_option( "{$_size}_size_w" );
				$sizes[ $_size ]['height'] = get_option( "{$_size}_size_h" );
				$sizes[ $_size ]['crop']   = (bool) get_option( "{$_size}_crop" );
			} elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {
				$sizes[ $_size ] = array(
					'width'  => $_wp_additional_image_sizes[ $_size ]['width'],
					'height' => $_wp_additional_image_sizes[ $_size ]['height'],
					'crop'   => $_wp_additional_image_sizes[ $_size ]['crop'],
				);
			}
		}
		return $sizes;
	}

	/**
	 * Get WC gallary data.
	 *
	 * @since 1.0
	 * @return void
	 */
	function knawatfibu_get_wcgallary_meta( $post_id ){
		
		$image_meta  = array();

		$gallary_images = get_post_meta( $post_id, KNAWATFIBU_WCGALLARY, true );
		
		if( !is_array( $gallary_images ) && $gallary_images != '' ){
			$gallary_images = explode( ',', $gallary_images );
			if( !empty( $gallary_images ) ){
				$gallarys = array();
				foreach ($gallary_images as $gallary_image ) {
					$gallary = array();
					$gallary['url'] = $gallary_image;
					$imagesizes = @getimagesize( $gallary_image );
					$gallary['width'] = isset( $imagesizes[0] ) ? $imagesizes[0] : '';
					$gallary['height'] = isset( $imagesizes[1] ) ? $imagesizes[1] : '';
					$gallarys[] = $gallary;
				}
				$gallary_images = $gallarys;
				update_post_meta( $post_id, KNAWATFIBU_WCGALLARY, $gallary_images );
				return $gallary_images;
			}
		}else{
			if( !empty( $gallary_images ) ){
				$need_update = false;
				foreach ($gallary_images as $key => $gallary_image ) {
					if( !isset( $gallary_image['width'] ) && isset( $gallary_image['url'] ) ){
						$imagesizes1 = @getimagesize( $gallary_image['url'] );
						$gallary_images[$key]['width'] = isset( $imagesizes1[0] ) ? $imagesizes1[0] : '';
						$gallary_images[$key]['height'] = isset( $imagesizes1[1] ) ? $imagesizes1[1] : '';
						$need_update = true;
					}
				}
				if( $need_update ){
					update_post_meta( $post_id, KNAWATFIBU_WCGALLARY, $gallary_images );
				}
				return $gallary_images;
			}	
		}
		return $gallary_images;
	}

	/**
	 * Get fake product gallary ids if url gallery values are there.
	 *
	 * @param  string $value Default product gallery ids
	 * @param  object $product WC Product
	 *
	 * @return bool|array $value modified gallary ids.
	 */
	function knawatfibu_set_customized_gallary_ids( $value, $product ){

		if( $this->knawatfibu_is_disallow_posttype( 'product') ){
			return $value;
		}

		$product_id = $product->get_id();
		if( empty( $product_id ) ){
			return $value;
		}
		$gallery_images = $this->knawatfibu_get_wcgallary_meta( $product_id );
		if( !empty( $gallery_images ) ){
			$i = 0;
			foreach ( $gallery_images as $gallery_image ) {
				$gallery_ids[] = '_knawatfibu_wcgallary__'.$i.'__'.$product_id;
				$i++;
			}
			return $gallery_ids;
		}
		return $value;
	}

	/**
	 * Get image src if attachement id contains '_knawatfibu_wcgallary' or '_knawatfibu_fimage_url'
	 *
	 * @uses   get_image_sizes()
	 * @param  string $image Image Src
	 * @param  int $attachment_id Attachment ID
	 * @param  string $size Size
	 * @param  string $icon Icon
	 *
	 * @return bool|array $image Image Src
	 */
	function knawatfibu_replace_attachment_image_src( $image, $attachment_id, $size, $icon ) {
		global $knawatfibu;
		if( false !== strpos( $attachment_id, '_knawatfibu_wcgallary' ) ){
			$attachment = explode( '__', $attachment_id );
			$image_num  = $attachment[1];
			$product_id = $attachment[2];
			if( $product_id > 0 ){
				
				$gallery_images = $knawatfibu->common->knawatfibu_get_wcgallary_meta( $product_id );
				if( !empty( $gallery_images ) ){
					if( !isset( $gallery_images[$image_num]['url'] ) ){
						return false;
					}
					$url = $gallery_images[$image_num]['url'];
					
					if( apply_filters( 'knawatfibu_user_resized_images', true ) ){
						$url = $knawatfibu->common->knawatfibu_resize_image_on_the_fly( $url, $size );	
					}
					$image_size = $knawatfibu->common->knawatfibu_get_image_size( $size );
					if ($url) {
						if( $image_size ){
							if( !isset( $image_size['crop'] ) ){
								$image_size['crop'] = '';
							}
							return array(
										$url,
										$image_size['width'],
										$image_size['height'],
										$image_size['crop'],
								);
						}else{
							if( $gallery_images[$image_num]['width'] != '' && $gallery_images[$image_num]['width'] > 0 ){
								return array( $url, $gallery_images[$image_num]['width'], $gallery_images[$image_num]['height'], false );
							}else{
								return array( $url, 800, 600, false );
							}
						}
					}
				}
			}
		}

		if( is_numeric($attachment_id ) && $attachment_id > 0 ){
			$image_data = $knawatfibu->admin->knawatfibu_get_image_meta( $attachment_id, true );

			// if( !empty( $image_data['img_url'] ) ){
			if ( isset( $image_data['img_url'] ) && $image_data['img_url'] != '' ){

				$image_url = $image_data['img_url'];
				$width = isset( $image_data['width'] ) ? $image_data['width'] : '';
				$height = isset( $image_data['height'] ) ? $image_data['height'] : '';

				// Run Photon Resize Magic.
				if( apply_filters( 'knawatfibu_user_resized_images', true ) ){
					$image_url = $knawatfibu->common->knawatfibu_resize_image_on_the_fly( $image_url, $size );
				}

				$image_size = $knawatfibu->common->knawatfibu_get_image_size( $size );
				if ($image_url) {
					if( $image_size ){
						if( !isset( $image_size['crop'] ) ){
							$image_size['crop'] = '';
						}
						return array(
									$image_url,
									$image_size['width'],
									$image_size['height'],
									$image_size['crop'],
							);
					}else{
						if( $width != '' && $height != '' ){
							return array( $image_url, $width, $height, false );
						}
						return array( $image_url, 800, 600, false );
					}
				}
			}
		}

		return $image;
	}

	/**
	 * Get size information for a specific image size.
	 *
	 * @uses   get_image_sizes()
	 * @param  string $size The image size for which to retrieve data.
	 * @return bool|array $size Size data about an image size or false if the size doesn't exist.
	 */
	function knawatfibu_get_image_size( $size ) {
		$sizes = $this->knawatfibu_get_image_sizes();

		if( is_array( $size ) ){
			$woo_size = array();
			$woo_size['width'] = $size[0];
			$woo_size['height'] = $size[1];
			return $woo_size;
		}
		if ( isset( $sizes[ $size ] ) ) {
			return $sizes[ $size ];
		}

		return false;
	}

	/**
	 * Get if Is current posttype is active to show featured image by url or not.
	 *
	 * @param  string $posttype Post type
	 * @return bool
	 */
	function knawatfibu_is_disallow_posttype( $posttype ) {

		$options = get_option( KNAWATFIBU_OPTIONS );
		$disabled_posttypes = isset( $options['disabled_posttypes'] ) ? $options['disabled_posttypes']  : array();

		return in_array( $posttype, $disabled_posttypes );
	}

	/**
	 * Add WooCommerce Product listable Thumbnail Support for Woo 3.5 or greater
	 *
	 * @since 1.0
	 * @return void
	 */
	public function knawatfibu_woo_thumb_support() {
		global $pagenow;
		if( 'edit.php' === $pagenow ){
			global $typenow;
			if( 'product' === $typenow && isset( $_GET['post_type'] ) && 'product' === sanitize_text_field( $_GET['post_type'] ) ){
				add_filter( 'wp_get_attachment_image_src', array( $this, 'knawatfibu_replace_attachment_image_src' ), 10, 4 );
			}
		}
	}

	/**
	 * Add Support for WooCommerce Product Structured Data.
	 *
	 * @since 1.0
	 * @param array $markup
	 * @param object $product
	 * @return array $markup
	 */
	function knawatfibu_woo_structured_data_product_support( $markup, $product ) {
		if ( isset($markup['image']) && empty($markup['image']) ) {
			global $knawatfibu;
			$product_id = $product->get_id();
			if( !$this->knawatfibu_is_disallow_posttype( 'product' ) && $product_id > 0 ){
				$image_data = $knawatfibu->admin->knawatfibu_get_image_meta( $product_id );
				if( !empty($image_data) && isset($image_data['img_url']) && !empty($image_data['img_url']) ) {
					$markup['image'] = $image_data['img_url'];
				}
			}
		}
		return $markup;
	}

	/**
	 * Add support for "Facebook for WooCommerce" plugin.
	 *
	 * @param array $product_data
	 * @param int $product_id
	 * @return array $product_data Altered product data for Facebook feed.
	 */
	public function knawatfibu_facebook_for_woocommerce_support( $product_data, $product_id ) {
		if( empty( $product_data ) || empty( $product_id ) ){
			return $product_data;
		}

		global $knawatfibu;
		// Product Image
		$product_image = $knawatfibu->admin->knawatfibu_get_image_meta( $product_id );
		if( isset( $product_image['img_url'] ) && !empty( $product_image['img_url'] ) ){
			$product_data['image_url'] = $product_image['img_url'];
			$image_override = get_post_meta($product_id, 'fb_product_image', true);
			if ( !empty($image_override ) ) {
				$product_data['image_url'] = $image_override;
			}
		}
		// Product Gallery Images
		$product_gallery_images = $knawatfibu->common->knawatfibu_get_wcgallary_meta( $product_id );
		if( !empty( $product_gallery_images ) ){
			$gallery_images = array();
			foreach ($product_gallery_images as $wc_gimage) {
				if( isset( $wc_gimage['url'] ) ){
					$gallery_images[] = $wc_gimage['url'];
				}
			}
			if( !empty( $gallery_images ) ){
				$product_data['additional_image_urls'] = $gallery_images;
			}
		}

		return $product_data;
	}

	/**
	 * Add Support for Shopz.io WC GraphQL Support.
	 *
	 * @since 1.0
	 * @param array|string $image
	 * @param string $attachment_id
	 * @return altered Image.
	 */
	function knawatfibu_shopzio_product_image_url( $image, $attachment_id ) {
		if( empty( $attachment_id ) || !empty($image)){
			return $image;
		}

		$image_data = $this->knawatfibu_replace_attachment_image_src( $image, $attachment_id, 'full', false);
		if (!empty($image_data) && isset($image_data[0]) && !empty($image_data[0])) {
			$image = $image_data[0];
		}

		return $image;
	}

	function knawatfibu_woocommerce_available_variation( $value, $variable_product, $variation ){
		$variation_id =  $variation->get_id();
		if( empty( $variation_id ) ){
			return $value;
		}

		global $knawatfibu;
		// Product Variation Image
		$variation_image = $knawatfibu->admin->knawatfibu_get_image_meta( $variation_id, true );
		if( isset( $variation_image['img_url'] ) && !empty( $variation_image['img_url'] ) && isset($value['image'])){
			$image_url = $variation_image['img_url'];
			$width = (isset( $variation_image['width'] ) && !empty($variation_image['width'])) ? $variation_image['width'] : '';
			$height = (isset( $variation_image['height'] ) && !empty($variation_image['height'])) ? $variation_image['height'] : '';

			$value['image']['url'] = $image_url;
			// Large version.
			$value['image']['full_src'] = $image_url;
			$value['image']['full_src_w'] = $width;
			$value['image']['full_src_h'] = $height;

			// Gallery thumbnail.
			$value['image']['gallery_thumbnail_src'] = $image_url;
			$value['image']['gallery_thumbnail_src_w'] = $width;
			$value['image']['gallery_thumbnail_src_h'] = $height;

			// Thumbnail version.
			$value['image']['thumb_src'] = $image_url;
			$value['image']['thumb_src_w'] = $width;
			$value['image']['thumb_src_h'] = $height;

			// Image version.
			$value['image']['src'] = $image_url;
			$value['image']['src_w'] = $width;
			$value['image']['src_h'] = $height;
		}
		return $value;
	}
}