<?php
require 'vendor/autoload.php';
use Abraham\TwitterOAuth\TwitterOAuth;

/**
 * Plugin Name: Light Twitter Widget
 * Description: A light asynchronous twitter widget.
 * Version: 1.0.5
 * Author: Jan Wolf
 * Author URI: https://jan-wolf.de
 * License: MIT
 */

class jw_lighttwitterwidget {

	const PREFIX = 'jw_lighttwitterwidget';
	const OPTION_NAME = 'settings';
	const CACHE_NAME = 'cache';
	const OPTION_GROUP = 'settings_group';
	const OPTION_SECTION_GENERAL = 'settings_section_general';
	const OPTION_SECTION_AUTHENTICATION = 'settings_section_authentication';
	const TEXT_DOMAIN = 'jw_lighttwitterwidget';
	const DEFAULT_REFRESH_INTERVAL = 3600;

	private static function prefix($string) {
		return self::PREFIX . '_' . $string;
	}

	private function get_options(){
		$options = get_option( self::prefix(self::OPTION_NAME), false );
		$default = [
			'consumer'          => '',
			'oauthtoken'        => '',
			'oauthtokensecret'  => '',
			'consumersecret'    => '',
			'refreshinterval'   => self::DEFAULT_REFRESH_INTERVAL,
			'styling'           => true,
			'autoload'          => true
		];
		if($options === false) return $default;
		return wp_parse_args($options, $default);
	}

	private function get_cache(){
		$cache = get_option( self::prefix(self::CACHE_NAME), false );
		$default = [
			'last_refresh'  => null,
			'last_data'     => null
		];
		if($cache === false) return $default;
		return wp_parse_args($cache, $default);
	}

	function load_textdomain() {
		load_plugin_textdomain( self::TEXT_DOMAIN, false, plugin_basename( dirname( __FILE__ ) ) . '/lang' );
	}

	public function __construct() {
		add_action( 'admin_init', [ $this, 'page_init' ] );
		add_action( 'admin_menu', [ $this, 'add_plugin_page' ] );
		add_action( 'plugins_loaded', [$this, 'load_textdomain'] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'wp_ajax_nopriv_' . self::prefix('api'), [ $this, 'api' ] );
		add_action( 'wp_ajax_' . self::prefix('api'), [ $this, 'api' ] );
		add_shortcode( self::prefix('make'), [ $this, 'generate_widget' ] );
		register_uninstall_hook( __FILE__, [ get_called_class(), 'uninstall' ] );
	}

	public function enqueue_scripts() {
		$options = $this->get_options();

		wp_enqueue_script( self::prefix('script'), plugins_url( 'dst/script.min.js', __FILE__ ), [ 'jquery' ] );
		wp_localize_script( self::prefix('script'), self::prefix('ajaxobj'), [
			'endpoint_url' => admin_url( 'admin-ajax.php' ),
			'endpoint_nonce' => wp_create_nonce( self::prefix('nonce') ),
			'endpoint_action' => self::prefix('api'),
			'autoload' => $options['autoload']
		] );

		// Optional styling.
		if($options['styling']) {
			wp_register_style( self::prefix('style'), plugins_url( 'dst/style.min.css', __FILE__ ) );
			wp_enqueue_style( self::prefix('style') );
		}
	}

	public static function uninstall() {
		delete_option( self::prefix(self::OPTION_NAME) );
		delete_option( self::prefix(self::CACHE_NAME) );
	}

	/**
	 * Add options page.
	 */
	public function add_plugin_page() {
		// This page will be registered within the Settings-Menu.
		add_options_page(
			__( 'Twitter Widget', self::TEXT_DOMAIN ),
			__( 'Twitter Widget', self::TEXT_DOMAIN ),
			'edit_pages',
			self::prefix(self::OPTION_GROUP), [
				$this,
				'create_admin_page'
			] );
	}

