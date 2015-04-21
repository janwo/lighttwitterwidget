<?php
/**
 * Plugin Name: Light Twitter Widget
 * Description: A light asynchronous twitter widget.
 * Version: 1.0
 * Author: Jan Wolf
 * Author URI: http://jan-wolf.de
 * License: GPL2
 */

 /*  Copyright 2013  Jan Wolf  (email : info@jan-wolf.de)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

class jw_lighttwitterwidget {
  /**
   * Holds the values to be used in the fields callbacks
   */
  protected $defaults;
	protected $option_name;
	protected $option_group;
	protected $option_section_general;
	protected $option_section_textvars;
  protected const TEXT_DOMAIN_LIGHT_TWITTER_WIDGET = 'jw_lighttwitterwidget';

    /**
     * Start up
     */
    public function __construct() {
  		$this->option_section_general =  'jw_lighttwitterwidget_settingssection_general';
  		$this->option_section_autoresponder = 'jw_lighttwitterwidget_settingssection_textvars';
  		$this->option_name = 'jw_lighttwitterwidget_settings';
  		$this->option_group = 'jw_lighttwitterwidget_settingsgroup';
  		$this->defaults = array(
  			'consumer' => '',
  			'oauthtoken' => '',
  			'oauthtokensecret' => '',
  			'consumersecret' => '',
  			'follow' => __('Folge %s', TEXT_DOMAIN_LIGHT_TWITTER_WIDGET),
  			'servererror' => __('Diese Funktion ist zurzeit nicht verfügbar.', TEXT_DOMAIN_LIGHT_TWITTER_WIDGET),
  			'timewhentwittered' => __('getwittert am %s', TEXT_DOMAIN_LIGHT_TWITTER_WIDGET)
      );

      add_action( 'admin_init', array( $this, 'page_init' ) );
      add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
      add_action( 'wp_enqueue_scripts', array( $this, 'enqueueScripts' ) );
  		add_action( 'jw_lighttwitterwidget_createwidget', array( $this, 'generateTwitterWidget' ) );

  		if(is_admin()) {
  			add_action('wp_ajax_nopriv_jw_lighttwitterwidget_twitterresponse', array( $this, 'getTwitterResponse' ) );
  			add_action('wp_ajax_jw_lighttwitterwidget_twitterresponse', array( $this, 'getTwitterResponse' ) );
  		}

  		// Uninstall Plugin hook
      register_uninstall_hook(__FILE__, array($this, 'uninstall'));
    }

	public function enqueueScripts() {
		$options = get_option($this->option_name, $this->defaults);
		wp_enqueue_script( 'jw_lighttwitterwidget_script', plugins_url( 'script.js', __FILE__ ),array('jquery'));
		wp_localize_script( 'jw_lighttwitterwidget_script', 'jw_lighttwitterwidget_ajaxobj', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ), 'servererror' => $options['jw_lighttwitterwidget_servererror'], 'nonce' => wp_create_nonce( 'jw_lighttwitterwidget_nonce') ) );
		wp_enqueue_style( 'jw_lighttwitterwidget_style', plugins_url( 'style.css', __FILE__ ) );
	}

	/* BACK END FUNCTIONS */

    public function uninstall() {
    	delete_option($this->option_name);
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            __('Twitter-Einstellungen', TEXT_DOMAIN_LIGHT_TWITTER_WIDGET),
            __('Twitter-Widget', TEXT_DOMAIN_LIGHT_TWITTER_WIDGET),
            'edit_pages',
            $this->option_group,
            array( $this, 'create_admin_page' )
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        ?>
		<div class="wrap">
			<?php screen_icon(); ?>
			<h2>Twitter-Einstellungen</h2>
			<form method="post" action="options.php">
			<?php
                // This prints out all hidden setting fields
                settings_fields( $this->option_group );
                do_settings_sections( $this->option_group );
                submit_button();
            ?>
			</form>
		</div>
		<?php
    }

    /**
     * Register and add settings
     */
    public function page_init() {
		register_setting(
            $this->option_group, // Option group
            $this->option_name, // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            $this->option_section_general, // ID
            'Twitter-Daten', // Title
            array( $this, 'print_section_info_general' ), // Callback
            $this->option_group // Page
        );

        add_settings_section(
            $this->option_section_textvars, // ID
            'Textvariablen', // Title
            array( $this, 'print_section_info_textvars' ), // Callback
            $this->option_group // Page
        );

        add_settings_field(
            'consumer', // ID
            'Consumer-String', // Title
            array( $this, 'consumer_callback' ), // Callback
            $this->option_group, // Page
            $this->option_section_general // Section
        );

        add_settings_field(
            'consumersecret', // ID
            'Consumersecret-String', // Title
            array( $this, 'consumersecret_callback' ), // Callback
            $this->option_group, // Page
            $this->option_section_general // Section
        );

        add_settings_field(
            'oauthtoken', // ID
            'OAuthToken-String', // Title
            array( $this, 'oauthtoken_callback' ), // Callback
            $this->option_group, // Page
            $this->option_section_general // Section
        );

        add_settings_field(
            'oauthtokensecret', // ID
            'OAuthTokensecret-String', // Title
            array( $this, 'oauthtokensecret_callback' ), // Callback
            $this->option_group, // Page
            $this->option_section_general // Section
        );

        add_settings_field(
            'follow', // ID
            'Folge Person XY', // Title
            array( $this, 'follow_callback' ), // Callback
            $this->option_group, // Page
            $this->option_section_textvars // Section
        );

        add_settings_field(
            'timewhentwittered', // ID
            'Getwittert am XY', // Title
            array( $this, 'timewhentwittered_callback' ), // Callback
            $this->option_group, // Page
            $this->option_section_textvars // Section
        );

		add_settings_field(
            'servererror',  // ID
            'Unbekannte Fehlermeldung',  // Title
            array( $this, 'servererror_callback' ),  // Callback
            $this->option_group,  // Page
            $this->option_section_textvars // Section
        );
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input ) {
		$output = get_option($this->option_name, $this->defaults);
        $output['follow'] = sanitize_text_field($input['follow']);
        $output['consumer'] = sanitize_text_field($input['consumer']);
        $output['oauthtoken'] = sanitize_text_field($input['oauthtoken']);
        $output['oauthtokensecret'] = sanitize_text_field($input['oauthtokensecret']);
        $output['consumersecret'] = sanitize_text_field($input['consumersecret']);
        $output['timewhentwittered'] = sanitize_text_field($input['timewhentwittered']);
        $output['servererror'] = sanitize_text_field($input['servererror']);
		return $output;
    }

    /**
     * Print the Section text for general section
     */
    public function print_section_info_general()
    {
        print 'Geben Sie Ihre Twitter-Daten ein, die zur Initialisierung des Widgets verwendet werden sollen:';
    }

    /**
     * Print the Section text for autoresponder section
     */
    public function print_section_info_textvars()
    {
        print 'Geben Sie Ihren gewünschten Wortlaut des Widgets an:';
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function follow_callback()
    {
		$options = get_option($this->option_name, $this->defaults);
        printf(
            '<input type="text" id="follow" name="'.$this->option_name.'[follow]" value="%s" />',
            isset( $options["follow"] ) ? esc_attr( $options["follow"] ) : '' );
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function consumer_callback()
    {
		$options = get_option($this->option_name, $this->defaults);
        printf(
            '<input type="text" id="consumer" name="'.$this->option_name.'[consumer]" value="%s" />',
            isset( $options["consumer"] ) ? esc_attr( $options["consumer"] ) : '' );
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function consumersecret_callback()
    {
		$options = get_option($this->option_name, $this->defaults);
        printf(
            '<input type="text" id="consumersecret" name="'.$this->option_name.'[consumersecret]" value="%s" />',
            isset( $options["consumersecret"] ) ? esc_attr( $options["consumersecret"] ) : '' );
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function oauthtoken_callback()
    {
		$options = get_option($this->option_name, $this->defaults);
        printf(
            '<input type="text" id="oauthtoken" name="'.$this->option_name.'[oauthtoken]" value="%s" />',
            isset( $options["oauthtoken"] ) ? esc_attr( $options["oauthtoken"] ) : '' );
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function oauthtokensecret_callback()
    {
		$options = get_option($this->option_name, $this->defaults);
        printf(
            '<input type="text" id="oauthtokensecret" name="'.$this->option_name.'[oauthtokensecret]" value="%s" />',
            isset( $options["oauthtokensecret"] ) ? esc_attr( $options["oauthtokensecret"] ) : '' );
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function servererror_callback()
    {
		$options = get_option($this->option_name, $this->defaults);
        printf(
            '<input type="text" id="servererror" name="'.$this->option_name.'[servererror]" value="%s" />',
            isset( $options["servererror"] ) ? esc_attr( $options["servererror"] ) : '' );
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function timewhentwittered_callback()
    {
		$options = get_option($this->option_name, $this->defaults);
        printf(
            '<input type="text" id="timewhentwittered" name="'.$this->option_name.'[timewhentwittered]" value="%s" />',
            isset( $options["timewhentwittered"] ) ? esc_attr( $options["timewhentwittered"] ) : '' );
    }

	/* FRONT END FUNCTIONS */

	public function runTwitterStyler($status,$targetBlank=true,$linkMaxLen=250){
		if($status==null)
			return "";

		// The target
		$target=$targetBlank ? " target=\"_blank\" " : "";

		// convert link to url
		$status = preg_replace("/((http:\/\/|https:\/\/)[^ )
	]+)/e", "'<a href=\"$1\" rel=\"nofollow\" class=\"url\" title=\"$1\"$target>'. ((strlen('$1')>=$linkMaxLen ? substr('$1',0,$linkMaxLen).'...':'$1')).'</a>'", $status);

		// convert @ to follow
		$status = preg_replace("/(@([_a-z0-9\-]+))/i","<a class=\"user\" rel=\"nofollow\" href=\"http://twitter.com/$2\" title=\"Follow $2\"$target>$1</a>",$status);

		// convert # to search
		$status = preg_replace("/(#([_a-z0-9\-]+))/i","<a class=\"tag\" rel=\"nofollow\" href=\"http://search.twitter.com/search?q=%23$2\" title=\"Search $1\"$target>$1</a>",$status);

		// return the status
		return $status;
	}

	public function getTwitterResponse(){
		$options = get_option($this->option_name, $this->defaults);
		require_once('twitteroauth/twitteroauth.php');
		$consumer = $options["consumer"];
		$consumer_secret = $options["consumersecret"];
		$oauth_token = $options["oauthtoken"];
		$oauth_token_secret = $options["oauthtokensecret"];

		$connection = new TwitterOAuth($consumer, $consumer_secret, $oauth_token, $oauth_token_secret);
		$response = $connection->get("statuses/user_timeline");

		header('content-type: application/json; charset=utf-8');

		if($response==null || isset($response->errors))
			die(json_encode(array("ok"=>false)));
		$response = $response[0];
		if($response==null && $response->text!=null)
			die(json_encode(array("ok"=>false)));
		$date = new DateTime($response->created_at);
		die(json_encode(array("ok"=>true,"fulltext"=>'<a class="user" href="http://twitter.com/'.$response->user->screen_name.'" title="'.str_replace("%s", $response->user->screen_name, $options['follow']).'" target="_blank" rel="nofollow">'.$response->user->screen_name.'</a>: '.$this->runTwitterStyler($response->text).'<br/><span id="sub">'.str_replace("%s", $date->format("d.m.Y"), $options['timewhentwittered']).'</span>',"tweet"=>$this->runTwitterStyler($response->text),"user"=>$this->runTwitterStyler($response->user->screen_name), "date"=>$response->created_at)));
	}

	public function generateTwitterWidget() {
		?>
		<div id="jw_lighttwitterwidget"><div></div></div>
		<?php
	}
}

new jw_lighttwitterwidget();
