<?php 
// Featured Image by URL metabox Template

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

function knawatfibu_get_gallary_slot( $image_url = '' ){
	ob_start();
	?>
	<div id="knawatfibu_wcgallary__COUNT__" class="knawatfibu_wcgallary">
		<div id="knawatfibu_url_wrap__COUNT__" <?php if( $image_url != ''){ echo 'style="display: none;"'; } ?>>
			<input id="knawatfibu_url__COUNT__" class="knawatfibu_url" type="text" name="knawatfibu_wcgallary[__COUNT__][url]" placeholder="<?php _e('Image URL', 'featured-image-by-url') ?>" data-id="__COUNT__" value="<?php echo $image_url; ?>"/>
			<a id="knawatfibu_preview__COUNT__" class="knawatfibu_preview button" data-id="__COUNT__">
				<?php _e( 'Preview', 'featured-image-by-url' ); ?>
			</a>
		</div>
		<div id="knawatfibu_img_wrap__COUNT__" class="knawatfibu_img_wrap" <?php if( $image_url == ''){ echo 'style="display: none;"'; } ?>>
			<span href="#" class="knawatfibu_remove" data-id="__COUNT__"></span>
			<img id="knawatfibu_img__COUNT__" class="knawatfibu_img" data-id="__COUNT__" src="<?php echo $image_url; ?>" />
		</div>
	</div>
	<?php
	$gallery_image = ob_get_clean();
	return preg_replace('/\s+/', ' ', trim($gallery_image));
}

?>

<div id="knawatfibu_wcgallary_metabox_content" >
	<?php
	global $knawatfibu;
	$gallary_images = $knawatfibu->common->knawatfibu_get_wcgallary_meta( $post->ID );
	$count = 1;
	if( !empty( $gallary_images ) ){
		foreach ($gallary_images as $gallary_image ) {
			echo str_replace( '__COUNT__', $count, knawatfibu_get_gallary_slot( $gallary_image['url'] ) );
			$count++;
		}
	}
	echo str_replace( '__COUNT__', $count, knawatfibu_get_gallary_slot() );
	$count++;
	?>
</div>
<div style="clear:both"></div>
<script>
	jQuery(document).ready(function($){

		var counter = <?php echo $count;?>;
		// Preview
		$(document).on("click", ".knawatfibu_preview", function(e){
						
			e.preventDefault();
			counter = counter + 1;
			var new_element_str = '';
			var id = jQuery(this).data('id');
			imgUrl = $('#knawatfibu_url'+id).val();
			
			if ( imgUrl != '' ){
				$("<img>", { // Url validation
						    src: imgUrl,
						    error: function() {alert('<?php _e('Error URL Image', 'featured-image-by-url') ?>')},
						    load: function() {
						    	$('#knawatfibu_img_wrap'+id).show();
						    	$('#knawatfibu_img'+id).attr('src',imgUrl);
						    	$('#knawatfibu_remove'+id).show();
						    	$('#knawatfibu_url'+id).hide();
						    	$('#knawatfibu_preview'+id).hide();
						    	new_element_str = '<?php echo knawatfibu_get_gallary_slot(); ?>';
						    	new_element_str = new_element_str.replace(/__COUNT__/g, counter );
						    	$('#knawatfibu_wcgallary_metabox_content').append( new_element_str );
						    }
				});
			}
		});

		$(document).on("click", ".knawatfibu_remove", function(e){
			var id2 = jQuery(this).data('id');

			e.preventDefault();
			$('#knawatfibu_wcgallary'+id2).remove();
		});

	});
</script>