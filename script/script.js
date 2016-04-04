/*!
* @author: Jan Wolf (https://jan-wolf.de)
* @license: 2016 Jan Wolf
*/
(function ( $ ) {
	"use strict";
	// Define.
	var jw_lighttwitterwidget = function (options) {
		// Merge options with default values.
		options = $.extend( {
			element: null,
			regex: {
				time: {
					unknown_variable: /(\s|^)x(\s|$)/ig,
					plural_indicator: /\[(.*?)]/ig,
					enclosing: /(seconds|minutes|hours|months|days|years)\((.*?)\)/ig
				},
				tweet: /tweet\(\)/ig,
				name: /name\(\)/ig,
				screen_name: /screen_name\(\)/ig
			}
		}, options || {} );

		var prefix = function(string){
			return options.element.attr('data-prefix') + '_' + string;
		};

		var resolve_preset = function(preset, tweet){
			// Replace the screen_name.
			preset = preset.replace(options.regex.screen_name, tweet.user.screen_name);

			// Replace the name.
			preset = preset.replace(options.regex.name, tweet.user.name);

			// Replace the time.
			var found_valid_time = false;
			preset = preset.replace(options.regex.time.enclosing, function(match, p1, p2){
				// Already found a valid time in the whole string? Delete this substring.
				if(found_valid_time) return "";

				// Get number of time units.
				var millis_ago = Date.now() - new Date(tweet.date).getTime();
				switch(p1) {
					case 'seconds': millis_ago /= 1000; break;
					case 'minutes': millis_ago /= 1000 * 60; break;
					case 'hours': millis_ago /= 1000 * 60 * 60; break;
					case 'days': millis_ago /= 1000 * 60 * 60 * 24; break;
					case 'months': millis_ago /= 1000 * 60 * 60 * 24 * 31; break;
					case 'years': millis_ago /= 1000 * 60 * 60 * 24 * 365; break;
				}
				millis_ago = Math.floor(millis_ago);
				found_valid_time = millis_ago >= 1;
				if(!found_valid_time) return "";

				// Replace unknown variable.
				p2 = p2.replace(options.regex.time.unknown_variable, "$1" + millis_ago + "$2");

				// Replace plural indicator variable.
				return p2.replace(options.regex.time.plural_indicator, millis_ago >= 2 ? "$1" : "");
			});

			// Replace the tweet.
			preset = preset.replace(options.regex.tweet, tweet.text);

			return preset;
		};

		var always = function(){
			// Disable loading state.
			options.element.removeClass('loading');
		};

		var error = function(){
			// Enable error state.
			options.element.addClass('error');

			// Get error element.
			var error_element = options.element.find('[data-error]');

			// Set error message.
			error_element.text( error_element.attr('data-error') );
		};

		var no_tweets = function(){
			// Enable no-tweets state.
			options.element.addClass('no-tweets');

			// Set empty tweet.
			options.element.find('[data-no-tweets]').text( tweet.attr('data-no-tweets') );
		};

		// Make ajax call.
		var ajax_obj = eval(prefix('ajaxobj'));
		$.ajax( {
			url: ajax_obj.ajaxurl,
			cache: false,
			dataType: "json",
			type: "POST",
			data: {
				action: prefix('twitterresponse'),
				nonce: ajax_obj.nonce
			},
			beforeSend: function ( x ) {
				if ( x && x.overrideMimeType ) {
					x.overrideMimeType( "application/json;charset=UTF-8" );
				}
			}
		} ).always(always).fail(error).done(function(data){
			switch ( data.status) {
				case 'success':
					// Fill preset elements.
					options.element.find('[data-preset]').each(function(){
						var preset = resolve_preset($(this).attr('data-preset'), data.tweet);
						var attribute = $(this ).attr('data-preset-on');
						if(attribute === undefined)
							$( this ).html( preset );
						else
							$( this ).attr(attribute, preset);
					});

					// Fill image elements.
					options.element.find('[data-avatar]').each(function(){
						// Prepare the right URL.
						var url = data.tweet.user.image.replace('_normal.', '_bigger.');

						// Apply new src, if it's an image.
						if($( this ).is('img')) {
							$( this ).attr( 'src', url );
							return;
						}

						// Apply background image.
						$( this ).css( 'background-image', 'url(' + url + ')' );
					});
					break;

				case 'no-tweets':
					// Transform to no-tweets-state.
					no_tweets();
					break;

				default:
					// Transform to error-state.
					error();
			}
		});
	};

	// Initialize.
	$(document).ready(function() {
		$('.jw_lighttwitterwidget_widget' ).each(function(){
			new jw_lighttwitterwidget({
				element: $(this)
			});
		});
	});
})(jQuery);
