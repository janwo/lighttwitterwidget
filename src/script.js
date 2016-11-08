/*!
* @author: Jan Wolf (https://jan-wolf.de)
* @license: 2016 Jan Wolf
*/
(function ( $ ) {
	"use strict";

	// Define.
	$.fn.jwLightTwitterWidget = function (options) {
		// Merge options with default values.
		options = $.extend( true, {
			prefix: 'jw_lighttwitterwidget',
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
			return options.prefix + '_' + string;
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
			widgets.removeClass('loading');
		};

		var error = function(){
			// Enable error state.
			widgets.addClass('error');

			// Get error element.
			widgets.find('[data-error]').each(function () {
				// Set error message.
				$(this).text( $(this).attr('data-error') );
			});
		};

		var no_tweets = function(){
			// Enable no-tweets state.
			widgets.addClass('no-tweets');

			// Get empty tweet element.
			widgets.find('[data-no-tweets]').each(function () {
				// Set empty tweet.
				$(this).text( $(this).attr('data-no-tweets') );
			});
		};

		// Save scope.
		var widgets = $(this);

		// Make ajax call.
		var ajax_obj = eval( prefix('ajaxobj') );
		$.ajax( {
			url: ajax_obj.endpoint_url,
			cache: false,
			dataType: "json",
			type: "POST",
			data: {
				action: ajax_obj.endpoint_action,
				nonce: ajax_obj.endpoint_nonce
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
					widgets.find('[data-preset]').each(function(){
						var preset = resolve_preset($(this).attr('data-preset'), data.tweet);
						var attribute = $(this ).attr('data-preset-on');
						if(attribute === undefined)
							$( this ).html( preset );
						else
							$( this ).attr(attribute, preset);
					});

					// Fill image elements.
					widgets.find('[data-avatar]').each(function(){
						// Prepare the right URL.
						var url = data.tweet.user.image.replace('_normal.', '_200x200.');

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
		if(eval('jw_lighttwitterwidget_ajaxobj' ).autoload) $('.jw_lighttwitterwidget_widget').jwLightTwitterWidget();
	});
})(jQuery);
