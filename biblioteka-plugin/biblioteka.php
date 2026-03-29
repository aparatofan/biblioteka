<?php
/**
 * Plugin Name: Biblioteka
 * Description: A book library plugin for WordPress. Add books with title, author, category, cover image, and related post URL.
 * Version: 1.3.0
 * Author: Czytaj Mądrze
 * Text Domain: biblioteka
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'BIBLIOTEKA_VERSION', '1.3.0' );
define( 'BIBLIOTEKA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BIBLIOTEKA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Create the books table on plugin activation.
 */
function biblioteka_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'biblioteka_books';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        title VARCHAR(500) NOT NULL,
        description TEXT NOT NULL,
        author VARCHAR(500) NOT NULL,
        category VARCHAR(200) NOT NULL,
        image_url VARCHAR(1000) DEFAULT '',
        related_post_url VARCHAR(1000) DEFAULT '',
        bookstore_url VARCHAR(1000) DEFAULT '',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );

    // Ensure description column exists (dbDelta may skip TEXT columns on older MySQL).
    $row = $wpdb->get_row( "SHOW COLUMNS FROM $table_name LIKE 'description'" );
    if ( ! $row ) {
        $wpdb->query( "ALTER TABLE $table_name ADD description TEXT NOT NULL AFTER title" );
    }

    update_option( 'biblioteka_db_version', BIBLIOTEKA_VERSION );
}
register_activation_hook( __FILE__, 'biblioteka_activate' );

/**
 * Update the database schema if the plugin version has changed.
 */
function biblioteka_check_db_update() {
    if ( get_option( 'biblioteka_db_version' ) !== BIBLIOTEKA_VERSION ) {
        biblioteka_activate();
    }
}
add_action( 'plugins_loaded', 'biblioteka_check_db_update' );

/**
 * Return the list of valid categories.
 */
function biblioteka_get_categories() {
    return array(
        'Science & Nature',
        'Psychology & Human Nature',
        'Philosophy & Meaning',
        'Faith & Worldview',
        'Practical Skills & Productivity',
        'Learning & Education',
        'Money & Business',
        'Language & Communication',
        'Biographies',
        'Misc',
    );
}

// Load admin functionality.
if ( is_admin() ) {
    require_once BIBLIOTEKA_PLUGIN_DIR . 'admin/admin-page.php';
}

// Load frontend functionality.
require_once BIBLIOTEKA_PLUGIN_DIR . 'public/shortcode.php';
