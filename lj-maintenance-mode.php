<?php
/**
 * Plugin Name: Maintenance Mode
 * Plugin URI: https://plugins.itsluk.as/maintenance-mode/
 * Description: Very simple Maintenance Mode & Coming soon page. Using default Wordpress markup, No ads, no paid upgrades.
 * Version: 2.2.5
 * Author: Lukas Juhas
 * Author URI: https://plugins.itsluk.as/
 * Text Domain: lj-maintenance-mode
 * License: GPL2
 * Domain Path: /languages/
 *
 * Copyright 2014-2017  Lukas Juhas
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @package lj-maintenance-mode
 * @author Lukas Juhas
 * @version 2.2.5
 *
 */

// define stuff
define('LJMM_VERSION', '2.2.5');
define('LJMM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('LJMM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LJMM_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('LJMM_PLUGIN_DOMAIN', 'lj-maintenance-mode');

// activation hook
add_action('activate_' . LJMM_PLUGIN_BASENAME, 'ljmm_install');

/**
 * Installation
 *
 * @since 1.0
*/
function ljmm_install()
{
    // remove old settings. This has been deprecated in 1.2
    delete_option('ljmm-content-default');

    // set default content
    ljmm_set_content();
}

/**
 * Default hardcoded settings
 *
 * @since 1.4
 */
function ljmm_get_defaults($type)
{
    switch ($type) {
        case 'maintenance_message':
            $default = __("<h1>Website Under Maintenance</h1><p>Our Website is currently undergoing scheduled maintenance. Please check back soon.</p>", LJMM_PLUGIN_DOMAIN);
            break;
        case 'warning_wp_super_cache':
            $default = __("Important: Don't forget to flush your cache using WP Super Cache when enabling or disabling Maintenance Mode.", LJMM_PLUGIN_DOMAIN);
            break;
        case 'warning_w3_total_cache':
            $default = __("Important: Don't forget to flush your cache using W3 Total Cache when enabling or disabling Maintenance Mode.", LJMM_PLUGIN_DOMAIN);
            break;
        case 'warning_comet_cache':
            $default = __("Important: Don't forget to flush your cache using Comet Cache when enabling or disabling Maintenance Mode.", LJMM_PLUGIN_DOMAIN);
            break;
        case 'ljmm_enabled':
            $default = __("Maintenance Mode is currently active. To make sure that it works, open your web page in either private / incognito mode, different browser or simply log out. Logged in users are not affected by the Maintenance Mode.", LJMM_PLUGIN_DOMAIN);
            break;
        default:
            $default = false;
            break;
    }

    return $default;
}

/**
 * Set the default content
 * Avoid duplicate function.
 * @since 1.0
 */
function ljmm_set_content()
{
    // If content is not set, set the default content.
    $content = get_option('ljmm-content');
    if (empty($content)) :
        $content = ljmm_get_defaults('maintenance_message');
        /**
        * f you are trying to ensure that a given option is created,
        * use update_option() instead, which bypasses the option name check
        * and updates the option with the desired value whether or not it exists.
        */
        update_option('ljmm-content', stripslashes($content));
    endif;

    // If content is not set, set the default content.
    $mode = get_option('ljmm-mode');
    if (empty($mode)) :
        update_option('ljmm-mode', 'default');
    endif;
}

/**
 * Load plugin textdomain.
 *
 * @since 1.3.1
*/
function ljmm_load_textdomain()
{
    load_plugin_textdomain(LJMM_PLUGIN_DOMAIN, false, dirname(plugin_basename(__FILE__)) . '/languages/');
}
add_action('plugins_loaded', 'ljmm_load_textdomain');

/**
 * main class
 *
 * @since 1.0
*/
class ljMaintenanceMode
{
    /**
     * constructor
     *
     * @since 1.0
     */
    public function __construct()
    {
        add_action('admin_menu', array( $this, 'ui' ));
        add_action('admin_head', array( $this, 'style' ));
        add_action('admin_init', array( $this, 'settings' ));
        add_action('admin_init', array( $this, 'manage_capabilities'));

        // remove old settings. This has been deprecated in 1.2
        delete_option('ljmm-content-default');

        $is_enabled = get_option('ljmm-enabled');

        if ($is_enabled || isset($_GET['ljmm']) && $_GET['ljmm'] == 'preview') :
            add_action('get_header', array( $this, 'maintenance' ));
        endif;

        add_action('admin_bar_menu', array( $this, 'indicator' ), 100);
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'action_links'));

        add_action('ljmm_before_mm', array( $this, 'before_maintenance_mode' ));
    }

    /**
     * Settings page
     *
     * @since 1.0
    */
    public function ui()
    {
        add_submenu_page('options-general.php', __('Maintenance Mode', LJMM_PLUGIN_DOMAIN), __('Maintenance Mode', LJMM_PLUGIN_DOMAIN), 'delete_plugins', 'lj-maintenance-mode', array($this, 'settingsPage'));
    }

    /**
     * Inject styling
     *
     * @since 1.1
    */
    public function style()
    {
        echo '<style type="text/css">#wp-admin-bar-ljmm-indicator.ljmm-indicator--enabled { background: rgba(159, 0, 0, 1) }</style>';
    }

    /**
     * Settings
     *
     * @since 1.0
    */
    public function settings()
    {
        register_setting('ljmm', 'ljmm-enabled');
        register_setting('ljmm', 'ljmm-content');
        register_setting('ljmm', 'ljmm-site-title');
        register_setting('ljmm', 'ljmm-roles');
        register_setting('ljmm', 'ljmm-mode');

        //set the content
        ljmm_set_content();
    }

    /**
     * Settings page
     *
     * @since 1.0
    */
    public function settingsPage()
    {
        ?>
        <div class="wrap">
            <h2><?php _e('Maintenance Mode', LJMM_PLUGIN_DOMAIN); ?></h2>
            <form method="post" action="options.php">
                <?php settings_fields('ljmm'); ?>
                <?php do_settings_sections('ljmm'); ?>

                <?php $this->notify(); ?>

                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">
                            <label for="ljmm_enabled"><?php _e('Enabled', LJMM_PLUGIN_DOMAIN); ?></label>
                        </th>
                        <td>
                            <?php $ljmm_enabled = esc_attr(get_option('ljmm-enabled')); ?>
                            <input type="checkbox" id="ljmm_enabled" name="ljmm-enabled" value="1" <?php checked($ljmm_enabled, 1); ?>>
                            <?php if ($ljmm_enabled) : ?>
                                <p class="description"><?php echo ljmm_get_defaults('ljmm_enabled'); ?></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <a href="<?php echo esc_url(add_query_arg('ljmm', 'preview', bloginfo('url'))); ?>" target="_blank" class="button button-secondary"><?php _e('Preview', LJMM_PLUGIN_DOMAIN); ?></a>
                            <a class="button button-secondary support" href="https://plugins.itsluk.as/maintenance-mode/support/" target="_blank"><?php _e('Support', LJMM_PLUGIN_DOMAIN); ?></a>
                        </th>
                    </tr>
                    <tr>
                        <th colspan="2">
                            <?php $content = get_option('ljmm-content');
                            $editor_id = 'ljmm-content';
                            wp_editor($content, $editor_id); ?>
                        </th>
                    </tr>
                </table>

                <a href="#" class="ljmm-advanced-settings">
                    <span class="ljmm-advanced-settings__label-advanced">
                        <?php _e('Advanced Settings', LJMM_PLUGIN_DOMAIN); ?>
                    </span>
                    <span class="ljmm-advanced-settings__label-hide-advanced" style="display: none;">
                        <?php _e('Hide Advanced Settings', LJMM_PLUGIN_DOMAIN); ?>
                    </span>
                </a>
                <table class="form-table form--ljmm-advanced-settings" style="display: none">
                    <tr valign="middle">
                        <th scope="row"><?php _e('Site Title', LJMM_PLUGIN_DOMAIN); ?></th>
                        <td>
                            <?php $ljmm_site_title = esc_attr(get_option('ljmm-site-title')); ?>
                            <input name="ljmm-site-title" type="text" id="ljmm-site-title" placeholder="<?php echo $this->site_title(); ?>" value="<?php echo $ljmm_site_title; ?>" class="regular-text">
                            <p class="description"><?php _e('Overrides default site meta title.', LJMM_PLUGIN_DOMAIN); ?></p>
                        </td>
                    </tr>
                    <tr valign="middle">
                        <th scope="row"><?php _e('Mode', LJMM_PLUGIN_DOMAIN); ?></th>
                        <td>
                            <?php $ljmm_mode = esc_attr(get_option('ljmm-mode')); ?>
                            <?php $mode_default = $ljmm_mode == 'default' ? true: false; ?>
                            <?php $mode_cs = $ljmm_mode == 'cs' ? true: false; ?>
                            <label>
                                <input name="ljmm-mode" type="radio" value="default" <?php checked($mode_default, 1); ?>>
                                <?php _e('Maintenance Mode', LJMM_PLUGIN_DOMAIN); ?> (<?php _e('Default', LJMM_PLUGIN_DOMAIN); ?>)
                            </label>
                            <label>
                                <input name="ljmm-mode" type="radio" value="cs" <?php checked($mode_cs, 1); ?>>
                                <?php _e('Coming Soon Page', LJMM_PLUGIN_DOMAIN); ?>
                            </label>
                            <p class="description"><?php _e('Default sets HTTP to 503, coming soon will set HTTP to 200.', LJMM_PLUGIN_DOMAIN); ?> <a href="https://en.wikipedia.org/wiki/List_of_HTTP_status_codes" target="blank"><?php _e('Learn more.', LJMM_PLUGIN_DOMAIN); ?></a></p>
                        </td>
                    </tr>
                    <?php global $wpdb; ?>
                    <?php $options = get_option('ljmm-roles'); ?>
                    <?php $wp_roles = get_option($wpdb->prefix . 'user_roles'); ?>
                    <?php if ($wp_roles && is_array($wp_roles)) : ?>
                        <tr valign="top">
                            <th scope="row"><?php _e('User Roles', LJMM_PLUGIN_DOMAIN); ?>
                                <p class="description"><?php _e('Tick the ones that can access front-end of your website if maintenance mode is enabled', LJMM_PLUGIN_DOMAIN); ?>.</p>
                                <p class="description"><?php _e('Please note that this does NOT apply to admin area', LJMM_PLUGIN_DOMAIN); ?>.</p>
                                <p><a href="#" class="ljmm-toggle-all"><?php _e('Toggle all', LJMM_PLUGIN_DOMAIN); ?></a></p>
                            </th>
                            <td>
                                <?php foreach ($wp_roles as $role => $role_details) :  ?>
                                    <?php if ($role !== 'administrator') : ?>
                                        <fieldset>
                                            <legend class="screen-reader-text"><span><?php if (isset($options[$role])) { echo $options[$role]; } ?></span>
                                            </legend>
                                            <label>
                                                <input type="checkbox" class="ljmm-roles" name="ljmm-roles[<?php echo $role; ?>]" value="1" <?php checked(isset($options[$role]), 1); ?> /> <?php echo $role_details['name']; ?>
                                            </label>
                                        </fieldset>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <tr valign="top">
                            <th scope="row" colspan="2">
                                <p class="description"><?php _e('User Role control is currently not avialable on your website. Sorry!', LJMM_PLUGIN_DOMAIN); ?></p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <script>
          jQuery(document).ready(function() {
            jQuery('.ljmm-advanced-settings').on('click', function(event) {
              event.preventDefault();
              jQuery('.form--ljmm-advanced-settings').toggle();
              if (jQuery('.form--ljmm-advanced-settings').is(':visible')) {
                jQuery(this).find('.ljmm-advanced-settings__label-advanced').hide();
                jQuery(this).find('.ljmm-advanced-settings__label-hide-advanced').show();
              } else {
                jQuery(this).find('.ljmm-advanced-settings__label-advanced').show();
                jQuery(this).find('.ljmm-advanced-settings__label-hide-advanced').hide();
              }
            });
            jQuery('.ljmm-toggle-all').on('click', function(event) {
              event.preventDefault();
              var checkBoxes = jQuery("input.ljmm-roles");
              checkBoxes.prop("checked", !checkBoxes.prop("checked"));
            });
          });
        </script>
    <?php

    }

    /**
     * admin bar indicator
     *
     * @since 1.1
    */
    public function indicator($wp_admin_bar)
    {
        $enabled = apply_filters('ljmm_admin_bar_indicator_enabled', $enabled = true);

        if (!current_user_can('delete_plugins')) {
            return false;
        }

        if (!$enabled) {
            return false;
        }

        $is_enabled = get_option('ljmm-enabled');
        $status = _x('Disabled', 'Admin bar indicator', LJMM_PLUGIN_DOMAIN);

        if ($is_enabled) {
            $status = _x('Enabled', 'Admin bar indicator', LJMM_PLUGIN_DOMAIN);
        }

        $indicatorClasses = $is_enabled ? 'ljmm-indicator ljmm-indicator--enabled' : 'ljmm-indicator';

        $indicator = array(
            'id' => 'ljmm-indicator',
            'title' => '<span class="ab-icon dashicon-before dashicons-hammer"></span> ' . $status,
            'parent' => false,
            'href' => get_admin_url(null, 'options-general.php?page=lj-maintenance-mode'),
            'meta' => array(
                'title' => _x('Maintenance Mode', 'Admin bar indicator', LJMM_PLUGIN_DOMAIN),
                'class' => $indicatorClasses,
            )
        );

        $wp_admin_bar->add_node($indicator);
    }

    /**
     * plugin action links
     *
     * @since 1.1
     * @return mixed
    */
    public function action_links($links)
    {
        $links[] = '<a href="'. get_admin_url(null, 'options-general.php?page=lj-maintenance-mode') .'">'._x('Settings', 'Plugin Settings link', LJMM_PLUGIN_DOMAIN).'</a>';
        $links[] = '<a target="_blank" href="https://plugins.itsluk.as/maintenance-mode/support/">'._x('Support', 'Plugin Support link', LJMM_PLUGIN_DOMAIN).'</a>';

        return $links;
    }

    /**
     * default site title for maintenance mode
     *
     * @since 2.0
     * @return string
     */
    public function site_title()
    {
        return apply_filters('ljmm_site_title', get_bloginfo('name') . ' - ' . __('Website Under Maintenance', LJMM_PLUGIN_DOMAIN));
    }

    /**
     * manage capabilities
     *
     * @since 2.0
     */
    public function manage_capabilities()
    {
        global $wpdb;

        $wp_roles = get_option($wpdb->prefix . 'user_roles');
        $all_roles = get_option('ljmm-roles');

        // extra checks
        if ($wp_roles && is_array($wp_roles)) {
            foreach ($wp_roles as $role => $role_details) {
                $get_role = get_role($role);

                if (is_array($all_roles) && array_key_exists($role, $all_roles)) {
                    $get_role->add_cap('ljmm_view_site');
                } else {
                    $get_role->remove_cap('ljmm_view_site');
                }
            }
        }

        // administrator by default
        $admin_role = get_role('administrator');
        $admin_role->add_cap('ljmm_view_site');
    }

    /**
     * get mode
     *
     * @since 2.2
     * @return int
     */
    public function get_mode()
    {
        $mode = get_option('ljmm-mode');
        if ($mode == 'cs') {
            // coming soon page
            return 200;
        }

        // maintenance mode
        return 503;
    }

    /**
     * get content
     *
     * @since 2.3
     * @return mixed
     */
    public function get_content()
    {
        $get_content = get_option('ljmm-content');
        $content = (!empty($get_content)) ? $get_content : ljmm_get_defaults('maintenance_message');
        $content = apply_filters('the_content', $content);
        $content = apply_filters('ljmm_content', $content);

        return $content;
    }

    /**
     * get title
     *
     * @since 2.3
     * @return string
     */
    public function get_title()
    {
        $site_title = get_option('ljmm-site-title');
        return $site_title ? $site_title : $this->site_title();
    }

    /**
     * before maintenance mode
     */
    public function before_maintenance_mode()
    {
        // remove jetpack sharing
        remove_filter('the_content', 'sharing_display', 19);
    }

    /**
     * Maintenance Mode
     *
     * @since 1.0
    */
    public function maintenance()
    {
        do_action('ljmm_before_mm');

        // TML Compatibility
        if (class_exists('Theme_My_Login')) {
            if (Theme_My_Login::is_tml_page()) {
                return;
            }
        }

        if (!(current_user_can('ljmm_view_site') || current_user_can('super admin')) || (isset($_GET['ljmm']) && $_GET['ljmm'] == 'preview')) {
            wp_die($this->get_content(), $this->get_title(), array('response' => $this->get_mode()));
        }
    }

   /**
    * notify if cache plugin detected
    *
    * @since 1.2
   */
    public function notify()
    {
        $cache_plugin_enabled = $this->cache_plugin();
        if (!empty($cache_plugin_enabled)) {
            $class = "error";
            $message = $this->cache_plugin();
            if (isset($_GET['settings-updated'])) {
                echo '<div class="'.$class.'"><p>'.$message.'</p></div>';
            }
        }
    }

    /**
     * detect cache plugins
     *
     * @since 1.2
     * @return string
    */
    public function cache_plugin()
    {
        $message = '';
        // add wp super cache support
        if (in_array('wp-super-cache/wp-cache.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            $message = ljmm_get_defaults('warning_wp_super_cache');
        }

        // add w3 total cache support
        if (in_array('w3-total-cache/w3-total-cache.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            $message = ljmm_get_defaults('warning_w3_total_cache');
        }

        // add comet cache support
        if (in_array('comet-cache/comet-cache.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            $message = ljmm_get_defaults('warning_comet_cache');
        }

        return $message;
    }
}

// initialise plugin.
$ljMaintenanceMode = new ljMaintenanceMode();
