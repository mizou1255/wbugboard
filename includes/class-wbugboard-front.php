<?php

namespace WBugBoard\Includes;

use WBugBoard\Includes\API\WBugBoard_Routes;

class WBugBoard_Front
{
    protected $routes;

    public function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_scripts']);
        add_action('wp_footer', [$this, 'add_vue_app_container']);
        add_action('template_redirect', [$this, 'check_maintenance_mode']);

        $this->routes = new WBugBoard_Routes;
        $this->register_api_routes();
    }

    private function register_api_routes()
    {
        $this->routes->add_route('/front-create-ticket', 'POST', $this, 'wp_wpit_create_ticket', function () {
            return $this->user_has_allowed_role();
        });
        $this->routes->add_route('/get-user-tickets', 'GET', $this, 'get_user_tickets', function () {
            return $this->user_has_allowed_role();
        });
        $this->routes->add_route('/user-settings', 'GET', $this, 'get_user_settings', function () {
            return $this->user_has_allowed_role();
        });
        $this->routes->add_route('/team', 'GET', $this, 'get_team_users', function () {
            return $this->user_has_allowed_role();
        });

        $this->routes->add_route('/update-ticket-status', 'POST', $this, 'wp_wpit_update_ticket_status', function () {
            return $this->user_has_allowed_role();
        });

        $this->routes->add_route('/delete-ticket/(?P<ticket_id>\d+)', 'DELETE', $this, 'delete_ticket', function () {
            return $this->user_has_allowed_role();
        });

        $this->routes->register_routes();
    }

    public function enqueue_frontend_scripts()
    {
        wp_enqueue_script('wbugboard-front-app', MYIT_URL . '/assets/dist/front.min.js', array(), MYIT_VERSION, true);
        wp_enqueue_style('fontawesome', MYIT_URL . '/assets/css/all.min.css', array(), MYIT_VERSION);
        wp_enqueue_style('wbugboard-app-style', MYIT_URL . '/assets/dist/style.min.css', array(), MYIT_VERSION);
        wp_enqueue_style('wbugboard-notyf', MYIT_URL . '/assets/css/notyf.min.css', array(), MYIT_VERSION);

        add_filter('script_loader_tag', array($this, 'add_type_attribute'), 10, 2);

        require_once MYIT_PATH . '/languages/translations.php';

        wp_localize_script('wbugboard-front-app', 'WPIT_Front', array(
            'nonce' => wp_create_nonce('wp_rest'),
            'WPIT_trans' => $translations,
        ));

        wp_enqueue_script('vue-js', MYIT_URL . '/assets/js/vue.global.min.js', array(), null, true);
        wp_enqueue_script('konva-js', MYIT_URL . '/assets/js/konva.min.js', array(), null, true);
    }

    public function add_type_attribute($tag, $handle)
    {
        $scripts = array(
            'wbugboard-front-app',
        );

        if (in_array($handle, $scripts)) {
            return str_replace(' src', ' type="module" src', $tag);
        }

        return $tag;
    }

    public function add_vue_app_container()
    {
        echo '<div id="wbugboard-front-app"></div>';
    }

    public function check_maintenance_mode()
    {
        $general_settings = get_option('wpit_general_settings', []);
        $is_maintenance_mode = isset($general_settings['maintenanceMode']) ? $general_settings['maintenanceMode'] : false;
        $allowed_roles = get_option('wpit_allowed_roles', []);

        if ($is_maintenance_mode && !current_user_can('manage_options')) {
            $user = wp_get_current_user();
            if (!array_intersect($allowed_roles, $user->roles)) {
                wp_die(
                    esc_html__('The site is currently under maintenance. Please come back later.', 'wbugboard'),
                    esc_html__('Maintenance', 'wbugboard'),
                    array('response' => 503)
                );
            }
        }
    }

    public function wp_wpit_create_ticket(\WP_REST_Request $request)
    {
        global $wpdb;
        $nonce = $request->get_header('X-WP-Nonce');
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_REST_Response(array('error' => __('Invalid nonce.', 'wbugboard')), 403);
        }

        if (!function_exists('wp_handle_upload')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        $title = sanitize_text_field($request->get_param('title'));
        $description = wp_kses_post($request->get_param('description'));
        $priority = sanitize_text_field($request->get_param('priority'));
        $priority = sanitize_text_field($request->get_param('priority'));
        $asigned = $request->get_param('asigned');
        $user_id = get_current_user_id();

        if (empty($title) || empty($description)) {
            return new \WP_REST_Response(array('error' => __('Title and description are required.', 'wbugboard')), 400);
        }

        $ticket_info = sanitize_text_field($request->get_param('ticket_info'));

        $general_settings = get_option('wpit_general_settings', []);
        $default_status = isset($general_settings['defaultStatus']) ? $general_settings['defaultStatus'] : 'new';

        $asigned = (!empty($asigned) && is_numeric($asigned)) ? absint($asigned) : null;

        $inserted = $wpdb->insert(
            MYIT_TICKETS,
            array(
                'title' => $title,
                'description' => $description,
                'priority' => $priority,
                'status' => $default_status,
                'user_id' => $user_id,
                'assigned_user_id' => $asigned,
                'ticket_info' => $ticket_info,
            ),
            array('%s', '%s', '%s', '%s', '%d', '%d', '%s')
        );

        if ($inserted === false) {
            return new \WP_REST_Response(array('error' => __('Error inserting ticket into the database.', 'wbugboard'), 'sql_error' => $wpdb->last_error), 500);
        }

        $ticket_id = $wpdb->insert_id;

        if (!empty($_FILES['file'])) {
            if (!function_exists('wp_handle_upload')) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
            }

            add_filter('upload_dir', array($this, 'custom_upload_dir'));
            add_filter('wp_handle_upload_prefilter', function ($file) use ($ticket_id) {
                $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $file['name'] = 'ticket_' . $ticket_id . '.' . $file_extension;
                return $file;
            });

            $upload = wp_handle_upload($_FILES['file'], array('test_form' => false));

            remove_filter('upload_dir', array($this, 'custom_upload_dir'));
            remove_filter('wp_handle_upload_prefilter', '__return_false');

            if ($upload && !isset($upload['error'])) {
                $attachment_url = esc_url_raw($upload['url']);
                $wpdb->update(
                    MYIT_TICKETS,
                    array('attachment_url' => $attachment_url),
                    array('id' => $ticket_id),
                    array('%s'),
                    array('%d')
                );

                if ($wpdb->last_error) {
                    return new \WP_Error(
                        'db_error',
                        __('Database error when inserting attachment:', 'wbugboard') . $wpdb->last_error,
                        array('status' => 500)
                    );
                }
            } else {
                return new \WP_REST_Response(array(
                    'error' => __('File upload failed:', 'wbugboard') . $upload['error'],
                ), 500);
            }
        }

        $email_settings = get_option('wpit_email_settings', []);
        $adminNotifications = isset($email_settings['adminNotifications']) ? (bool) $email_settings['adminNotifications'] : false;
        $userNotifications = isset($email_settings['userNotifications']) ? (bool) $email_settings['userNotifications'] : false;
        $useCustomEmail = isset($email_settings['useCustomEmail']) ? (bool) $email_settings['useCustomEmail'] : false;
        $notificationEmail = isset($email_settings['notificationEmail']) ? $email_settings['notificationEmail'] : '';

        $headers = array('Content-Type: text/html; charset=UTF-8');

        if ($adminNotifications) {
            $admin_email = ($useCustomEmail && !empty($notificationEmail)) ? $notificationEmail : get_option('admin_email');
            $admin_subject = isset($email_settings['adminSubject']) ? $email_settings['adminSubject'] : __('New Ticket', 'wbugboard');
            $admin_body = isset($email_settings['adminBody']) ? $email_settings['adminBody'] : __('A new ticket has been submitted by a user.', 'wbugboard');

            wp_mail($admin_email, $admin_subject, $admin_body, $headers);
        }

        if ($userNotifications && $user_id) {
            $user_data = get_userdata($user_id);
            $user_email = $user_data->user_email;
            $user_subject = isset($email_settings['userSubject']) ? $email_settings['userSubject'] : __('Ticket Successfully Created', 'wbugboard');
            $user_body = isset($email_settings['userBody']) ? $email_settings['userBody'] : __('Your ticket has been successfully created.', 'wbugboard');

            wp_mail($user_email, $user_subject, $user_body, $headers);
        }

        return new \WP_REST_Response(array('success' => true, 'ticket_id' => $ticket_id), 200);
    }

    public function custom_upload_dir($upload)
    {
        $upload_dir = WP_CONTENT_DIR . '/uploads/wpit-uploads';

        if (!file_exists($upload_dir)) {
            wp_mkdir_p($upload_dir);
        }

        $upload['path'] = $upload_dir;
        $upload['url'] = WP_CONTENT_URL . '/uploads/wpit-uploads';
        $upload['subdir'] = '';

        return $upload;
    }

    public function get_user_tickets(\WP_REST_Request $request)
    {
        global $wpdb;
        $user_id = get_current_user_id();

        if (!$user_id) {
            return new \WP_REST_Response(array('error' => __('User not logged in.', 'wbugboard')), 403);
        }

        $tickets = $wpdb->get_results($wpdb->prepare("
        SELECT
            t.id,
            t.title,
            t.description,
            t.status,
            t.priority,
            t.attachment_url,
            t.assigned_user_id,
            p.name AS priority_name,
            u.display_name AS assigned_user_name
        FROM %i t
        LEFT JOIN %i p ON t.priority = p.id
        LEFT JOIN {$wpdb->users} u ON t.assigned_user_id = u.ID
        WHERE t.user_id = %d
        ORDER BY t.id DESC",
            MYIT_TICKETS,
            MYIT_PRIORITIES,
            $user_id
        ));

        if (empty($tickets)) {
            return new \WP_REST_Response(array('success' => true, 'tickets' => []), 200);
        }

        $tickets_data = array_map(function ($ticket) {
            return array(
                'id' => $ticket->id,
                'title' => $ticket->title,
                'description' => $ticket->description,
                'status' => $ticket->status,
                'priority' => $ticket->priority_name ? $ticket->priority_name : null,
                'attachment_url' => $ticket->attachment_url ? $ticket->attachment_url : null,
                'assigned_user_name' => $ticket->assigned_user_name ? $ticket->assigned_user_name : null,
            );
        }, $tickets);

        return new \WP_REST_Response(array('success' => true, 'tickets' => $tickets_data), 200);
    }

    public function get_user_settings(\WP_REST_Request $request)
    {
        $user_id = get_current_user_id();

        if (!$user_id) {
            return new \WP_REST_Response(array('error' => __('User not logged in.', 'wbugboard')), 403);
        }

        $allowed_roles = get_option('wpit_allowed_roles', []);

        $current_user = wp_get_current_user();
        $user_roles = $current_user->roles;
        $userHasAccess = !empty(array_intersect($user_roles, $allowed_roles));

        $general_settings = get_option('wpit_general_settings', []);

        $activatePlugin = isset($general_settings['activatePlugin']) ? (bool) $general_settings['activatePlugin'] : false;
        $clientChangeStatus = isset($general_settings['clientChangeStatus']) ? (bool) $general_settings['clientChangeStatus'] : false;
        $clientDeleteTicket = isset($general_settings['clientDeleteTicket']) ? (bool) $general_settings['clientDeleteTicket'] : false;

        $settings = [
            'activatePlugin' => $activatePlugin,
            'userHasAccess' => $userHasAccess,
            'roles' => $user_roles,
            'allowed_roles' => $allowed_roles,
            'clientChangeStatus' => $clientChangeStatus,
            'clientDeleteTicket' => $clientDeleteTicket,
        ];

        return new \WP_REST_Response($settings, 200);
    }

    public function wp_wpit_update_ticket_status(\WP_REST_Request $request)
    {
        global $wpdb;
        $nonce = $request->get_header('X-WP-Nonce');
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_REST_Response(array('error' => __('Invalid nonce.', 'wbugboard')), 403);
        }

        $ticket_id = intval($request->get_param('ticket_id'));
        $new_status = sanitize_text_field($request->get_param('status'));
        $user_id = get_current_user_id();

        $ticket = $wpdb->get_row($wpdb->prepare("SELECT * FROM %i WHERE id = %d AND user_id = %d", MYIT_TICKETS, $ticket_id, $user_id));
        if (!$ticket) {
            return new \WP_REST_Response(array('error' => __('Ticket not found or access denied.', 'wbugboard')), 403);
        }

        $updated = $wpdb->update(
            MYIT_TICKETS,
            array('status' => $new_status),
            array('id' => $ticket_id),
            array('%s'),
            array('%d')
        );

        if ($updated === false) {
            return new \WP_REST_Response(array('error' => __('Error updating ticket status.', 'wbugboard')), 500);
        }

        return new \WP_REST_Response(array('success' => true, 'status' => $new_status), 200);
    }

    private function user_has_allowed_role()
    {
        $allowed_roles = get_option('wpit_allowed_roles', []);

        $user = wp_get_current_user();
        $user_roles = $user->roles;

        return !empty(array_intersect($allowed_roles, $user_roles));
    }

    public function get_team_users(\WP_REST_Request $request)
    {
        $nonce = $request->get_header('X-WP-Nonce');
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_REST_Response(array('error' => __('Invalid nonce.', 'wbugboard')), 403);
        }

        $user_ids = get_option('wpit_team_users', []);

        if (empty($user_ids)) {
            return rest_ensure_response([]);
        }

        $users = get_users(array(
            'include' => $user_ids,
        ));

        $result = array_map(function ($user) {
            return array(
                'id' => $user->ID,
                'name' => $user->display_name,
            );
        }, $users);

        return rest_ensure_response($result);
    }

    public function delete_ticket(\WP_REST_Request $request)
    {

        $nonce = $request->get_header('X-WP-Nonce');
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_REST_Response(array('error' => __('Invalid nonce.', 'wbugboard')), 403);
        }

        global $wpdb;
        $ticket_id = absint($request->get_param('ticket_id'));
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new \WP_REST_Response(array('success' => false, 'message' => __('You are not logged in.', 'wbugboard')), 403);
        }

        $ticket = $wpdb->get_row($wpdb->prepare("SELECT * FROM %i WHERE id = %d", MYIT_TICKETS, $ticket_id));
        if (!$ticket) {
            return new \WP_REST_Response(array('success' => false, 'message' => __('Ticket not found.', 'wbugboard')), 404);
        }

        if ((int) $ticket->user_id !== $user_id && !current_user_can('manage_options')) {
            return new \WP_REST_Response(array('success' => false, 'message' => __('You are not authorized to delete this ticket.', 'wbugboard')), 403);
        }

        $deleted_comments = $wpdb->delete(
            MYIT_COMMENTS,
            array('ticket_id' => $ticket_id),
            array('%d')
        );

        $deleted_ticket = $wpdb->delete(
            MYIT_TICKETS,
            array('id' => $ticket_id),
            array('%d')
        );

        if ($deleted_ticket === false) {
            return new \WP_REST_Response(array('success' => false, 'message' => __('Failed to delete the ticket.', 'wbugboard')), 500);
        }

        return new \WP_REST_Response(array('success' => true, 'message' => __('Ticket deleted successfully.', 'wbugboard')), 200);
    }
}
