<?php
/**
 * @package WordPress
 * @subpackage BuddyBoss Media
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if( ! class_exists( 'BuddyBoss_Media_Plugin' ) ):
/**
 *
 * BuddyBoss Media Plugin Main Controller
 * **************************************
 *
 *
 */
class BuddyBoss_Media_Plugin
{
	/* Includes
	 * ===================================================================
	 */

	/**
	 * Most WordPress/BuddyPress plugin have the includes in the function
	 * method that loads them, we like to keep them up here for easier
	 * access.
	 * @var array
	 */
	private $main_includes = array(

		// Core
		'media-functions',
		'media-compat',
		'media-pagination',
		'media-template',

		// Types
		'types/photo-class',
		'types/photo-hooks',
		'types/photo-screens',
		'types/photo-template',

		// 3rd-Party/Vendor
		'vendor/image-rotation-fixer'

	);

	/**
	 * Admin includes
	 * @var array
	 */
	private $admin_includes = array(
		'admin',
		'media-functions',
	);

	/* Plugin Options
	 * ===================================================================
	 */

	/**
	 * Default options for the plugin, the strings are
	 * run through localization functions during instantiation,
	 * and after the user saves options the first time they
	 * are loaded from the DB.
	 *
	 * @var array
	 */
	private $default_options = array(
		'enabled'             => true,

		'rotation_fix'        => false,

		'notices_legacy'      => true,

		'UPDATE_MENUS'        => true,

		'INJECT_MARKUP'       => true,
		'ADD_TPL_HOOKS'       => true,
		'LOAD_CSS'            => true,
		'LOAD_JS'             => true,
		'USE_WP_CACHE'        => true,

		'TYPES'               => array( 'photo', 'video' ),
		'ACTIVE_TYPES'        => array( 'photo' )
	);

	/**
	 * This options array is setup during class instantiation, holds
	 * default and saved options for the plugin.
	 *
	 * @var array
	 */
	public $options = array();

	/**
	 * Is BuddyPress installed and activated?
	 * @var boolean
	 */
	public $bp_enabled = false;

	/* Version
	 * ===================================================================
	 */

	/**
	 * Plugin codebase version
	 * @var string
	 */
	public $version = '0.0.0';

	/**
	 * Plugin database version
	 * @var string
	 */
	public $db_version = '0.0.0';

	/* Paths
	 * ===================================================================
	 */
	public $file            = '';
	public $basename        = '';
	public $plugin_dir      = '';
	public $plugin_url      = '';
	// public $includes_dir = '';
	// public $includes_url = '';
	public $lang_dir        = '';
	public $assets_dir      = '';
	public $assets_url      = '';

	/* Component State
	 * ===================================================================
	 */
	public $current_type   = '';
	public $current_item   = '';
	public $current_action = '';
	public $is_single_item = false;
	public $is_legacy      = false;

	/* Magic
	 * ===================================================================
	 */

	/**
	 * BuddyBoss Media uses many variables, most of which can be filtered to
	 * customize the way that it works. To prevent unauthorized access,
	 * these variables are stored in a private array that is magically
	 * updated using PHP 5.2+ methods. This is to prevent third party
	 * plugins from tampering with essential information indirectly, which
	 * would cause issues later.
	 *
	 * @see BuddyBoss_Media_Plugin::setup_globals()
	 * @var array
	 */
	private $data;

	/* Singleton
	 * ===================================================================
	 */

