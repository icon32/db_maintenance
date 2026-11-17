<?php
/**
 * Plugin Name:       DB Maintenance
 * Plugin URI:        https://lithosdigital.gr/
 * Description:       Puts the site behind a maintenance page, with a logo and text you choose.
 * Version:           1.8
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Dionisis Bolanis
 * Author URI:        https://lithosdigital.gr/
 * License:           GPL v2 or later
 * Text Domain:       db-maintenance
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/*
 * Duplicate-copy guard.
 *
 * If a second copy of this plugin is installed in another folder (uploading a
 * zip from GitHub creates "db_maintenance-main" next to "db_maintenance", for
 * example) both copies get loaded. Without this guard the second one fatals
 * the whole site with "Cannot redeclare ...".
 *
 * This only works because every function lives in includes/, required at
 * runtime below: PHP registers top-level functions while it compiles a file,
 * which happens before any code in that file - including this check - runs.
 */
if ( defined( 'DBCERN_MAINTENANCE_VERSION' ) ) {
    return;
}

define( 'DBCERN_MAINTENANCE_VERSION', '1.8' );

/*
 * Lets you switch the plugin off from wp-config.php when you cannot reach
 * wp-admin:  define( 'DBCERN_MAINTENANCE_OFF', true );
 */
if ( defined( 'DBCERN_MAINTENANCE_OFF' ) && DBCERN_MAINTENANCE_OFF ) {
    return;
}

/*
 * Paths only. Deliberately computed without calling any WordPress function.
 *
 * This file runs before `plugins_loaded`, and plugin_dir_url() fires the
 * `plugins_url` filter - which CDN, media-offload and security plugins hook.
 * Calling it this early can recurse or crash the PHP worker, which produces a
 * blank 503 from the server and nothing in the PHP log. The URL is needed by
 * exactly one function, so it is worked out lazily instead.
 */
define( 'DBCERN_MAINTENANCE_FILE', __FILE__ );
define( 'DBCERN_MAINTENANCE_DIR', rtrim( str_replace( '\\', '/', __DIR__ ), '/' ) . '/' );

// The folder this plugin is meant to live in. An upload that unpacks to any
// other folder name is renamed to this one so WordPress replaces the existing
// copy instead of installing a second one.
define( 'DBCERN_MAINTENANCE_FOLDER', 'db_maintenance' );

// Option and nonce names.
define( 'DBCERN_MAINTENANCE_OPTION', 'dbcern_maintenance_mode' );
define( 'DBCERN_MAINTENANCE_TOGGLE_NONCE', 'dbcern_toggle_maintenance' );
define( 'DBCERN_MAINTENANCE_USER_NONCE', 'dbcern_create_user' );
define( 'DBCERN_MAINTENANCE_SETTINGS_NONCE', 'dbcern_save_maintenance_page' );
define( 'DBCERN_MAINTENANCE_PAGE_SLUG', 'dbcern-maintenance-admin_page' );

/*
 * Load the plugin's code, but never white-screen the site if a file is missing
 * - an upload can arrive incomplete, and a bare require would take the whole
 * site down with it.
 */
$dbcern_maintenance_parts = array(
    DBCERN_MAINTENANCE_DIR . 'includes/upload-replace.php',
    DBCERN_MAINTENANCE_DIR . 'includes/db-maintenance.php',
);

$dbcern_maintenance_missing = array();
foreach ( $dbcern_maintenance_parts as $dbcern_maintenance_part ) {
    if ( is_readable( $dbcern_maintenance_part ) ) {
        require_once $dbcern_maintenance_part;
    } else {
        $dbcern_maintenance_missing[] = str_replace( DBCERN_MAINTENANCE_DIR, '', $dbcern_maintenance_part );
    }
}

if ( ! empty( $dbcern_maintenance_missing ) ) {
    // Captured by value. A plugin file is included at global scope, so a
    // global variable here would be unset along with the locals below.
    add_action(
        'admin_notices',
        function () use ( $dbcern_maintenance_missing ) {
            if ( ! current_user_can( 'activate_plugins' ) ) {
                return;
            }
            printf(
                '<div class="notice notice-error"><p><strong>DB Maintenance is not complete.</strong> '
                . 'These files are missing: %s. Re-upload the plugin zip.</p></div>',
                esc_html( implode( ', ', $dbcern_maintenance_missing ) )
            );
        }
    );
}

unset( $dbcern_maintenance_parts, $dbcern_maintenance_part, $dbcern_maintenance_missing );
