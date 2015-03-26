/**
 * Advanced Ads.
 *
 * @author    Thomas Maier <thomas.maier@webgilde.com>
 * @license   GPL-2.0+
 * @link      http://webgilde.com
 * @copyright 2013-2015 Thomas Maier, webgilde GmbH
 */
;
(function ($) {
    "use strict";
	
    // On DOM ready
    $(function () {
		$(document).on('click', '#submit-pastecode', function(ev){
			ev.preventDefault();
			var rawContent = $('#pastecode-content').val();
			rawContent = rawContent.trim();
			var theAd = {};
			var theContent = $('<div />').html(rawContent);
			var adByGoogle = theContent.find('ins');
			theAd.slotId = adByGoogle.attr('data-ad-slot');
			theAd.pubId = ''; 
			if ('undefined' != typeof(adByGoogle.attr('data-ad-client'))) {
				theAd.pubId = adByGoogle.attr('data-ad-client').substr(3);
			}
			if ('' != theAd.slotId && '' != theAd.pubId) {
				theAd.display = adByGoogle.css('display');
				theAd.format = adByGoogle.attr('data-ad-format');
				theAd.style = adByGoogle.attr('style');
				
				if ('undefined' == typeof(theAd.format) && -1 != theAd.style.indexOf('width')) {
					/* normal ad */
					theAd.type = 'normal';
					theAd.width = adByGoogle.css('width').replace('px', '');
					theAd.height = adByGoogle.css('height').replace('px', '');
					setDetailsFromAdCode(theAd);
					return;
				}
				
				if ('undefined' != typeof(theAd.format) && 'auto' == theAd.format) {
					/* Responsive ad, auto resize */
					theAd.type = 'responsive';
					setDetailsFromAdCode(theAd)
					return;
				}
			}
			// Not recognized ad code
			$('#pastecode-msg').append($('<p />').css('color', 'red').html(gadsenseData.msg.unknownAd));
			
		});
		
        $(document).on('change', '#unit-type, #unit-code', function (ev) {
			var type = $('#unit-type').val();
			if ('responsive' == type) {
				$('#advanced-ads-ad-parameters-size').css('display', 'none');
			} else if ('normal' == type) {
				$('#advanced-ads-ad-parameters-size').css('display', 'block');
			}
			$(document).trigger('gadsenseUnitChanged');
            window.gadsenseFormatAdContent();
        });

		$(document).on('click', '#show-pastecode-div', function(ev){
			ev.preventDefault();
			$('#pastecode-div').show(500);
		});
		
		$(document).on('click', '#hide-pastecode-div', function(ev){
			ev.preventDefault();
			$('#pastecode-div').hide(500);
			$('#pastecode-content').val('');
			$('#pastecode-msg').empty();
		});
				
		/**
		 * Set ad parameters fields from the result of parsing ad code
		 */
		function setDetailsFromAdCode(theAd) {
			$('#unit-code').val(theAd.slotId);
			if ('normal' == theAd.type) {
				$('#unit-type').val('normal');
				$('#advanced-ads-ad-parameters-size input[name="advanced_ad[width]"]').val(theAd.width);
				$('#advanced-ads-ad-parameters-size input[name="advanced_ad[height]"]').val(theAd.height);
			}
			if ('responsive' == theAd.type) {
				$('#unit-type').val('responsive');
				$('#ad-resize-type').val('auto');
				$('#advanced-ads-ad-parameters-size input[name="advanced_ad[width]"]').val('');
				$('#advanced-ads-ad-parameters-size input[name="advanced_ad[height]"]').val('');
			}
			var storedPubId = gadsenseData.pubId;
			if ('' == storedPubId) {
				$('#adsense-ad-param-error').text(gadsenseData.msg.missingPubId);
			} else if (theAd.pubId != storedPubId) {
				$('#adsense-ad-param-error').text(gadsenseData.msg.pubIdMismatch);
			} else {
				$('#adsense-ad-param-error').empty();
			}
			$('#unit-type').trigger('change');
			$('#hide-pastecode-div').trigger('click');
		}
				
        /**
         * Format the post content field
		 *
         */
		window.gadsenseFormatAdContent = function () {
            var slotId = $('#adsense-new-add-div-default #unit-code').val();
            if ('' == slotId) return false;
            var unitType = $('#adsense-new-add-div-default #unit-type').val();
            var adContent = {
                slotId: slotId,
                unitType: unitType,
            };
			if ('responsive' == unitType) {
				var resize = $('#adsense-new-add-div-default #ad-resize-type').val();
				if (0 == resize) resize = 'auto';
				adContent.resize = resize;
			}
			if ('undefined' != typeof(adContent.resize) && 'auto' != adContent.resize) {
				$(document).trigger('gadsenseFormatAdResponsive', [adContent]);
			}
			if ('undefined' != typeof(window.gadsenseAdContent)) {
				adContent = window.gadsenseAdContent;
				delete(window.gadsenseAdContent);
			}
            $('#advads-ad-content-adsense').val(JSON.stringify(adContent, false, 2));
        }

    });

})(jQuery);
