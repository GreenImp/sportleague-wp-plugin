/**
 * Author: lee
 * Date Created: 07/09/2012 13:02
 */

(function($){
	$(document).ready(function(){
		/**
		 * Add datepicker to relevant fields
		 */
		$('input.datepicker').datepicker({
			dateFormat:'yy-mm-dd'
		});

		/**
		 * Show confirmation message on deletion of elements
		 */
		$('#sportleague .submitdelete').on('click', function(e){
			return showNotice.warn();
		});

		/**
		 * Handle media file uploads
		 */
		var imageObject = null;	// stores the input field

		$('input[type=button].uploadBtn').on('click', function(){
			// find the input field for the button
			imageObject = $(this).siblings('input[type=text].file:first');
			if(imageObject.length == 0){
				imageObject = null;
				return false;
			}

			// set the form field name (this is defined out of scope, by Wordpress)
			formfield = imageObject.attr('name');
			// call the media upload dialogue box
			tb_show('', 'media-upload.php?type=image&post_id=0&TB_iframe=true');
			return false;
		});

		// rewrite the 'send_to_editor' function, to post to our file input
		window.send_to_editor_orig = window.send_to_editor;
		window.send_to_editor = function(html){
			if(imageObject === null){
				// no image defined
				window.send_to_editor_orig(html);
			}else{
				// find the image element in the returned data
				var imageObj = $(html).find('img:first');

				// set the input field to the image source
				imageObject.val(imageObj.attr('src') || '');

				// check if a holder has been defined for displaying the image
				var imageBox = imageObject.siblings('.imageBox');
				if(imageBox.length == 0){
					// no holder exists - create one
					imageBox = $('<div class="imageBox"></div>');
					imageObject.parent().append(imageBox);
				}
				// remove any wordpress alignment classes
				imageObj.removeClass('alignleft alignright aligncenter size-full');

				// add the image and display it
				imageBox
						.stop(true, true)
						.hide()
						.html(imageObj)
						.fadeIn(600);
			}

			// remove the upload dialogue box
			tb_remove();
		}
	});
})(jQuery);