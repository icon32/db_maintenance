<?php
/**
 * Makes an uploaded copy of this plugin replace the installed one.
 *
 * WordPress identifies a plugin by its folder name. A zip downloaded from
 * GitHub unpacks to "db_maintenance-main", so WordPress treats it as a brand
 * new plugin and installs it alongside the existing "db_maintenance" folder.
 * Both copies then load, and the site dies with "Cannot redeclare ...".
 *
 * Renaming the uploaded folder back to the canonical name fixes the cause:
 * WordPress recognises it as the same plugin and overwrites it.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Finds this plugin's main file inside a directory.
 *
 * Identified by the DBCERN_MAINTENANCE_VERSION fingerprint next to a plugin
 * header, so it keeps working even if the file itself is renamed.
 *
 * @return string Full path to the main file, or '' if this is not our plugin.
 */
function dbcern_maintenance_find_main_file( $dir ) {
    if ( ! is_string( $dir ) || '' === $dir || ! is_dir( $dir ) ) {
        return '';
    }

    $files = glob( trailingslashit( $dir ) . '*.php' );
    if ( empty( $files ) ) {
        return '';
    }

    foreach ( $files as $file ) {
        $head = (string) @file_get_contents( $file, false, null, 0, 8192 );
        if ( false !== strpos( $head, 'Plugin Name:' ) && false !== strpos( $head, 'DBCERN_MAINTENANCE_VERSION' ) ) {
            return $file;
        }
    }

    return '';
}

/**
 * Renames an uploaded copy of this plugin to the canonical folder name.
 *
 * Runs after the zip is unpacked and before it is copied into place, so
 * WordPress sees the upload as the same plugin and replaces it.
 *
 * @param string $source        Unpacked folder being installed.
 * @param string $remote_source Working directory holding that folder.
 * @return string|WP_Error
 */
function dbcern_maintenance_normalise_upload_folder( $source, $remote_source, $upgrader = null, $hook_extra = array() ) {
    global $wp_filesystem;

    if ( ! is_string( $source ) || '' === $source || ! $wp_filesystem ) {
        return $source;
    }

    // Plugin installs and updates only - never themes or core.
    if ( ! empty( $hook_extra['type'] ) && 'plugin' !== $hook_extra['type'] ) {
        return $source;
    }
    if ( empty( $hook_extra['type'] ) && class_exists( 'Plugin_Upgrader' ) && ! ( $upgrader instanceof Plugin_Upgrader ) ) {
        return $source;
    }

    // Leave every other plugin alone.
    if ( '' === dbcern_maintenance_find_main_file( $source ) ) {
        return $source;
    }

    $source  = trailingslashit( $source );
    $desired = trailingslashit( trailingslashit( $remote_source ) . DBCERN_MAINTENANCE_FOLDER );

    if ( $source === $desired ) {
        return $source;
    }

    if ( ! $wp_filesystem->move( untrailingslashit( $source ), untrailingslashit( $desired ), true ) ) {
        return new WP_Error(
            'dbcern_rename_failed',
            'Could not rename the uploaded folder to "' . DBCERN_MAINTENANCE_FOLDER . '". Delete the old copy and upload again.'
        );
    }

    return $desired;
}
add_filter( 'upgrader_source_selection', 'dbcern_maintenance_normalise_upload_folder', 10, 4 );

/**
 * Lets an upload of this plugin overwrite the installed copy without the
 * "destination folder already exists" stop.
 *
 * Deliberately narrow: it only applies when the uploaded file is named after
 * this plugin, so no other plugin can ever be overwritten by this filter.
 */
function dbcern_maintenance_allow_overwrite( $options ) {
    if ( empty( $options['package'] ) || ! is_string( $options['package'] ) ) {
        return $options;
    }

    if ( ! empty( $options['hook_extra']['type'] ) && 'plugin' !== $options['hook_extra']['type'] ) {
        return $options;
    }

    $name = strtolower( basename( $options['package'] ) );
    if ( 0 !== strpos( $name, 'db_maintenance' ) && 0 !== strpos( $name, 'db-maintenance' ) ) {
        return $options;
    }

    $options['clear_destination']             = true;
    $options['abort_if_destination_exists']   = false;

    return $options;
}
add_filter( 'upgrader_package_options', 'dbcern_maintenance_allow_overwrite' );

/**
 * Warns when a second copy of this plugin is sitting in the plugins folder.
 *
 * The guard in db_maintenance.php stops it crashing the site, but the stray
 * folder should still be deleted.
 */
function dbcern_maintenance_duplicate_copy_notice() {
    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    if ( ! $screen || ! in_array( $screen->id, array( 'plugins', 'toplevel_page_' . DBCERN_MAINTENANCE_PAGE_SLUG ), true ) ) {
        return;
    }

    if ( ! current_user_can( 'activate_plugins' ) ) {
        return;
    }

    $ours   = untrailingslashit( DBCERN_MAINTENANCE_DIR );
    $others = array();

    // Only look at folders that could plausibly be a copy of this plugin,
        // instead of reading every plugin on the site.
        $candidates = array_merge(
            (array) glob( WP_PLUGIN_DIR . '/*maintenance*', GLOB_ONLYDIR ),
            (array) glob( WP_PLUGIN_DIR . '/db_*', GLOB_ONLYDIR )
        );

        foreach ( array_unique( $candidates ) as $dir ) {
        if ( untrailingslashit( $dir ) === $ours ) {
            continue;
        }
        if ( '' !== dbcern_maintenance_find_main_file( $dir ) ) {
            $others[] = basename( $dir );
        }
    }

    if ( empty( $others ) ) {
        return;
    }

    printf(
        '<div class="notice notice-warning"><p><strong>DB Maintenance:</strong> a second copy is installed in %1$s. '
        . 'The running copy is %2$s. Delete the other folder from Plugins to avoid confusion.</p></div>',
        esc_html( implode( ', ', $others ) ),
        esc_html( basename( $ours ) )
    );
}
add_action( 'admin_notices', 'dbcern_maintenance_duplicate_copy_notice' );