	/**
	 * Options page callback.
	 */
	public function create_admin_page() {
		?>
		<div class="wrap">
			<?php screen_icon(); ?>
			<h2><?php _e( 'Twitter Widget', self::TEXT_DOMAIN );?></h2>
			<form method="post" action="options.php">
				<?php
					// This prints out all hidden setting fields.
					settings_fields( self::prefix(self::OPTION_GROUP) );
					do_settings_sections( self::prefix(self::OPTION_GROUP) );
					submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Register and add settings.
	 */
	public function page_init() {
		register_setting( self::prefix(self::OPTION_GROUP), // Option group
			self::prefix(self::OPTION_NAME), // Option name
			[ $this, 'sanitize' ] // Sanitize
		);

		add_settings_section( self::OPTION_SECTION_GENERAL, // ID
			__('General Settings', self::TEXT_DOMAIN), // Title
			[ $this, 'print_section_info_general' ], // Callback
			self::prefix(self::OPTION_GROUP) // Page
		);

		add_settings_section( self::OPTION_SECTION_AUTHENTICATION, // ID
			__('Authentication', self::TEXT_DOMAIN), // Title
			[ $this, 'print_section_info_authentication' ], // Callback
			self::prefix(self::OPTION_GROUP) // Page
		);

		add_settings_field( 'consumer', // ID
			__('Consumer String', self::TEXT_DOMAIN), // Title
			[ $this, 'consumer_callback' ], // Callback
			self::prefix(self::OPTION_GROUP), // Page
			self::OPTION_SECTION_AUTHENTICATION // Section
		);

		add_settings_field( 'consumersecret', // ID
			__('Consumer Secret', self::TEXT_DOMAIN), // Title
			[ $this, 'consumersecret_callback' ], // Callback
			self::prefix(self::OPTION_GROUP), // Page
			self::OPTION_SECTION_AUTHENTICATION // Section
		);

		add_settings_field( 'oauthtoken', // ID
			__('OAuthToken String', self::TEXT_DOMAIN), // Title
			[ $this, 'oauthtoken_callback' ], // Callback
			self::prefix(self::OPTION_GROUP), // Page
			self::OPTION_SECTION_AUTHENTICATION // Section
		);

		add_settings_field( 'oauthtokensecret', // ID
			__('OAuthToken Secret', self::TEXT_DOMAIN), // Title
			[ $this, 'oauthtokensecret_callback' ], // Callback
			self::prefix(self::OPTION_GROUP), // Page
			self::OPTION_SECTION_AUTHENTICATION // Section
		);

		add_settings_field( 'refreshinterval', // ID
			__('Refresh Interval (Seconds)', self::TEXT_DOMAIN), // Title
			[ $this, 'refreshinterval_callback' ], // Callback
			self::prefix(self::OPTION_GROUP), // Page
			self::OPTION_SECTION_GENERAL // Section
		);

		add_settings_field( 'styling', // ID
			__('Enqueue default styling?', self::TEXT_DOMAIN), // Title
			[ $this, 'styling_callback' ], // Callback
			self::prefix(self::OPTION_GROUP), // Page
			self::OPTION_SECTION_GENERAL // Section
		);

		add_settings_field( 'autoload', // ID
			__('Initialize automatically?', self::TEXT_DOMAIN), // Title
			[ $this, 'autoload_callback' ], // Callback
			self::prefix(self::OPTION_GROUP), // Page
			self::OPTION_SECTION_GENERAL // Section
		);
	}

	/**
	 * Sanitize each setting field as needed.
	 *
	 * @param array $input Contains all settings fields as array keys
	 * @return array|mixed
	 */
	public function sanitize( $input ) {
		$data = $this->get_options();
		$data[ 'consumer' ] = sanitize_text_field( $input[ 'consumer' ] );
		$data[ 'oauthtoken' ] = sanitize_text_field( $input[ 'oauthtoken' ] );
		$data[ 'oauthtokensecret' ] = sanitize_text_field( $input[ 'oauthtokensecret' ] );
		$data[ 'consumersecret' ] = sanitize_text_field( $input[ 'consumersecret' ] );
		$data[ 'refreshinterval' ] = is_numeric( $input[ 'refreshinterval' ] ) ? intval($input[ 'refreshinterval' ]) : self::DEFAULT_REFRESH_INTERVAL;
		$data[ 'styling' ] =  isset($input[ 'styling' ]) && $input[ 'styling' ] == '1';
		$data[ 'autoload' ] =  isset($input[ 'autoload' ]) && $input[ 'autoload' ] == '1';
		return $data;
	}

	public function print_section_info_authentication() {
		_e('In order to make the widget work, fill the form with your twitter credentials that are used to gather the tweets.', self::TEXT_DOMAIN);
	}

	public function print_section_info_general() {
		_e('Define your desired general settings of the widget here.', self::TEXT_DOMAIN);
	}

	public function consumer_callback() {
		$options = $this->get_options();
		print '<input type="text" id="consumer" name="' . self::prefix(self::OPTION_NAME) . '[consumer]" value="' . ( isset( $options[ 'consumer' ] ) ? esc_attr( $options[ 'consumer' ] ) : '' ) . '">';
	}

	public function consumersecret_callback() {
		$options = $this->get_options();
		print '<input type="text" id="consumersecret" name="' . self::prefix(self::OPTION_NAME) . '[consumersecret]" value="' . ( isset( $options[ 'consumersecret' ] ) ? esc_attr( $options[ 'consumersecret' ] ) : '' ) . '">';
	}

	public function oauthtoken_callback() {
		$options = $this->get_options();
		print '<input type="text" id="oauthtoken" name="' . self::prefix(self::OPTION_NAME) . '[oauthtoken]" value="' . ( isset( $options[ 'oauthtoken' ] ) ? esc_attr( $options[ 'oauthtoken' ] ) : '' ) . '">';
	}

	public function oauthtokensecret_callback() {
		$options = $this->get_options();
		print '<input type="text" id="oauthtokensecret" name="' . self::prefix(self::OPTION_NAME) . '[oauthtokensecret]" value="' . ( isset( $options[ 'oauthtokensecret' ] ) ? esc_attr( $options[ 'oauthtokensecret' ] ) : '' ) . '">';
	}

	public function refreshinterval_callback() {
		$options = $this->get_options();
		print '<input type="number" id="refreshinterval" name="' . self::prefix(self::OPTION_NAME) . '[refreshinterval]" value="' . ( isset( $options[ 'refreshinterval' ] ) ? esc_attr( $options[ 'refreshinterval' ] ) : '' ) . '">';
	}

	public function styling_callback() {
		$options = $this->get_options();
		print '<input type="checkbox" id="styling" name="' . self::prefix(self::OPTION_NAME) . '[styling]" value="1" ' . checked(true, $options['styling'], false) . '>';
	}

	public function autoload_callback() {
		$options = $this->get_options();
		print '<input type="checkbox" id="autoload" name="' . self::prefix(self::OPTION_NAME) . '[autoload]" value="1" ' . checked(true, $options['autoload'], false) . '>';
	}

	public function twitter_styler( $text, $targetBlank = true ) {
		if ( is_null($text) ) return '';

		// Define target method.
		$target = $targetBlank ? " target=\"_blank\" " : '';

		// Convert link to URL.
		$url_pattern = "/([\w]+\:\/\/[\w-?&;#~=\.\/\@]+[\w\/])/";
		$text = preg_replace( $url_pattern, "<a href=\"$1\" rel=\"nofollow\" class=\"url\" title=\"$1\"$target>$1</a>", $text );

		// Convert @ to a follow-link.
		$text = preg_replace( "/(@([_a-z0-9\-]+))/i", "<a class=\"user\" rel=\"nofollow\" href=\"http://twitter.com/$2\" title=\"Follow $2\"$target>$1</a>", $text );

		// Convert # to a search-link.
		$text = preg_replace( "/(#([_a-z0-9\-]+))/i", "<a class=\"tag\" rel=\"nofollow\" href=\"https://twitter.com/hashtag/$2\" title=\"Search $1\"$target>$1</a>", $text );

		return $text;
	}

	public function api() {
		// Get options and cached data.
		$options = $this->get_options();
		$cache = $this->get_cache();

		// Needs a update?
		$now = time();
		$needs_update = is_null($cache['last_refresh']) || (strtotime($cache['last_refresh']) + $options['refreshinterval']) < $now;
		if($needs_update) {
			// Make request.
			$connection = new TwitterOAuth( $options[ 'consumer' ], $options[ 'consumersecret' ], $options[ 'oauthtoken' ], $options[ 'oauthtokensecret' ] );
			$parameters = [ 'include_rts' => false, 'count' => 1 ];
			if ( !is_null($cache[ 'last_data' ]) ) $parameters[ 'since_id' ] = $cache[ 'last_data' ][ 'id' ];

			try {
				// Connect.
				$response = $connection->get( 'statuses/user_timeline', $parameters );

				// On success.
				if(!isset( $response->errors )) {
					// Update meta of the cache.
					$cache['last_refresh'] = date('D M j G:i:s O Y', $now);

					// Got a new tweet?
					if ( isset( $response[ 0 ] ) ) {
						$response = $response[ 0 ];
						// Update the last data.
						$cache['last_data'] = [
							'id'    => $response->id,
							'text'  => $response->text,
							'user'  => [
								'name'  => $response->user->name,
								'screen_name' => $response->user->screen_name,
								'image' => $response->user->profile_image_url_https,
							],
							'date'  => $response->created_at
						];
					}

					// Save.
					update_option(self::prefix(self::OPTION_NAME), $options);
					update_option(self::prefix(self::CACHE_NAME), $cache);
				}
			} catch(\Abraham\TwitterOAuth\TwitterOAuthException $exception) {
				// Show error message.
				$response = null;
			}
		}

		// Output.
		header( 'content-type: application/json; charset=utf-8' );
		$output = [ 'status' => is_null($cache['last_refresh']) || ($needs_update && is_null($response)) ? 'error' : (is_null($cache['last_data']) ? 'no-tweets' : 'success') ];
		if(!is_null($cache['last_data'])) $output['tweet'] = array_merge($cache['last_data'], [
			'text' => $this->twitter_styler($cache['last_data']['text'])
		]);
		die( json_encode( $output ) );
	}

	public function generate_widget($atts, $content = null) {
		$atts = shortcode_atts([
			'class' => 'jw_lighttwitterwidget_widget'
		], $atts);

		// Echo the wrapper.
		echo '<div class="' . $atts['class'] . '" data-prefix="' . self::PREFIX . '">';

		// Echo the template.
		if ( is_null( $content ) ) {
			echo '<div class="user"><img data-avatar><span class="name" data-preset="name()"></span><span class="screen_name" data-preset="screen_name()"></span></div>';
			echo '<span class="tweet" data-preset="tweet()" data-error="' . __( 'Service temporarily unavailable.', self::TEXT_DOMAIN ) . '" data-no-tweets="' . __( 'There are no tweets yet.', self::TEXT_DOMAIN ) . '"></span>';
			echo '<span class="date" data-preset="' . __( 'Tweeted years(x year[s])months(x month[s])days(x day[s])hours(x hour[s]) ago.', self::TEXT_DOMAIN ) . '"></span>';
		} else {
			echo $content;
		}

		// Close the wrapper.
		echo '</div>';
	}
}

new jw_lighttwitterwidget();
