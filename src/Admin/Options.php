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

	/**
	 * Saved options.
	 *
	 * @since 1.0.3
	 * @var   array
	 */
	public array $options = array();

	/**
	 * Option group and option name.
	 *
	 * @since 1.0.3
	 * @var   string
	 */
	public const OPTION_NAME = 'site_settings';

	/**
	 * Admin menu slug.
	 *
	 * @since 1.0.3
	 * @var   string
	 */
	protected string $menu_slug = 'site-settings';

	/**
	 * Option key for the remote media URL.
	 *
	 * @since 1.0.3
	 * @var   string
	 */
	protected string $remote_media_option = 'remote_media_url';

	/**
	 * Default remote media URL.
	 *
	 * @since 1.0.3
	 * @var   string
	 */
	protected string $remote_media_url = '';

	/**
	 * Option key for the enabled toggle.
	 *
	 * @since 1.0.3
	 * @var   string
	 */
	protected string $remote_media_enabled_option = 'remote_media_enabled';

	/**
	 * Environments on which remote media serving is permitted.
	 *
	 * Filterable via the `site_functionality_remote_media_environments` filter.
	 *
	 * @since 1.0.3
	 * @var   array
	 */
	protected array $enabled_environments = array(
		'local',
		'development',
		'staging',
	);

	/**
	 * Required capability to manage settings.
	 *
	 * @since 1.0.3
	 * @var   string
	 */
	protected string $capabilities = 'manage_options';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct( $version, $plugin_name ) {
		parent::__construct( $version, $plugin_name );
		$this->init();
	}

	/**
	 * Init
	 *
	 * @return void
	 */
	public function init() {
		$this->options             = (array) get_option( self::OPTION_NAME, array() );
		$this->enabled_environments = (array) apply_filters( 'site_functionality_remote_media_environments', $this->enabled_environments );

		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'init_settings' ) );

		if ( in_array( wp_get_environment_type(), $this->enabled_environments, true ) && ! empty( $this->options[ $this->remote_media_enabled_option ] ) ) {
			add_filter( 'upload_dir', array( $this, 'serve_remote_media' ) );
		}
	}

	/**
	 * Register the options page under Settings.
	 *
	 * @since  1.0.3
	 * @return void
	 */
	public function add_admin_menu(): void {
		add_options_page(
			esc_html__( 'Site Settings', 'site-functionality' ),
			esc_html__( 'Site Settings', 'site-functionality' ),
			$this->capabilities,
			$this->menu_slug,
			array( $this, 'render_page' ),
			1
		);
	}

	/**
	 * Register settings, section, and field.
	 *
	 * @since  1.0.3
	 * @return void
	 */
	public function init_settings(): void {
		register_setting( self::OPTION_NAME, self::OPTION_NAME );

		add_settings_section(
			self::OPTION_NAME . '_section',
			'',
			false,
			$this->menu_slug
		);

		add_settings_field(
			$this->remote_media_enabled_option,
			__( 'Enable Remote Media', 'site-functionality' ),
			array( $this, 'render_remote_media_enabled' ),
			$this->menu_slug,
			self::OPTION_NAME . '_section'
		);

		add_settings_field(
			$this->remote_media_option,
			__( 'Remote Media URL', 'site-functionality' ),
			array( $this, 'render_remote_media_url' ),
			$this->menu_slug,
			self::OPTION_NAME . '_section'
		);
	}

	/**
	 * Render the settings page.
	 *
	 * @since  1.0.3
	 * @return void
	 */
	public function render_page(): void {
		if ( ! current_user_can( $this->capabilities ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'site-functionality' ) );
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( self::OPTION_NAME );
				do_settings_sections( $this->menu_slug );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render the enabled toggle field.
	 *
	 * @since  1.0.3
	 * @return void
	 */
	public function render_remote_media_enabled(): void {
		$enabled = ! empty( $this->options[ $this->remote_media_enabled_option ] );
		?>
		<input
			type="checkbox"
			id="<?php echo esc_attr( $this->remote_media_enabled_option ); ?>"
			name="<?php echo esc_attr( self::OPTION_NAME . '[' . $this->remote_media_enabled_option . ']' ); ?>"
			value="1"
			<?php checked( $enabled ); ?>
		/>
		<p class="description"><?php printf( esc_html__( 'Only active on: %s.', 'site-functionality' ), esc_html( implode( ', ', $this->enabled_environments ) ) ); ?></p>
		<?php
	}

	/**
	 * Render the remote media URL input field.
	 *
	 * @since  1.0.3
	 * @return void
	 */
	public function render_remote_media_url(): void {
		$value = $this->options[ $this->remote_media_option ] ?? $this->remote_media_url;
		?>
		<input
			type="text"
			id="<?php echo esc_attr( $this->remote_media_option ); ?>"
			name="<?php echo esc_attr( self::OPTION_NAME . '[' . $this->remote_media_option . ']' ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
		/>
		<?php
	}

	/**
	 * Rewrite upload URLs to point to the remote site on local environments.
	 *
	 * @since  1.0.3
	 * @param  array $dirs Upload directory data.
	 * @return array
	 */
	public function serve_remote_media( array $dirs ): array {
		$remote = $this->options[ $this->remote_media_option ] ?? $this->remote_media_url;

		if ( ! $remote ) {
			return $dirs;
		}

		$remote          = untrailingslashit( $remote );
		$local           = untrailingslashit( get_option( 'siteurl' ) );
		$dirs['baseurl'] = str_replace( $local, $remote, $dirs['baseurl'] );
		$dirs['url']     = str_replace( $local, $remote, $dirs['url'] );

		return $dirs;
	}
}
