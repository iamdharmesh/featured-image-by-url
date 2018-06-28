<?php 
// Featured Image by URL metabox Template

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;
$image_url = '';
$image_alt = '';
if( isset( $image_meta['img_url'] ) && $image_meta['img_url'] != '' ){
	$image_url = esc_url( $image_meta['img_url'] );
}
if( isset( $image_meta['img_alt'] ) && $image_meta['img_alt'] != '' ){
	$image_alt = esc_attr( $image_meta['img_alt'] );
}
?>

<div id="knawatfibu_metabox_content" >

	<input id="knawatfibu_url" type="text" name="knawatfibu_url" placeholder="<?php _e('Image URL', 'featured-image-by-url') ?>" value="<?php echo $image_url; ?>" />
	<a id="knawatfibu_preview" class="button" >
		<?php _e('Preview', 'featured-image-by-url') ?>
	</a>
	
	<input id="knawatfibu_alt" type="text" name="knawatfibu_alt" placeholder="<?php _e('Alt text (Optional)', 'featured-image-by-url') ?>" value="<?php echo $image_alt; ?>" />

	<div >
		<span id="knawatfibu_noimg"><?php _e('No image', 'featured-image-by-url'); ?></span>
			<img id="knawatfibu_img" src="<?php echo $image_url; ?>" />
	</div>

	<a id="knawatfibu_remove" class="button" style="margin-top:4px;"><?php _e('Remove Image', 'featured-image-by-url') ?></a>
</div>

<script>
	jQuery(document).ready(function($){

		<?php if ( ! $image_meta['img_url'] ): ?>
			$('#knawatfibu_img').hide().attr('src','');
			$('#knawatfibu_noimg').show();
			$('#knawatfibu_alt').hide().val('');
			$('#knawatfibu_remove').hide();
			$('#knawatfibu_url').show().val('');
			$('#knawatfibu_preview').show();
		<?php else: ?>
			$('#knawatfibu_noimg').hide();
			$('#knawatfibu_remove').show();
			$('#knawatfibu_url').hide();
			$('#knawatfibu_preview').hide();
		<?php endif; ?>

		// Preview Featured Image
		$('#knawatfibu_preview').click(function(e){
			
			e.preventDefault();
			imgUrl = $('#knawatfibu_url').val();
			
			if ( imgUrl != '' ){
				$("<img>", {
						    src: imgUrl,
						    error: function() {alert('<?php _e('Error URL Image', 'featured-image-by-url') ?>')},
						    load: function() {
						    	$('#knawatfibu_img').show().attr('src',imgUrl);
						    	$('#knawatfibu_noimg').hide();
						    	$('#knawatfibu_alt').show();
						    	$('#knawatfibu_remove').show();
						    	$('#knawatfibu_url').hide();
						    	$('#knawatfibu_preview').hide();
						    }
				});
			}
		});

		// Remove Featured Image
		$('#knawatfibu_remove').click(function(e){

			e.preventDefault();
			$('#knawatfibu_img').hide().attr('src','');
			$('#knawatfibu_noimg').show();
	    	$('#knawatfibu_alt').hide().val('');
	    	$('#knawatfibu_remove').hide();
	    	$('#knawatfibu_url').show().val('');
	    	$('#knawatfibu_preview').show();

		});

	});

</script>