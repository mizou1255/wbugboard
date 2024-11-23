<?php

namespace WBugBoard\Includes;

class WBugBoard_Migrations
{

    public function plugin_activation()
    {
        global $wpdb;
        $table_name = MYIT_TICKETS;
        $table_name_comment = MYIT_COMMENTS;
        $table_name_priority = MYIT_PRIORITIES;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            description LONGTEXT NOT NULL,
            priority BIGINT(20) UNSIGNED NOT NULL,
            status VARCHAR(20) DEFAULT 'new',
            attachment_url VARCHAR(500) DEFAULT NULL,
            ticket_info LONGTEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            user_id BIGINT(20) UNSIGNED DEFAULT NULL,
            assigned_user_id BIGINT(20) UNSIGNED DEFAULT NULL,
            PRIMARY KEY (id),
            FOREIGN KEY (user_id) REFERENCES {$wpdb->prefix}users(ID) ON DELETE SET NULL,
            FOREIGN KEY (assigned_user_id) REFERENCES {$wpdb->prefix}users(ID) ON DELETE SET NULL
        ) $charset_collate;";

        $sql2 = "CREATE TABLE IF NOT EXISTS $table_name_comment (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                ticket_id BIGINT(20) UNSIGNED NOT NULL,
                user_id BIGINT(20) UNSIGNED NOT NULL,
                comment LONGTEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY ticket_id (ticket_id)
            ) $charset_collate;";

        $sql3 = "CREATE TABLE IF NOT EXISTS $table_name_priority (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            level INT(10) NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
        dbDelta($sql2);
        dbDelta($sql3);

        $this->insert_default_priorities();
    }

    public function insert_default_priorities()
    {
        global $wpdb;

        $priorities_exist = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM %i", MYIT_PRIORITIES));

        if (!$priorities_exist) {
            $wpdb->insert(MYIT_PRIORITIES, ['name' => 'Basse', 'level' => 1]);
            $wpdb->insert(MYIT_PRIORITIES, ['name' => 'Moyenne', 'level' => 2]);
            $wpdb->insert(MYIT_PRIORITIES, ['name' => 'Haute', 'level' => 3]);
        }
    }

    public function plugin_desactivation()
    {
        global $wpdb;

        // Utiliser `wpdb->prepare()` pour la suppression des tables
        $wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS %s", MYIT_TICKETS));
        $wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS %s", MYIT_COMMENTS));
        $wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS %s", MYIT_PRIORITIES));

        // Supprimer le dossier des uploads spécifiques
        $upload_dir = WP_CONTENT_DIR . '/uploads/wpit-uploads';

        if (is_dir($upload_dir)) {
            $this->delete_directory($upload_dir);
        }
    }

    private function delete_directory($dir)
    {
        if (!function_exists('WP_Filesystem')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        WP_Filesystem();
        global $wp_filesystem;
        if ($wp_filesystem->is_dir($dir)) {
            $files = $wp_filesystem->dirlist($dir);
            foreach ($files as $file => $fileinfo) {
                $filepath = trailingslashit($dir) . $file;
                if ('f' === $fileinfo['type']) {
                    wp_delete_file($filepath);
                } else {
                    $this->delete_directory($filepath);
                }
            }
            $wp_filesystem->delete($dir, true);
        }
    }
}