	/**
	 * Main BuddyBoss Media Instance.
	 *
	 * BuddyBoss Media is great
	 * Please load it only one time
	 * For this, we thank you
	 *
	 * Insures that only one instance of BuddyBoss Media exists in memory at any
	 * one time. Also prevents needing to define globals all over the place.
	 *
	 * @since BuddyBoss Media (1.0.0)
	 *
	 * @static object $instance
	 * @uses BuddyBoss_Media_Plugin::setup_globals() Setup the globals needed.
	 * @uses BuddyBoss_Media_Plugin::setup_actions() Setup the hooks and actions.
	 * @uses BuddyBoss_Media_Plugin::setup_textdomain() Setup the plugin's language file.
	 * @see buddyboss_media()
	 *
	 * @return BuddyBoss Media The one true BuddyBoss.
	 */
	public static function instance()
	{
		// Store the instance locally to avoid private static replication
		static $instance = null;

		// Only run these methods if they haven't been run previously
		if ( null === $instance )
		{
			$instance = new BuddyBoss_Media_Plugin;
			$instance->setup_globals();
			$instance->setup_actions();
			$instance->setup_textdomain();
		}

		// Always return the instance
		return $instance;
	}

	/* Magic Methods
	 * ===================================================================
	 */

	/**
	 * A dummy constructor to prevent BuddyBoss Media from being loaded more than once.
	 *
	 * @since BuddyBoss Media (1.0.0)
	 * @see BuddyBoss_Media_Plugin::instance()
	 * @see buddypress()
	 */
	private function __construct() { /* Do nothing here */ }

