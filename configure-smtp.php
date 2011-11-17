<?php
/**
 * @package Amazon SES DKIM Mailer
 * @author Anatta (Nick Murray)
 * @version 1.0.2
 */
/*
Plugin Name: Amazon SES DKIM Mailer
Version: 1.0.2
Plugin URI: http://www.anatta.com/tools/amazon-ses-with-dkim-support-wordpress-plugin
Author: Anatta (Nick Murray)
Author URI: http://www.anatta.com/about/nick-murray
Description: Configure Amazon AES mailing in WordPress, including support for sending e-mail via SSL/TLS (such as GMail).

Compatible with WordPress 3.0+, 3.1+, 3.2+, 3.3

=>> Read the accompanying readme.txt file for instructions and documentation.
=>> Also, visit the plugin's homepage for additional information and updates.
=>> Or visit: http://wordpress.org/extend/plugins/amazon-ses-and-dkim-mailer/

TODO:
	* Incorporate Amazon SES stats checking
	* Implement failover to SMTP once SES quota is reached, or SES error code received
	* Add simple DKIM key and DNS record generator to plugin homepage
*/


/*
The bulk of this plugin is based on the configure-smtp plugin (http://wordpress.org/extend/plugins/configure-smtp/)

Please therefore respect the copyright notice below
 
Copyright (c) 2004-2011 by Scott Reilly (aka coffee2code)

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation
files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy,
modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the
Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR
IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

if ( ! class_exists( 'c2c_ConfigureAES_DKIM_SMTP' ) ) :

require_once( 'c2c-plugin.php' );

class c2c_ConfigureAES_DKIM_SMTP extends C2C_Plugin_023 {

	public static $instance;

	private $gmail_config = array(
		'host' => 'smtp.gmail.com',
		'port' => '587',
		'smtp_auth' => true,
		'smtp_secure' => 'tls'
	);
	private $error_msg = '';

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		$this->ConfigureAES_DKIM_SMTP();
	}

	public function ConfigureAES_DKIM_SMTP() {
		// Be a singleton
		if ( ! is_null( self::$instance ) )
			return;

		$this->C2C_Plugin_023( '1.0', 'anatta-configure-mailer', 'anatta', __FILE__, array() );
		register_activation_hook( __FILE__, array( __CLASS__, 'activation' ) );
		self::$instance = $this;
	}

	/**
	 * Handles activation tasks, such as registering the uninstall hook.
	 *
	 * @since 3.1
	 *
	 * @return void
	 */
	public function activation() {
		register_uninstall_hook( __FILE__, array( __CLASS__, 'uninstall' ) );
	}

	/**
	 * Handles uninstallation tasks, such as deleting plugin options.
	 *
	 * @since 3.1
	 *
	 * @return void
	 */
	public function uninstall() {
		delete_option( 'anatta_configure-mailer' );
	}

	/**
	 * Initializes the plugin's configuration and localizable text variables.
	 *
	 * @return void
	 */
	public function load_config() {
		$this->name      = __( 'Anatta Mailer', $this->textdomain );
		$this->menu_name = __( 'Mail Settings', $this->textdomain );
                $userDomain = explode("@", get_bloginfo('admin_email'));
		$this->config = array(
			'use_aws' => array( 'input' => 'checkbox', 'default' => false,
				'label' => __( 'Send e-mail via Amazon SES?', $this->textdomain ),
				'help' => __( 'Clicking this will override many of the settings defined below. You will need to input your AWS details password below.', $this->textdomain )),
			'AWSAccessKeyID' => array( 'input' => 'text', 
				'label' => __( 'Amazon AWS Access Key', $this->textdomain ),
				'help' => __( 'Set your Amazon AWS Access Key.', $this->textdomain ) ),
			'AWSSecretKey' => array( 'input' => 'text', 
				'label' => __( 'Amazon AWS Secret Key', $this->textdomain ),
				'help' => __( 'Set your Amazon AWS Secret Key.', $this->textdomain ) ),
			'from_email' => array( 'input' => 'text', 'default' => get_bloginfo('admin_email'),
				'label' => __( 'From e-mail', $this->textdomain ),
				'help' => __( 'Sets the From: e-mail address for all outgoing messages. Leave blank to use the WordPress default. This value will be used even if you don\'t enable Amazon AES or SMTP.<br />NOTE: For Amazon SES, the From: e-mail address needs to have been validated.<br />For SMTP, this may not take effect depending on your mail server and settings, especially if using SMTPAuth (such as for GMail).', $this->textdomain ) ),
			'from_name'	=> array( 'input' => 'text', 'default' => get_bloginfo('name'),
				'label' => __( 'Sender name', $this->textdomain ),
				'help' => __( 'Sets the From name for all outgoing messages. Leave blank to use the WordPress default. This value will be used even if you don\'t enable Amazon SES or SMTP.', $this->textdomain ) ),	
			'use_dkim' => array( 'input' => 'checkbox', 'default' => false,
				'label' => __( 'Use DKIM validation?', $this->textdomain ),
				'help' => __( 'Clicking this requires you to enter your DKIM details below. Also you will need to set up your DNS DKIM record.', $this->textdomain )),
			'dkim_domain' => array( 'input' => 'text', 
				'label' => __( 'DKIM domain', $this->textdomain ),  'default' => $userDomain[1],
				'help' => __( 'Set the DKIM domain to send from.', $this->textdomain ) ),
			'dkim_private' => array( 'input' => 'text', 
				'label' => __( 'Path to the DKIM private key', $this->textdomain ),  'default' => '.htkeyprivate',
				'help' => __( 'Set the path relative to the website root directory (exclude leading forward slash).', $this->textdomain ) ),
			'dkim_selector' => array( 'input' => 'text', 
				'label' => __( 'DKIM selector', $this->textdomain ),  'default' => 'ses',
				'help' => __( 'Set the DKIM selector for this key.', $this->textdomain ) ),
			'dkim_passphrase' => array( 'input' => 'text',
				'label' => __( 'DKIM key passphrase', $this->textdomain ),
				'help' => __( 'Set the passphrase for the DKIM private key.', $this->textdomain ) ),
				
			'use_smtp' => array( 'input' => 'checkbox', 'default' => false,
				'label' => __( 'Send e-mail via SMTP?', $this->textdomain ),
				'help' => __( 'Note that if Amazon SES is selected above, SMTP settings will be ignored', $this->textdomain )),					
				
			'use_gmail' => array( 'input' => 'checkbox', 'default' => false,
				'label' => __( 'Send e-mail via GMail?', $this->textdomain ),
				'help' => __( 'Clicking this will override many of the settings defined below. You will need to input your GMail username and password below.<br />If Amazon SES is selected above then Gmail Settings will be ignored', $this->textdomain ),
				'input_attributes' => 'onclick="return configure_gmail();"' ),
			'host' => array( 'input' => 'text', 'default' => 'localhost', 'require' => true,
				'label' => __( 'SMTP host', $this->textdomain ),
				'help' => __( 'If "localhost" doesn\'t work for you, check with your host for the SMTP hostname.', $this->textdomain ) ),
			'port' => array( 'input' => 'short_text', 'default' => 25, 'datatype' => 'int', 'required' => true,
				'label' => __( 'SMTP port', $this->textdomain ),
				'help' => __( 'This is generally 25.', $this->textdomain ) ),
			'smtp_secure' => array( 'input' => 'select', 'default' => 'None',
				'label' => __( 'Secure connection prefix', $this->textdomain ),
				'options' => array( '', 'ssl', 'tls' ),
				'help' => __( 'Sets connection prefix for secure connections (prefix method must be supported by your PHP install and your SMTP host)', $this->textdomain ) ),
			'smtp_auth'	=> array( 'input' => 'checkbox', 'default' => false,
				'label' => __( 'Use SMTPAuth?', $this->textdomain ),
				'help' => __( 'If checked, you must provide the SMTP username and password below', $this->textdomain ) ),
			'smtp_user'	=> array( 'input' => 'text', 'default' => '',
				'label' => __( 'SMTP username', $this->textdomain ),
				'help' => '' ),
			'smtp_pass'	=> array( 'input' => 'password', 'default' => '',
				'label' => __( 'SMTP password', $this->textdomain ),
				'help' => '' ),
			'wordwrap' => array( 'input' => 'short_text', 'default' => '55',
				'label' => __( 'Wordwrap length', $this->textdomain ),
				'help' => __( 'Sets word wrapping on the body of the message to a given number of characters.', $this->textdomain ) ),
			'debug' => array( 'input' => 'checkbox', 'default' => false,
				'label' => __( 'Enable SMTP debugging?', $this->textdomain ),
				'help' => __( 'Only check this if you are experiencing problems and would like more error reporting to occur. <em>Uncheck this once you have finished debugging.</em>', $this->textdomain ) ),
			'hr' => array()
			
		);
	}

	/**
	 * Override the plugin framework's register_filters() to actually actions against filters.
	 *
	 * @return void
	 */
	public function register_filters() {
		global $pagenow;
		if ( 'options-general.php' == $pagenow )
		add_action( 'admin_print_footer_scripts',          array( &$this, 'add_js' ) );
		add_filter( 'wp_mail', array( &$this, 'anatta_swap_php_mailer') ); //earliest call before phpmailer is loaded.
		add_action( 'admin_init',                              array( &$this, 'maybe_send_test' ) );
		add_action( 'phpmailer_init',                          array( &$this, 'phpmailer_init' ) );
		add_action( 'admin_init',                              array( &$this, 'maybe_check_DKIM' ) );
		add_filter( 'wp_mail_from',                            array( &$this, 'wp_mail_from' ) );
		add_filter( 'wp_mail_from_name',                       array( &$this, 'wp_mail_from_name' ) );
		add_action( $this->get_hook( 'after_settings_form' ),  array( &$this, 'send_test_form' ) );
		add_filter( $this->get_hook( 'before_update_option' ), array( &$this, 'maybe_gmail_override' ) );
	}
	
	

	public function anatta_swap_php_mailer($mail_content) {
	
	global $phpmailer;

	// Use our PHP mailer before wordpress gets a chance to set it's own
		if ( !is_object( $phpmailer ) || !is_a( $phpmailer, 'PHPMailer' ) ) {
			require_once 'php-mailer/class.phpmailer.php';
			require_once 'php-mailer/class.smtp.php';
			$phpmailer = new PHPMailer( true );
		}	
			
		return($mail_content);

	}


		

	/**
	 * Outputs the text above the setting form
	 *
	 * @return void (Text will be echoed.)
	 */
	public function options_page_description() {
		$options = $this->get_options();
		parent::options_page_description( __( 'Configure Mail Settings', $this->textdomain ) );
		if ( ! empty( $this->error_msg ) )
			echo $this->error_msg;
		$str = '<a href="#test">' . __( 'test', $this->textdomain ) . '</a>';
		if ( empty( $options['host'] ) )
			echo '<div class="error"><p>' . __( 'Amazon SES or SMTP mailing is currently <strong>NOT ENABLED</strong> because no details have been specified.' ) . '</p></div>';
		echo '<p>' . sprintf( __( 'After you have configured your Mail settings, use the %s to send a test message to yourself.', $this->textdomain ), $str ) . '</p>';
	}

	/**
	 * Outputs JavaScript
	 *
	 * @return void (Text is echoed.)
	 */
	public function add_js() {
		$alert = __( 'Be sure to specify your full GMail email address (including the "@gmail.com") as the SMTP username, and your GMail password as the SMTP password.', $this->textdomain );
		$checked = $this->gmail_config['smtp_auth'] ? '1' : '';
		echo <<<JS
		<script type="text/javascript">
			function configure_gmail() {
				// The .attr('checked') == true is only for pre-WP3.2
				if (jQuery('#use_gmail').attr('checked') == 'checked' || jQuery('#use_gmail').attr('checked') == true) {
					jQuery('#host').val('{$this->gmail_config['host']}');
					jQuery('#port').val('{$this->gmail_config['port']}');
					if (jQuery('#use_gmail').attr('checked') == 'checked')
						jQuery('#smtp_auth').prop('checked', $checked);
					else // pre WP-3.2 only
						jQuery('#smtp_auth').attr('checked', {$this->gmail_config['smtp_auth']});
					jQuery('#smtp_secure').val('{$this->gmail_config['smtp_secure']}');
					if (!jQuery('#smtp_user').val().match(/.+@gmail.com$/) ) {
						jQuery('#smtp_user').val('your_name@gmail.com').focus().get(0).setSelectionRange(0,8);
					}
					alert('{$alert}');
					return true;
				}
			}
		</script>

JS;
	}

	/**
	 * If the 'Use GMail' option is checked, the GMail settings will override whatever the user may have provided
	 *
	 * @param array $options The options array prior to saving
	 * @return array The options array with GMail settings taking precedence, if relevant
	 */
	public function maybe_gmail_override( $options ) {
		// If GMail is to be used, those settings take precendence
		if ( $options['use_gmail'] )
			$options = wp_parse_args( $this->gmail_config, $options );
		return $options;
	}

	/**
	 * Sends test e-mail if form was submitted requesting to do so.
	 *
	 */
	public function maybe_send_test() {
		if ( isset( $_POST[$this->get_form_submit_name( 'submit_test_email' )] ) ) {
			check_admin_referer( $this->nonce_field );
			$user = wp_get_current_user();
			$email = $user->user_email;
			$timestamp = current_time( 'mysql' );
			$message = sprintf( __( 'Hi, this is the %s plugin e-mailing you a test message from your WordPress blog.', $this->textdomain ), $this->name );
			$message .= "\n\n";
			$message .= sprintf( __( 'This message was sent with this time-stamp: %s', $this->textdomain ), $timestamp );
			$message .= "\n\n";
			$message .= __( 'Congratulations!  Your blog is properly configured to send e-mail.', $this->textdomain );
			
            
            wp_mail( $email, __( 'Test message from your WordPress blog', $this->textdomain ), $message );

			// Check success
			global $phpmailer;
			if ( $phpmailer->ErrorInfo != "" ) {
				$this->error_msg  = '<div class="error"><p>' . __( 'An error was encountered while trying to send the test e-mail.', $this->textdomain ) . '</p>';
				$this->error_msg .= '<blockquote style="font-weight:bold;">';
				$this->error_msg .= '<p>' . $phpmailer->ErrorInfo . '</p>';
				$this->error_msg .= '</p></blockquote>';
				$this->error_msg .= '</div>';
			} else {
				$this->error_msg  = '<div class="updated"><p>' . __( 'Test e-mail sent.', $this->textdomain ) . '</p>';
				$this->error_msg .= '<p>' . sprintf( __( 'The body of the e-mail includes this time-stamp: %s.', $this->textdomain ), $timestamp ) . '</p></div>';
			}
		}
	}
    
    public function maybe_check_DKIM() {
		if ( isset( $_POST[$this->get_form_submit_name( 'check_DKIM' )] ) ) {
			check_admin_referer( $this->nonce_field );
			$emailKey = $_POST[$this->get_form_submit_name( 'check_address' )];
            $email = $emailKey . "@www.brandonchecketts.com";
            $timestamp = current_time( 'mysql' );
			$checkURL = '<a href="http://www.brandonchecketts.com/emailtest.php?email=' . $email . '" target="_blank" >by clicking here</a>';
			$message = sprintf( __( 'Hi, this is the %s plugin e-mailing you a test message from your WordPress blog.', $this->textdomain ), $this->name );
			$message .= "\n\n";
			$message .= sprintf( __( 'This message was sent with this time-stamp: %s', $this->textdomain ), $timestamp );
			$message .= "\n\n";
			$message .= __( 'Congratulations!  Your blog is sending e-mail OK, but check below to see if your DKIM signature passed.', $this->textdomain );
			
            
            wp_mail( $email, __( 'Test message from your WordPress blog', $this->textdomain ), $message );

			// Check success
			global $phpmailer;
			if ( $phpmailer->ErrorInfo != "" ) {
				$this->error_msg  = '<div class="error"><p>' . __( 'An error was encountered while trying to send the test e-mail.', $this->textdomain ) . '</p>';
				$this->error_msg .= '<blockquote style="font-weight:bold;">';
				$this->error_msg .= '<p>' . $phpmailer->ErrorInfo . '</p>';
				$this->error_msg .= '</p></blockquote>';
				$this->error_msg .= '</div>';
			} else {
				$this->error_msg  = '<div class="updated"><p>' . __( 'Test e-mail sent.', $this->textdomain ) . '</p>';
				$this->error_msg .= '<p>' . sprintf( __( 'You can check the results %s.', $this->textdomain ), $checkURL ) . '</p></div>';
			}
		}
	}

	/*
	 * Outputs form to send test e-mail.
	 *
	 * @return void (Text will be echoed.)
	 */
	public function send_test_form() {
		$user = wp_get_current_user();
		$email = $user->user_email;
		$action_url = $this->form_action_url();
		echo '<div class="wrap"><h2><a name="test"></a>' . __( 'Send A Test Message', $this->textdomain ) . "</h2>\n";
		echo '<p>' . __( 'Click the button below to send a test email to yourself to see if things are working.  Be sure to save any changes you made to the form above before sending the test e-mail.  Bear in mind it may take a few minutes for the e-mail to wind its way through the internet.', $this->textdomain ) . "</p>\n";
		echo '<p>' . sprintf( __( 'This e-mail will be sent to your e-mail address, %s.', $this->textdomain ), $email ) . "</p>\n";
		echo '<p><em>You must save any changes to the form above before attempting to send a test e-mail.</em></p>';
		echo "<form name='configure_smtp' action='$action_url' method='post'>\n";
		wp_nonce_field( $this->nonce_field );
		echo '<input type="hidden" name="' . $this->get_form_submit_name( 'submit_test_email' ) .'" value="1" />';
		echo '<div class="submit"><input type="submit" name="Submit" value="' . esc_attr__( 'Send test e-mail', $this->textdomain ) . '" /></form></div>';
        echo '<div class="wrap"><h2>Check DKIM Settings</h2>';
        echo "<p>Pressing the button below will send a test email to Brandon Checkett's online <a href='http://www.brandonchecketts.com/emailtest.php' target='_blank'>DKIM checker tool</a>.  If the email is successfully sent, then a link to the retrieve result will be posted at the top of the page.</p><p><strong>Note:</strong> If using Amazon SES, you mush have Production Access to be able to send to a public email.</p>";
        echo '<p><em>You must save any changes to the form above before attempting to send a test e-mail.</em></p>';
        echo "<form name='check_DKIM' action='$action_url' method='post'>\n";
		wp_nonce_field( $this->nonce_field );
		echo '<input type="hidden" name="' . $this->get_form_submit_name( 'check_DKIM' ) .'" value="1" /><input type="hidden" name=' . $this->get_form_submit_name( 'check_address' ) .' value=' . $this->get_random_string(10) . ' />';
        echo '<div class="submit"><input type="submit" name="Check" value="' . esc_attr__( 'Check DKIM setup', $this->textdomain ) . '" /></form></div>';
        echo '<div class="wrap"><h2>Thanks for using this plugin</h2>';
        echo '<p>I hope this plugin has saved you time and trouble and given you a pain free integration of Amazon SES and/or DKIM (two particularly obtuse protocols).</p><p>Many hours of sweat and tears have gone into making this plugin work.  If it has been of value to you, buying me a coffee or a beer would be a great way to show your appreciation and would certainly encourage me to continue publishing random pieces of code like this.</p><p>Many thanks<br />Nick</p>';
        echo '<form action="https://www.paypal.com/cgi-bin/webscr" method="post"><input type="hidden" name="cmd" value="_donations"><input type="hidden" name="business" value="paypal@anatta.net"><input type="hidden" name="lc" value="US"><input type="hidden" name="item_name" value="Anatta Limited"><input type="hidden" name="no_note" value="0"><input type="hidden" name="currency_code" value="USD"><input type="hidden" name="bn" value="PP-DonationsBF:btn_donateCC_LG.gif:NonHostedGuest"><input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!"></form></div>'; 
	}

	/**
	 * Configures PHPMailer object during its initialization stage
	 *
	 * @param object $phpmailer PHPMailer object
	 * @return void
	 */
	public function phpmailer_init( $phpmailer ) {
		$options = $this->get_options();
		
        if ( $options['use_aws'] ) {
        	$phpmailer->IsAmazonSES();
        	$phpmailer->AddAmazonSESKey($options['AWSAccessKeyID'], $options['AWSSecretKey']);
        }
        
		if (( $options['use_smtp'] || $options['use_gmail'] ) && !$options['use_aws']) {
        
        	// Don't configure for SMTP if no host is provided.
			if ( empty( $options['host'] ) )
				return;
        	$phpmailer->IsSMTP();
            $phpmailer->Host = $options['host'];
			$phpmailer->Port = $options['port'] ? $options['port'] : 25;
			$phpmailer->SMTPAuth = $options['smtp_auth'] ? $options['smtp_auth'] : false;
			if ( $phpmailer->SMTPAuth ) {
				$phpmailer->Username = $options['smtp_user'];
				$phpmailer->Password = $options['smtp_pass'];
			}
			if ( $options['smtp_secure'] != '' )
				$phpmailer->SMTPSecure = $options['smtp_secure'];
            if ( $options['debug'] )
				$phpmailer->SMTPDebug = true;
            }
            
		 if ( $options['wordwrap'] > 0 )
			$phpmailer->WordWrap = $options['wordwrap']; 
         
         if ( $options['use_dkim'] ) {
        	$phpmailer->DKIM_domain = $options['dkim_domain'];
			$phpmailer->DKIM_private = ABSPATH . $options['dkim_private'];
			$phpmailer->DKIM_selector = $options['dkim_selector'];
			$phpmailer->DKIM_passphrase = $options['dkim_passphrase'];
        }     
	}

	/**
	 * Configures the "From:" e-mail address for outgoing e-mails
	 *
	 * @param string $from The "from" e-mail address used by WordPress by default
	 * @return string The potentially new "from" e-mail address, if overridden via the plugin's settings.
	 */
	public function wp_mail_from( $from ) {
		$options = $this->get_options();
		if ( ! empty( $options['from_email'] ) )
			$from = $options['from_email'];
		return $from;
	}

	/**
	 * Configures the "From:" name for outgoing e-mails
	 *
	 * @param string $from The "from" name used by WordPress by default
	 * @return string The potentially new "from" name, if overridden via the plugin's settings.
	 */
	public function wp_mail_from_name( $from_name ) {
		$options = $this->get_options();
		if ( ! empty( $options['from_name'] ) )
			$from_name = wp_specialchars_decode( $options['from_name'], ENT_QUOTES );
		return $from_name;
	}

public function get_random_string($length) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $string ='';    
    for ($p = 0; $p < $length; $p++) {
        $string .= $characters[mt_rand(0, strlen($characters))];
    }
    return $string;
}

} // end ConfigureAES_DKIM_SMTP

// NOTICE: The 'c2c_configure_smtp' global is deprecated and will be removed in the plugin's version 3.0.
// Instead, use: c2c_ConfigureSMTP::$instance
$GLOBALS['c2c_configure_aes_dkim_smtp'] = new c2c_ConfigureAES_DKIM_SMTP();

endif; // end if !class_exists()

?>
