<?php
/**
 * Loads WCMp packages from the /packages directory.
 *
 * @package WCMp
 */

defined( 'ABSPATH' ) || exit;

/**
 * Packages class.
 *
 * @since 3.5.0
 */
class Packages {

	/**
	 * Array of package names and their main package classes.
	 *
	 * @var array Key is the package directory, value is the main package class which handles init.
	 */
	protected static $packages = array(
		//'wcmp-blocks/wcmp-blocks.php'   => 'WCMp_Blocks',
	);

	/**
	 * Construct package loader.
	 *
	 * @since 3.5.0
	 */
	public function __construct() {
		self::load_packages();
	}

	/**
	 * Checks a package exists by looking for it's directory.
	 *
	 * @param string $package Package name.
	 * @return boolean
	 */
	public static function package_exists( $package_dir ) {
		return file_exists( dirname( __DIR__ ) . '/packages/' . $package_dir );
	}
        
        /**
	 * Loads specific package file.
	 *
	 * Each package should include file first to load package class.
         * 
         * @param string $package_dir Package directory.
	 * @return void
	 */
        protected static function load_package( $package_dir ) {
            require_once $package_dir;
        }

	/**
	 * Loads packages after plugins_loaded hook.
	 *
	 * Each package should include an init file which loads the package so it can be used by core.
	 */
	protected static function load_packages() {
		$packages = apply_filters( 'wcmp_load_default_packages', self::$packages );
		if( $packages ) :
			foreach ( $packages as $package_dir => $package_class ) {
				if ( ! self::package_exists( $package_dir ) ) {
					self::missing_package( $package_dir );
					continue;
				}
				if( apply_filters( 'wcmp_load_package_' . $package_dir, false, $package_class ) ) {
					self::load_package( $package_dir );
					call_user_func( array( $package_class, 'init' ) );
				}
			}
		endif;
	}

	/**
	 * If a package is missing, add an admin notice.
	 *
	 * @param string $package Package name.
	 */
	protected static function missing_package( $package ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log(  // phpcs:ignore
				sprintf(
					/* Translators: %s package name. */
					esc_html__( 'Missing the WCMp %s package', 'dc-woocommerce-multi-vendor' ),
					'<code>' . esc_html( $package ) . '</code>'
				)
			);
		}
		add_action(
			'admin_notices',
			function() use ( $package ) {
				?>
				<div class="notice notice-error">
					<p>
						<strong>
							<?php
							printf(
								/* Translators: %s package name. */
								esc_html__( 'Missing the WCMp %s package', 'dc-woocommerce-multi-vendor' ),
								'<code>' . esc_html( $package ) . '</code>'
							);
							?>
						</strong>
					</p>
				</div>
				<?php
			}
		);
	}
}
new Packages();