	/**
	 * A dummy magic method to prevent BuddyBoss Media from being cloned.
	 *
	 * @since BuddyBoss Media (1.0.0)
	 */
	public function __clone() { _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'buddyboss-media' ), '1.7' ); }

	/**
	 * A dummy magic method to prevent BuddyBoss Media from being unserialized.
	 *
	 * @since BuddyBoss Media (1.0.0)
	 */
	public function __wakeup() { _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'buddyboss-media' ), '1.7' ); }

	/**
	 * Magic method for checking the existence of a certain custom field.
	 *
	 * @since BuddyBoss Media (1.0.0)
	 */
	public function __isset( $key ) { return isset( $this->data[$key] ); }

	/**
	 * Magic method for getting BuddyBoss Media varibles.
	 *
	 * @since BuddyBoss Media (1.0.0)
	 */
	public function __get( $key ) { return isset( $this->data[$key] ) ? $this->data[$key] : null; }

	/**
	 * Magic method for setting BuddyBoss Media varibles.
	 *
	 * @since BuddyBoss Media (1.0.0)
	 */
	public function __set( $key, $value ) { $this->data[$key] = $value; }

	/**
	 * Magic method for unsetting BuddyBoss Media variables.
	 *
	 * @since BuddyBoss Media (1.0.0)
	 */
	public function __unset( $key ) { if ( isset( $this->data[$key] ) ) unset( $this->data[$key] ); }

	/**
	 * Magic method to prevent notices and errors from invalid method calls.
	 *
	 * @since BuddyBoss Media (1.0.0)
	 */
	public function __call( $name = '', $args = array() ) { unset( $name, $args ); return null; }

	/* Plugin Specific, Setup Globals, Actions, Includes
	 * ===================================================================
	 */

	/**
	 * Array of 'media types', used to load files in the types
	 * folder and handle different kinds of files.
	 *
	 * Think of 'types' as a templating and processing module,
	 * each type follows the same class structure and can either
	 * attach to an existing type's BP component, add some functionality
	 * via hooks and filters, or extend it's own BP component.
	 *
	 * This is deprecated, $types and $active_types are loaded from
	 * the options so user's can selectively enable type modules.
	 *
	 * @since BuddyBoss Media (1.0.0)
	 *
	 * @deprecated Deprecated since pre-1.0 release during beta testing
	 *
	 * @var array Active components.
	 */
	public $active_types = array( 'photo', 'video' );

	/**
	 * Setup BuddyBoss Media plugin global variables.
	 *
	 * @since BuddyBoss Media (1.0.0)
	 * @access private
	 *
	 * @uses plugin_dir_path() To generate BuddyBoss Media plugin path.
	 * @uses plugin_dir_url() To generate BuddyBoss Media plugin url.
	 * @uses apply_filters() Calls various filters.
	 */
	private function setup_globals()
	{
		// DEFAULT CONFIGURATION OPTIONS
		$default_options = $this->default_options;

		$saved_options = get_option( 'buddyboss_media_plugin_options' );
		$saved_options = maybe_unserialize( $saved_options );

		$this->options = wp_parse_args( $saved_options, $default_options );

		/** Versions **************************************************/

		$this->version    = BUDDYBOSS_MEDIA_PLUGIN_VERSION;
		$this->db_version = BUDDYBOSS_MEDIA_PLUGIN_DB_VERSION;

		/** Components ************************************************/

		/**
		 * @var string Name of the current BuddyBoss Media component (primary)
		 */
		$this->current_type = '';

		/**
		 * @var string Name of the current BuddyBoss Media item (secondary)
		 */
		$this->current_item = '';

		/**
		 * @var string Name of the current BuddyBoss Media action (tertiary)
		 */
		$this->current_action = '';

		/**
		 * @var bool Displaying custom 2nd level navigation menu
		 */
		$this->is_single_item = false;

		/** Paths******************************************************/

		// BuddyBoss Media root directory
		$this->file          = BUDDYBOSS_MEDIA_PLUGIN_FILE;
		$this->basename      = plugin_basename( $this->file );
		$this->plugin_dir    = BUDDYBOSS_MEDIA_PLUGIN_DIR;
		$this->plugin_url    = BUDDYBOSS_MEDIA_PLUGIN_URL;

		// Languages
		$this->lang_dir      = dirname( $this->basename ) . '/languages/';

		// Includes
		$this->includes_dir  = $this->plugin_dir . 'includes';
		$this->includes_url  = $this->plugin_url . 'includes';

		// Templates
		$this->templates_dir = $this->plugin_dir . 'templates';
		$this->templates_url = $this->plugin_url . 'templates';

		// Assets
		$this->assets_dir    = $this->plugin_dir . 'assets';
		$this->assets_url    = $this->plugin_url . 'assets';

		/** Types *****************************************************/
		$this->types          = new stdClass();
	}

	/**
	 * Set up the default hooks and actions.
	 *
	 * @since BuddyBoss Media (1.0.0)
	 * @access private
	 *
	 * @uses register_activation_hook() To register the activation hook.
	 * @uses register_deactivation_hook() To register the deactivation hook.
	 * @uses add_action() To add various actions.
	 */
	private function setup_actions()
	{
		// Add actions to plugin activation and deactivation hooks
		// add_action( 'activate_'   . $this->basename, 'bp_activation'   );
		// add_action( 'deactivate_' . $this->basename, 'bp_deactivation' );

		// If BuddyBoss Media is being deactivated, do not add any actions
		// if ( bp_is_deactivation( $this->basename ) )
		// 	return;

		// Admin
		if ( ( is_admin() || is_network_admin() ) && current_user_can( 'manage_options' ) )
		{
			$this->load_admin();
		}

		if ( ! $this->is_enabled() )
			return;

		// Hook into BuddyPress init
		add_action( 'bp_loaded', array( $this, 'bp_loaded' ) );
	}

	/**
	 * Load plugin text domain
	 *
	 * @since BuddyBoss Media (1.0.0)
	 *
	 * @uses sprintf() Format .mo file
	 * @uses get_locale() Get language
	 * @uses file_exists() Check for language file
	 * @uses load_textdomain() Load language file
	 */
	public function setup_textdomain()
	{
		$domain = 'buddyboss-media';
		$locale = apply_filters('plugin_locale', get_locale(), $domain);

		//first try to load from wp-contents/languages/plugins/ directory
		load_textdomain($domain, WP_LANG_DIR.'/plugins/'.$domain.'-'.$locale.'.mo');

		//if not found, then load from buddboss-media/languages/ directory
		load_plugin_textdomain( 'buddyboss-media', false, $this->lang_dir );
	}

	/**
	 * We require BuddyPress to run the main components, so we attach
	 * to the 'bp_loaded' action which BuddyPress calls after it's started
	 * up. This ensures any BuddyPress related code is only loaded
	 * when BuddyPress is active.
	 *
	 * @since BuddyBoss Media (1.0.0)
	 *
	 * @return void
	 */
	public function bp_loaded()
	{
		global $bp;

		$this->bp_enabled = true;

		$this->check_legacy();

		// Detect legacy wall versions
		if ( $this->is_legacy )
		{
			// Show legacy admin notice if user hasn't disabled it
			if ( $this->option( 'notices_legacy' ) !== false )
			{
				add_action( 'admin_notices', array( $this, 'legacy_admin_notice' ) );
			}

			// Bail on legacy
			return;
		}

		$this->load_main();
	}

	/* Load
	 * ===================================================================
	 */

	/**
	 * Include required admin files.
	 *
	 * @since BuddyBoss Media (1.0.0)
	 * @access private
	 *
	 * @uses $this->do_includes() Loads array of files in the include folder
	 */
	private function load_admin()
	{
		$this->do_includes( $this->admin_includes );

		$this->admin = BuddyBoss_Media_Admin::instance();

		// var_dump( $this->admin );
	}

	/**
	 * Include required files.
	 *
	 * @since BuddyBoss Media (1.0.0)
	 * @access private
	 *
	 * @uses BuddyBoss_Media_Plugin::do_includes() Loads array of files in the include folder
	 */
	private function load_main()
	{
		$this->do_includes( $this->main_includes );

		// Component
		// $this->component = new BuddyBoss_Media_BP_Component( $this->options );

		// if ( $this->is_collection() )
		// {
		// 	$this->collection = new BuddyBoss_Media_Collection();
		// }

		// Types
		$this->types->photo = new BuddyBoss_Media_Type_Photo();
		// $this->types->audio = new BuddyBoss_Media_Type_Audio( $this->options );
		// $this->types->video = new BuddyBoss_Media_Type_Video( $this->options );
		// $this->types->archive = new BuddyBoss_Media_Type_Archive( $this->options );
		// $this->types->document = new BuddyBoss_Media_Type_Document( $this->options );
		// $this->types->generic = new BuddyBoss_Media_Type_Generic( $this->options );
	}

	/* Activate/Deactivation/Uninstall callbacks
	 * ===================================================================
	 */

	/**
	 * Fires when plugin is activated
	 *
	 * @since BuddyBoss Media (1.0.0)
	 *
	 * @uses current_user_can() Checks for user permissions
	 * @uses check_admin_referer() Verifies session
	 */
	public function activate()
	{
    if ( ! current_user_can( 'activate_plugins' ) )
    {
    	return;
    }

    $plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';

    check_admin_referer( "activate-plugin_{$plugin}" );
  }

	/**
	 * Fires when plugin is de-activated
	 *
	 * @since BuddyBoss Media (1.0.0)
	 *
	 * @uses current_user_can() Checks for user permissions
	 * @uses check_admin_referer() Verifies session
	 */
	public function deactivate()
	{
    if ( ! current_user_can( 'activate_plugins' ) )
    {
    	return;
    }

		$plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';

		check_admin_referer( "deactivate-plugin_{$plugin}" );
	}

	/**
	 * Fires when plugin is uninstalled
	 *
	 * @since BuddyBoss Media (1.0.0)
	 *
	 * @uses current_user_can() Checks for user permissions
	 * @uses check_admin_referer() Verifies session
	 */
	public function uninstall()
	{
    if ( ! current_user_can( 'activate_plugins' ) )
    {
    	return;
    }

    check_admin_referer( 'bulk-plugins' );

    // Important: Check if the file is the one
    // that was registered during the uninstall hook.
    if ( $this->file != WP_UNINSTALL_PLUGIN )
    {
    	return;
    }
	}

	/* Utility functions
	 * ===================================================================
	 */

	/**
	 * Include required array of files in the includes directory
	 *
	 * @since BuddyBoss Media (1.0.0)
	 *
	 * @uses require_once() Loads include file
	 */
	public function do_includes( $includes = array() )
	{
		foreach( (array)$includes as $include )
		{
			require_once( $this->includes_dir . '/' . $include . '.php' );
		}
	}

	/**
	 * Check if the plugin is active and enabled in the plugin's admin options.
	 *
	 * @since BuddyBoss Media (1.0.0)
	 *
	 * @uses BuddyBoss_Media_Plugin::option() Get plugin option
	 *
	 * @return boolean True when the plugin is active
	 */
	public function is_enabled()
	{
		$is_enabled = $this->option( 'enabled' );

		return (bool)$is_enabled;
	}

	/**
	 * Convenience function to access plugin options, returns false by default
	 *
	 * @since  BuddyBoss Media (1.0.0)
	 *
	 * @param  string $key Option key

	 * @uses apply_filters() Filters option values with 'buddyboss_media_option' &
	 *                       'buddyboss_media_option_{$option_name}'
	 * @uses sprintf() Sanitizes option specific filter
	 *
	 * @return mixed Option value (false if none/default)
	 *
	 */
	public function option( $key )
	{
		$key    = strtolower( $key );
		$option = isset( $this->options[$key] )
		        ? $this->options[$key]
		        : null;

		// Apply filters on options as they're called for maximum
		// flexibility. Options are are also run through a filter on
		// class instatiation/load.
		// ------------------------

		// This filter is run for every option
		$option = apply_filters( 'buddyboss_media_option', $option );

		// Option specific filter name is converted to lowercase
		$filter_name = sprintf( 'buddyboss_media_option_%s', strtolower( $key  ) );
		$option = apply_filters( $filter_name,  $option );

		return $option;
	}

	/**
	 * Check for older versions of BuddyBoss theme where the Media and Photos
	 * plugin were packaged in the theme.
	 *
	 * @since BuddyBoss Media (1.0.0)
	 *
	 * @uses apply_filters() Filters $legacy boolean with 'buddyboss_media_is_legacy'
	 *
	 * @return boolean True when a "packaged" legacy version of the Media/media
	 *                 plugin exists
	 */
	public function check_legacy()
	{
		$is_legacy = false;

		if ( is_admin() && isset( $_GET['disable_media_legacy_notice'] ) )
		{
			$this->options['notices_legacy'] = false;
			update_option( 'buddyboss_media_plugin_options', $this->options );
		}

		if ( file_exists( get_template_directory() . '/buddyboss-inc/buddyboss-wall/buddyboss-wall-loader.php' ) )
		{
			$is_legacy = true;
		}
		else if ( file_exists( get_stylesheet_directory() . '/buddyboss-inc/buddyboss-wall/buddyboss-wall-loader.php' ) )
		{
			$is_legacy = true;
		}

		$is_legacy = $this->is_legacy = apply_filters( 'buddyboss_media_is_legacy', $is_legacy );

		return (bool)$is_legacy;
	}

	/**
	 * Legacy admin notice
	 *
	 * @return [type] [description]
	 */
	public function legacy_admin_notice()
	{
    ?>
    <div class="updated">
	    <p><?php _e( 'To use the <strong>BuddyBoss Media</strong> plugin, please manually update your BuddyBoss theme to version 4.0 or above first. <a href="http://www.buddyboss.com/upgrading-to-buddyboss-4-0/">Read how &rarr;</a>', 'buddyboss-media' ); ?></p>
			<p class="submit"><a href="<?php echo add_query_arg('disable_media_legacy_notice', 'true', admin_url('options-general.php?page=buddyboss-media/includes/admin.php') ); ?>" class="button-primary"><?php _e( 'Disable Notice', 'buddyboss-media' ); ?></a></p>
    </div>
    <?php
	}

}
// End class BuddyBoss_Media_Plugin

endif;

?>
