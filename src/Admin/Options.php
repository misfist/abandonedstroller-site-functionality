<?php
/**
 * Site Options Page
 *
 * @since   1.0.0
 * @package Core_Functionality
 */

namespace SiteFunctionality\Admin;

use SiteFunctionality\Abstracts\Base;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Options extends Base {

	public $options = array();

	public const OPTION_NAME = 'site_settings';

	public const MENU_SLUG = 'site-settings';

	/**
	 * Remove media id
	 *
	 * @var string
	 */
	public $remote_media_option = 'remote_media_url';

	/**
	 * Remove media url
	 *
	 * @var string
	 */
	public $remote_media_url = 'https://abandonedstroller.com';

	/**
	 * Setting capabilities
	 *
	 * @var string
	 */
	public $capabilities = 'manage_options';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct( $version, $plugin_name ) {
		parent::__construct( $version, $plugin_name );
		$this->init();
		$this->options = \get_option( self::OPTION_NAME );
	}

	/**
	 * Init
	 *
	 * @return void
	 */
	public function init() {
		\add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		\add_action( 'admin_init', array( $this, 'init_settings' ) );
	}

	/**
	 * Add Options Page
	 *
	 * @link https://developer.wordpress.org/reference/functions/add_options_page/
	 *
	 * @return void
	 */
	public function add_admin_menu() {

		\add_options_page(
			\esc_html__( 'Site Settings', 'site-functionality' ), // Page Title
			\esc_html__( 'Site Settings', 'site-functionality' ), // Menu Title
			$this->capabilities, // Capability
			self::MENU_SLUG, // Menu Slug
			array( $this, 'render_page' ), // Callback
			1 // Position
		);
	}

	/**
	 * Register Settings
	 *
	 * @return void
	 */
	public function init_settings() {

		/**
		 * @link https://developer.wordpress.org/reference/functions/register_setting/
		 */
		\register_setting(
			self::OPTION_NAME, // Option Group
			self::OPTION_NAME // Option Name
		);

		/**
		 * @link https://developer.wordpress.org/reference/functions/add_settings_section/
		 */
		\add_settings_section(
			self::OPTION_NAME . '_section', // ID
			'', // Title
			false, // Callback
			sself::MENU_SLUG // Page
		);

		/**
		 * https://developer.wordpress.org/reference/functions/add_settings_field/
		 */
		\add_settings_field(
			'remote_media_url', // ID
			__( 'Serve Media from Remote URL', 'site-functionality' ), // Title
			array( $this, 'render_remote_media_url' ), //Callback
			self::MENU_SLUG, // Page
			self::OPTION_NAME . '_section' // Section
		);
	}

	/**
	 * Renders the Site Settings page
	 *
	 * @since  
	 * @return void
	 */
	public function render_page(): void {
		if ( ! current_user_can( $this->capabilities ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'site-functionality' ) );
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<?php
			if ( isset( $_GET['settings-updated'] ) ) {
				wp_admin_notice(
					__( 'Settings saved.', 'site-functionality' ),
					array(
						'type' => 'success',
					)
				);
			}
			?>
			<form action="options.php" method="post">
				<?php
				settings_fields( self::OPTION_NAME );
				do_settings_sections( self::OPTION_NAME );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render Field
	 *
	 * @return void
	 */
	function render_remote_media_url() {
		$path = get_option( $this->options['remote_media_url'], $this->remote_media_url );
		?>
		<input
			type="text"
			id="<?php echo esc_attr( $this->remote_media_option ); ?>"
			name="<?php echo esc_attr( $this->remote_media_option ); ?>"
			value="<?php echo esc_attr( $path ); ?>"
			class="regular-text"
		/>
		<?php 
	}
}
