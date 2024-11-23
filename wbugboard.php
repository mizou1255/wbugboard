<?php
/**
 * Plugin Name: WBugBoard
 * Description: A professional issue tracking and support ticket management plugin for WordPress. Easily manage and prioritize customer support tickets directly from your WordPress dashboard.
 * Plugin URI: https://wpit.melioze.com
 * Version: 1.0.0
 * Author: MELIOZE.dev
 * Author URI: https://moezbettoumi.fr
 * Text Domain: wbugboard
 * Domain Path: /languages/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.2
 * Tested up to: 6.7.1
 * Requires PHP: 8.0
 * Tags:
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
final class WBugBoard
{
    /**
     * Plugin version
     *
     * @var string
     */
    public $version = '1.0.0';

    /**
     * Minimum required PHP version
     *
     * @var string
     */
    private $min_php = '8.0';

    private $container = [];

    /**
     * Singleton instance
     *
     * @var WBugBoard
     */
    private static $instance;

    /**
     * Initialize the plugin
     *
     * @return WBugBoard
     */
    public static function init()
    {
        if (!isset(self::$instance) && !(self::$instance instanceof WBugBoard)) {
            self::$instance = new WBugBoard();
            self::$instance->setup();
        }

        return self::$instance;
    }

    /**
     * Setup the plugin
     *
     * @return void
     */
    private function setup()
    {
        register_activation_hook(__FILE__, [$this, 'auto_deactivate']);

        if (!$this->is_supported_php()) {
            return;
        }

        $this->define_constants();
        $this->includes();
        $this->instantiate();
        $this->init_actions();
    }

    /**
     * Define plugin constants
     *
     * @return void
     */
    private function define_constants()
    {
        global $wpdb;
        define('MYIT_VERSION', $this->version);
        define('MYIT_PREFIX', $wpdb->prefix);
        define('MYIT_PATH', dirname(__FILE__));
        define('MYIT_INCLUDES', MYIT_PATH . '/includes');
        define('MYIT_URL', plugins_url('', __FILE__));

        define('MYIT_TICKETS', MYIT_PREFIX . 'wpit_tickets');
        define('MYIT_PRIORITIES', MYIT_PREFIX . 'wpit_priorities');
        define('MYIT_COMMENTS', MYIT_PREFIX . 'wpit_ticket_comments');
        define('MYIT_USERS', MYIT_PREFIX . 'users');

    }

    /**
     * Include required files
     *
     * @return void
     */
    private function includes()
    {
        require_once MYIT_INCLUDES . '/API/Routes.php';
        require_once MYIT_INCLUDES . '/class-wbugboard-migrations.php';
        require_once MYIT_INCLUDES . '/class-wbugboard-app.php';
        require_once MYIT_INCLUDES . '/class-wbugboard-front.php';
        require_once MYIT_INCLUDES . '/class-wbugboard-settings.php';
    }

    /**
     * Instantiate necessary classes or services
     *
     * @return void
     */
    private function instantiate()
    {
        $this->container['routes'] = new WBugBoard\Includes\API\WBugBoard_Routes();
        $this->container['migration'] = new WBugBoard\Includes\WBugBoard_Migrations();
        $this->container['app'] = new WBugBoard\Includes\WBugBoard_App();
        $this->container['front'] = new WBugBoard\Includes\WBugBoard_Front();
        $this->container['settings'] = new WBugBoard\Includes\WBugBoard_Settings();
    }

    /**
     * Initialize actions and hooks
     *
     * @return void
     */
    private function init_actions()
    {
        add_action('init', [$this, 'localization_setup']);
        register_activation_hook(__FILE__, [$this->container['migration'], 'plugin_activation']);
        register_deactivation_hook(__FILE__, [$this->container['migration'], 'plugin_desactivation']);
    }

    /**
     * Setup plugin localization
     *
     * @return void
     */
    public function localization_setup()
    {
        load_plugin_textdomain('wbugboard', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    /**
     * Check if the PHP version is supported
     *
     * @return bool
     */
    public function is_supported_php()
    {
        return version_compare(PHP_VERSION, $this->min_php, '>=');
    }

    /**
     * Deactivate the plugin if the PHP version is not supported
     *
     * @return void
     */
    public function auto_deactivate()
    {
        if (!$this->is_supported_php()) {
            deactivate_plugins(plugin_basename(__FILE__));

            // translators: %1$s is the plugin name, %2$s is the required PHP version.
            wp_die(
                sprintf(
                    esc_html__(
                        'The %1$s plugin requires PHP version %2$s or higher.',
                        'wbugboard'
                    ),
                    esc_html__('WBugBoard', 'wbugboard'),
                    esc_html($this->min_php)
                ),
                esc_html__('Plugin Activation Error', 'wbugboard'),
                ['response' => 200, 'back_link' => true]
            );
        }
    }
}

/**
 * Initialize the WBugBoard plugin
 *
 * @return WBugBoard
 */
WBugBoard::init();
