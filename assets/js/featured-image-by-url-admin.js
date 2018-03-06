/**
 * Featured Image by URL admin javascript.
 */
jQuery(document).ready(function($){
	$(document).on("click", ".knawatfibu_pvar_preview", function(e){

		e.preventDefault();
		var id = jQuery(this).data('id');
		imgUrl = $('#knawatfibu_pvar_url_'+id).val();
		if ( imgUrl != '' ){
			$("<img>", { // Url validation
					    src: imgUrl,
					    error: function() { alert( knawatfibujs.invalid_image_url ); },
					    load: function() {
							$('#knawatfibu_pvar_img_wrap_'+id).show();
							$('#knawatfibu_pvar_img_'+id).attr('src',imgUrl);
							$('#knawatfibu_url_wrap_'+id).hide();
					    }
			});
		}
	});

	$(document).on("click", ".knawatfibu_pvar_remove", function(e){
		var id2 = jQuery(this).data('id');

		e.preventDefault();
		$('#knawatfibu_pvar_url_'+id2).val("").trigger("change");
		$('#knawatfibu_pvar_img_'+id2).attr('src',"");
		$('#knawatfibu_pvar_img_wrap_'+id2).hide();
		$('#knawatfibu_url_wrap_'+id2).show();
	});
});