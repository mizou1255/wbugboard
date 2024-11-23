<?php

namespace WBugBoard\Includes;

use WBugBoard\Includes\API\WBugBoard_Routes;

class WBugBoard_Settings
{
    protected $routes;

    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_submenu_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));

        $this->routes = new WBugBoard_Routes;
        $this->register_api_routes();
    }

    public function add_submenu_page()
    {
        add_submenu_page(
            'wbugboard',
            __('Settings', 'wbugboard'),
            __('Settings', 'wbugboard'),
            'manage_options',
            'wbugboard-app-settings',
            array($this, 'render_page'),
            10
        );
    }

    public function render_page()
    {
        echo '<div id="wbugboard-admin-settings"></div>';
    }

    public function enqueue_scripts($hook_suffix)
    {
        if ('wbugboard_page_wbugboard-app-settings' === $hook_suffix) {
            wp_enqueue_script('wbugboard-admin-settings', WBBD_URL . '/assets/dist/settings.min.js', array(), WBBD_VERSION, true);
        }
    }

    private function register_api_routes()
    {
        $this->routes->add_route('/settings', 'GET', $this, 'get_settings', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/priorities', 'GET', $this, 'get_priorities', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/priorities', 'POST', $this, 'add_priority', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/priorities/(?P<id>\d+)', 'PUT', $this, 'update_priority', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/priorities/(?P<id>\d+)', 'DELETE', $this, 'delete_priority', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/roles-access', 'GET', $this, 'get_roles_access', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/update-users-settings', 'POST', $this, 'update_users_settings', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/settings/general', 'POST', $this, 'save_general_settings', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/settings/email', 'POST', $this, 'save_email_settings', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/all-users', 'GET', $this, 'get_all_users', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/team-users', 'GET', $this, 'get_team_users', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/update-team-users', 'POST', $this, 'update_team_users', function () {
            return current_user_can('manage_options');
        });

        $this->routes->register_routes();
    }

    public function get_settings()
    {
        $general_settings = get_option('wbbd_general_settings', [
            'ticketLimit' => 5,
            'defaultStatus' => 'new',
            'maintenanceMode' => false,
            'attachmentSizeLimit' => 5,
            'clientChangeStatus' => false,
        ]);

        $email_settings = get_option('wbbd_email_settings', [
            'emailNotifications' => true,
            'useCustomEmail' => false,
            'notificationEmail' => '',
            'subject' => __('Ticket Notification', 'wbugboard'),
            'body' => __('Here are the details of your ticket.', 'wbugboard'),
            'adminNotifications' => true,
            'userNotifications' => true,
        ]);

        $settings = [
            'general' => $general_settings,
            'email' => $email_settings,
        ];

        return new \WP_REST_Response(['settings' => $settings], 200);
    }

    public function get_priorities()
    {
        global $wpdb;

        $priorities = $wpdb->get_results("SELECT * FROM %i", WBBD_PRIORITIES, ARRAY_A);

        if (!empty($priorities)) {
            return new \WP_REST_Response($priorities, 200);
        }

        return new \WP_REST_Response(array('message' => __('No priorities found', 'wbugboard')), 404);
    }

    public function add_priority(\WP_REST_Request $request)
    {
        if (!current_user_can('manage_options')) {
            return new \WP_REST_Response(['error' => __('You do not have permission to perform this action.', 'wbugboard')], 403);
        }

        global $wpdb;

        $name = sanitize_text_field($request->get_param('name'));

        if (empty($name)) {
            return new \WP_REST_Response(array('error' => __('Priority name is required', 'wbugboard')), 400);
        }

        $inserted = $wpdb->insert(
            WBBD_PRIORITIES,
            array('name' => $name),
            array('%s')
        );

        if ($inserted) {
            return new \WP_REST_Response(array('success' => true, 'message' => __('Priority added successfully', 'wbugboard'), 'id' => $wpdb->insert_id, 'name' => $name), 200);
        }

        return new \WP_REST_Response(array('error' => __('Error adding priority', 'wbugboard')), 500);
    }

    public function update_priority(\WP_REST_Request $request)
    {
        global $wpdb;
        $id = intval($request->get_param('id'));

        $name = sanitize_text_field($request->get_param('name'));

        if (empty($name)) {
            return new \WP_REST_Response(array('error' => __('Priority name is required', 'wbugboard')), 400);
        }

        $updated = $wpdb->update(
            WBBD_PRIORITIES,
            array('name' => $name),
            array('id' => $id),
            array('%s'),
            array('%d')
        );

        if ($updated !== false) {
            return new \WP_REST_Response(array('success' => true, 'message' => __('Priority updated successfully', 'wbugboard'), 'id' => $id, 'name' => $name), 200);
        }

        return new \WP_REST_Response(array('error' => __('Error updating priority', 'wbugboard')), 500);
    }

    public function delete_priority(\WP_REST_Request $request)
    {
        global $wpdb;
        $id = intval($request->get_param('id'));

        $deleted = $wpdb->delete(
            WBBD_PRIORITIES,
            array('id' => $id),
            array('%d')
        );

        if ($deleted) {
            return new \WP_REST_Response(array('success' => true, 'message' => __('Priority deleted successfully', 'wbugboard')), 200);
        }

        return new \WP_REST_Response(array('error' => __('Error deleting priority', 'wbugboard')), 500);
    }

    public function get_roles_access()
    {
        global $wp_roles;
        $roles = $wp_roles->roles;

        $allowed_roles = get_option('wbbd_allowed_roles', []);

        $roles_access = [];
        foreach ($roles as $role_name => $role_info) {
            $roles_access[] = [
                'name' => $role_name,
                'label' => $role_info['name'],
                'hasAccess' => in_array($role_name, $allowed_roles),
            ];
        }

        return new \WP_REST_Response(['roles' => $roles_access], 200);
    }

    public function save_general_settings(\WP_REST_Request $request)
    {
        $general_settings = $request->get_json_params();
        update_option('wbbd_general_settings', $general_settings);

        return new \WP_REST_Response(['success' => true, 'message' => __('General settings saved successfully', 'wbugboard')], 200);
    }

    public function save_email_settings(\WP_REST_Request $request)
    {
        $email_settings = $request->get_json_params();
        update_option('wbbd_email_settings', $email_settings);

        return new \WP_REST_Response(['success' => true, 'message' => __('Email settings saved successfully', 'wbugboard')], 200);
    }

    public function get_all_users($request)
    {
        $nonce = $request->get_header('X-WP-Nonce');
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_REST_Response(array('error' => __('Invalid nonce', 'wbugboard')), 403);
        }
        $users = get_users();
        $user_data = [];

        foreach ($users as $user) {
            $user_data[] = [
                'value' => $user->ID,
                'text' => $user->display_name,
            ];
        }

        return rest_ensure_response($user_data);
    }

    public function get_team_users($request)
    {
        $nonce = $request->get_header('X-WP-Nonce');
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_REST_Response(array('error' => __('Invalid nonce', 'wbugboard')), 403);
        }
        $team_users = get_option('wbbd_team_users', []);

        if (!is_array($team_users)) {
            $team_users = [];
        }

        $users = array_map(function ($user_id) {
            $user = get_userdata($user_id);
            return [
                'id' => $user->ID,
                'name' => $user->display_name,
            ];
        }, $team_users);

        return new \WP_REST_Response(['users' => $users], 200);
    }

    public function update_users_settings(\WP_REST_Request $request)
    {
        $roles = $request->get_param('roles');
        $users = $request->get_param('users');
        if (!is_array($roles)) {
            return new \WP_REST_Response(['error' => __('Invalid input', 'wbugboard')], 400);
        }

        if (!is_array($users)) {
            return new \WP_REST_Response(['error' => __('Invalid data.', 'wbugboard')], 400);
        }
        update_option('wbbd_allowed_roles', $roles);
        update_option('wbbd_team_users', $users);

        return new \WP_REST_Response(['success' => true], 200);
    }
}
