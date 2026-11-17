<?php
/**
 * All plugin behaviour lives here.
 *
 * This file is required at RUNTIME by db_maintenance.php, after its
 * duplicate-copy guard. Keeping the declarations out of the main plugin
 * file is what makes that guard work: PHP early-binds top-level functions
 * at compile time, so a second copy of the plugin would otherwise fatal with
 * "Cannot redeclare ..." before the guard ever ran.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}


//===================================
// Options
//===================================

/**
 * Default shape of the plugin option.
 */
function dbcern_maintenance_default_options() {
    return array(
        'status'          => '',
        'page_image'       => '',
        'page_image_white' => 0,
        'page_title'      => '',
        'page_content'    => '',
        'page_footer'     => '',
        'page_background' => '',
        'page_timer'      => array(
            'status'     => '',
            'time_start' => '',
            'time_end'   => '',
        ),
        'page_social'     => array(
            'facebook'  => '',
            'tweeter'   => '',
            'linked_in' => '',
        ),
    );
}

/**
 * Always returns an array with every expected key present, even if the
 * option was deleted or stored as a non-array value.
 */
function dbcern_maintenance_get_options() {
    $options = get_option( DBCERN_MAINTENANCE_OPTION, array() );

    if ( ! is_array( $options ) ) {
        $options = array();
    }

    return array_merge( dbcern_maintenance_default_options(), $options );
}

/**
 * URL to a file inside this plugin.
 *
 * Worked out on demand rather than at load time: plugin_dir_url() fires the
 * `plugins_url` filter, which is not safe to trigger before `plugins_loaded`.
 */
function dbcern_maintenance_url( $path = '' ) {
    return plugin_dir_url( DBCERN_MAINTENANCE_FILE ) . ltrim( (string) $path, '/' );
}

/**
 * Is maintenance mode currently switched on?
 */
function dbcern_maintenance_is_active() {
    $options = dbcern_maintenance_get_options();

    return 'activate' === $options['status'];
}

/**
 * Activating the plugin must never take the site offline.
 *
 * add_option() does nothing when the row already exists, so a leftover
 * 'activate' status from an earlier install used to come straight back and
 * put the site behind the maintenance page the moment the plugin was switched
 * on. The logo and text are kept; only the on/off switch is forced to off.
 */
function dbcern_maintenance_activation_plugin() {
    $options           = dbcern_maintenance_get_options();
    $options['status'] = '';

    update_option( DBCERN_MAINTENANCE_OPTION, $options );
}
register_activation_hook( DBCERN_MAINTENANCE_FILE, 'dbcern_maintenance_activation_plugin' );

/**
 * Turn maintenance mode off on deactivation, but keep the settings.
 *
 * The logo and text are real configuration now, and deleting them on every
 * deactivate meant they were lost each time the plugin was updated by
 * uploading a new zip.
 */
function dbcern_maintenance_deactivation_plugin() {
    $options           = dbcern_maintenance_get_options();
    $options['status'] = '';

    update_option( DBCERN_MAINTENANCE_OPTION, $options );
}
register_deactivation_hook( DBCERN_MAINTENANCE_FILE, 'dbcern_maintenance_deactivation_plugin' );

//===================================
// Admin bar toggle
//===================================

function dbcern_maintenance_admin_bar_item( WP_Admin_Bar $wp_admin_bar ) {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $menu_id   = 'dbcern_maintenance_mode';
    $is_active = dbcern_maintenance_is_active();

    if ( $is_active ) {
        $button_title = '<span style="width:5px;height:5px;background-color:red;border-radius:5px;padding:3px;">Maintenance Mode Activated</span>';
    } else {
        $button_title = '<span style="width:5px;height:5px;background-color:green;border-radius:5px;padding:3px;">Maintenance Mode Deactivated</span>';
    }

    $wp_admin_bar->add_menu(
        array(
            'id'     => $menu_id,
            'parent' => null, // use 'top-secondary' for toggle menu position.
            'href'   => '#',
            'title'  => $button_title,
        )
    );

    $target     = $is_active ? 'deactivate' : 'activate';
    $toggle_url = wp_nonce_url(
        add_query_arg( 'maintenance', $target, admin_url() ),
        DBCERN_MAINTENANCE_TOGGLE_NONCE
    );

    $wp_admin_bar->add_menu(
        array(
            'parent' => $menu_id,
            'title'  => $is_active ? 'Disable' : 'Enable',
            'id'     => $is_active ? 'disable_maintenance' : 'enable_maintenance',
            'href'   => $toggle_url,
        )
    );
}
add_action( 'admin_bar_menu', 'dbcern_maintenance_admin_bar_item', 100 );

/**
 * Handles the ?maintenance=activate|deactivate toggle.
 *
 * Requires manage_options plus a valid nonce, so the mode cannot be flipped
 * by tricking a logged-in administrator into following a crafted link.
 */
function dbcern_maintenance_option_save() {
    if ( ! isset( $_GET['maintenance'] ) ) {
        return;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
    if ( ! wp_verify_nonce( $nonce, DBCERN_MAINTENANCE_TOGGLE_NONCE ) ) {
        return;
    }

    $requested = sanitize_key( wp_unslash( $_GET['maintenance'] ) );
    if ( ! in_array( $requested, array( 'activate', 'deactivate' ), true ) ) {
        return;
    }

    $options           = dbcern_maintenance_get_options();
    $options['status'] = $requested;
    update_option( DBCERN_MAINTENANCE_OPTION, $options );
}
add_action( 'admin_init', 'dbcern_maintenance_option_save' );

//===================================
// Set Our Maintenance Page Template
//===================================

function dbcern_maintenance_page_template_redirect() {
    // Never intercept admin, login, AJAX, cron or CLI requests.
    if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
        return;
    }

    if ( defined( 'WP_CLI' ) && WP_CLI ) {
        return;
    }

    if ( isset( $GLOBALS['pagenow'] ) && 'wp-login.php' === $GLOBALS['pagenow'] ) {
        return;
    }

    if ( ! dbcern_maintenance_is_active() || is_user_logged_in() ) {
        return;
    }

    $page_template = DBCERN_MAINTENANCE_DIR . 'template/maintenance-page-template.php';
    if ( ! file_exists( $page_template ) ) {
        return;
    }

    /*
     * Deliberately answers 200, not 503.
     *
     * 503 is the technically correct status for a site that is deliberately
     * offline, and it keeps search engines from indexing the placeholder. But
     * CDNs and page caches happily store an error response, and once a 503 is
     * cached it is served to everyone with PHP never running - so the site
     * looks dead, nothing appears in any log, and it only recovers when the
     * cache expires. That trade is not worth it on client sites.
     *
     * Put define( 'DBCERN_MAINTENANCE_SEND_503', true ); in wp-config.php to
     * opt back in where you know the caching layer is safe.
     */
    if ( ! headers_sent() ) {
        nocache_headers();

        if ( defined( 'DBCERN_MAINTENANCE_SEND_503' ) && DBCERN_MAINTENANCE_SEND_503 ) {
            status_header( 503 );
            header( 'Retry-After: 3600' );
        }
    }

    include $page_template;
    exit;
}
add_action( 'init', 'dbcern_maintenance_page_template_redirect' );

//=====================================
// Set Our Maintenance Page Template END
//=====================================

// Remove admin bar for users who cannot manage the site.
function dbcern_remove_admin_bar() {
    if ( ! current_user_can( 'manage_options' ) && ! is_admin() ) {
        show_admin_bar( false );
    }
}
add_action( 'after_setup_theme', 'dbcern_remove_admin_bar' );

// Remove access to the dashboard for subscribers and contributors.
function dbcern_redirect_backend_to_front() {
    if ( ! is_admin() || wp_doing_ajax() ) {
        return;
    }

    $user = wp_get_current_user();
    if ( ! $user || ! $user->exists() || current_user_can( 'manage_options' ) ) {
        return;
    }

    $blocked_roles = array( 'subscriber', 'contributor' );
    if ( array_intersect( $blocked_roles, (array) $user->roles ) ) {
        wp_safe_redirect( home_url() );
        exit;
    }
}
add_action( 'init', 'dbcern_redirect_backend_to_front' );

/*
*=========================================
* Custom admin login header link   /START
*=========================================
*/
function dbcern_custom_login_url() {
    return home_url( '/' );
}
add_filter( 'login_headerurl', 'dbcern_custom_login_url' );

/**
 * Custom admin login header link alt text
 */
function dbcern_custom_login_title() {
    return get_option( 'blogname' );
}
add_filter( 'login_headertitle', 'dbcern_custom_login_title' );

/**
 * Styles the WordPress login page to match the maintenance page, using the
 * logo chosen in the settings.
 *
 * Only while maintenance mode is on, so the login screen goes back to normal
 * as soon as the site is live again.
 */
function dbcern_maintenance_login_branding() {
    if ( ! dbcern_maintenance_is_active() ) {
        return;
    }

    $options  = dbcern_maintenance_get_options();
    $logo_id  = absint( $options['page_image'] );
    $is_white = ! empty( $options['page_image_white'] );

    $logo_url = '';
    if ( $logo_id && wp_attachment_is_image( $logo_id ) ) {
        $src = wp_get_attachment_image_src( $logo_id, 'medium' );
        if ( ! empty( $src[0] ) ) {
            $logo_url = $src[0];
        }
    }
    ?>
    <style type="text/css">
        body.login {
            background-color: #06141A;
            background-image:
                radial-gradient(60vmax 60vmax at 12% 8%, rgba(15,122,107,0.55) 0%, rgba(15,122,107,0) 70%),
                radial-gradient(52vmax 52vmax at 88% 92%, rgba(27,168,160,0.42) 0%, rgba(27,168,160,0) 68%),
                radial-gradient(46vmax 46vmax at 60% 40%, rgba(21,53,107,0.5) 0%, rgba(21,53,107,0) 72%);
            background-repeat: no-repeat;
            color: #B6CECA;
        }

        <?php if ( '' !== $logo_url ) : ?>
        .login h1 a {
            background-image: url("<?php echo esc_url( $logo_url ); ?>");
            background-size: contain;
            background-position: center center;
            width: 220px;
            height: 84px;
            margin-bottom: 24px;
            <?php if ( $is_white ) : ?>
            filter: brightness(0) invert(1);
            <?php endif; ?>
        }
        <?php else : ?>
        /* No logo chosen: hide the WordPress mark rather than show it on dark. */
        .login h1 a {
            background: none;
            width: auto;
            height: auto;
            text-indent: 0;
            font-family: "Iowan Old Style", "Palatino Linotype", Palatino, Georgia, serif;
            font-size: 20px;
            line-height: 1.4;
            letter-spacing: 0.04em;
            color: #F2F8F6;
            overflow: visible;
        }
        <?php endif; ?>

        .login form {
            background: linear-gradient(180deg, rgba(13,38,44,0.78) 0%, rgba(6,20,26,0.86) 100%);
            border: 1px solid rgba(255,255,255,0.16);
            border-radius: 16px;
            box-shadow: 0 1px 0 rgba(255,255,255,0.18) inset, 0 30px 70px -25px rgba(0,0,0,0.75);
        }

        .login form label,
        .login .forgetmenot label {
            color: #B6CECA;
        }

        .login input[type="text"],
        .login input[type="password"] {
            background: rgba(6,20,26,0.6);
            border: 1px solid rgba(255,255,255,0.18);
            color: #F2F8F6;
            border-radius: 8px;
            box-shadow: none;
        }

        .login input[type="text"]:focus,
        .login input[type="password"]:focus {
            border-color: #D6A861;
            box-shadow: 0 0 0 1px #D6A861;
            outline: 2px solid transparent;
        }

        .login .button-primary {
            background: #D6A861;
            border-color: #D6A861;
            color: #06141A;
            text-shadow: none;
            border-radius: 8px;
            box-shadow: none;
        }

        .login .button-primary:hover,
        .login .button-primary:focus {
            background: #E2BA7C;
            border-color: #E2BA7C;
            color: #06141A;
        }

        .login #nav a,
        .login #backtoblog a {
            color: rgba(182,206,202,0.85);
        }

        .login #nav a:hover,
        .login #backtoblog a:hover,
        .login #nav a:focus,
        .login #backtoblog a:focus {
            color: #D6A861;
        }

        .login .dashicons-visibility:before {
            color: #B6CECA;
        }

        .login #login_error,
        .login .message,
        .login .success {
            background: rgba(6,20,26,0.9);
            color: #F2F8F6;
            border-left-color: #D6A861;
        }

        .login #login_error a,
        .login .message a {
            color: #D6A861;
        }
    </style>
    <?php
}
add_action( 'login_head', 'dbcern_maintenance_login_branding' );

/*
*=========================================
* Custom admin login header link   /END
*=========================================
*/

//===================================
// Admin page
//===================================

function dbcern_maintenance_admin_page_register() {
    add_menu_page(
        'DB Maintenance',                          // Page title
        'DB Maintenance',                          // Menu title
        'manage_options',                          // Capability required to access
        DBCERN_MAINTENANCE_PAGE_SLUG,              // Page slug
        'dbcern_maintenance_admin_page_display',   // Callback function to display the page content
        'dashicons-admin-users',                   // Icon URL or class
        1                                          // Position in the menu
    );
}
add_action( 'admin_menu', 'dbcern_maintenance_admin_page_register' );

/**
 * Shows an unmissable warning on every admin screen while visitors are being
 * served the maintenance page, with a one-click way to turn it off.
 *
 * Without this the site can be offline to the public with nothing in the logs
 * and nothing obvious in wp-admin to explain it.
 */
function dbcern_maintenance_active_notice() {
    if ( ! current_user_can( 'manage_options' ) || ! dbcern_maintenance_is_active() ) {
        return;
    }

    $settings_url = menu_page_url( DBCERN_MAINTENANCE_PAGE_SLUG, false );
    $toggle_url   = wp_nonce_url(
        add_query_arg( 'maintenance', 'deactivate', $settings_url ),
        DBCERN_MAINTENANCE_TOGGLE_NONCE
    );

    printf(
        '<div class="notice notice-error"><p><strong>Maintenance mode is on.</strong> '
        . 'Visitors cannot see this site - they get the maintenance page and an HTTP 503 response. '
        . '<a href="%1$s">Turn it off</a> or <a href="%2$s">open the settings</a>.</p></div>',
        esc_url( $toggle_url ),
        esc_url( $settings_url )
    );
}
add_action( 'admin_notices', 'dbcern_maintenance_active_notice' );

/**
 * Loads the media library picker on this plugin's screen only.
 */
function dbcern_maintenance_admin_assets( $hook_suffix ) {
    if ( 'toplevel_page_' . DBCERN_MAINTENANCE_PAGE_SLUG !== $hook_suffix ) {
        return;
    }

    wp_enqueue_media();
}
add_action( 'admin_enqueue_scripts', 'dbcern_maintenance_admin_assets' );

/**
 * Prints an admin notice.
 */
function dbcern_maintenance_notice( $message, $type = 'success' ) {
    printf(
        '<div class="notice notice-%1$s"><p>%2$s</p></div>',
        esc_attr( $type ),
        esc_html( $message )
    );
}

//===================================
// Maintenance page settings
//===================================

/**
 * Saves the logo and the text shown on the maintenance page.
 */
function dbcern_maintenance_handle_settings() {
    if ( ! isset( $_POST['dbcern_save_settings'] ) ) {
        return;
    }

    check_admin_referer( DBCERN_MAINTENANCE_SETTINGS_NONCE );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'You do not have permission to change these settings.', '', array( 'response' => 403 ) );
    }

    $options = dbcern_maintenance_get_options();

    $options['page_image']       = isset( $_POST['page_image'] ) ? absint( $_POST['page_image'] ) : 0;
    $options['page_image_white'] = isset( $_POST['page_image_white'] ) ? 1 : 0;
    $options['page_title']   = isset( $_POST['page_title'] ) ? sanitize_text_field( wp_unslash( $_POST['page_title'] ) ) : '';
    $options['page_content'] = isset( $_POST['page_content'] ) ? wp_kses_post( wp_unslash( $_POST['page_content'] ) ) : '';

    // Only keep an image that actually exists in the media library.
    if ( $options['page_image'] && ! wp_attachment_is_image( $options['page_image'] ) ) {
        $options['page_image'] = 0;
    }

    update_option( DBCERN_MAINTENANCE_OPTION, $options );

    dbcern_maintenance_notice( 'Maintenance page saved.' );
}

/**
 * Renders the maintenance page settings form.
 */
function dbcern_maintenance_render_settings( $form_action ) {
    $options   = dbcern_maintenance_get_options();
    $logo_id   = absint( $options['page_image'] );
    $logo_html = ( $logo_id && wp_attachment_is_image( $logo_id ) )
        ? wp_get_attachment_image( $logo_id, 'medium', false, array( 'style' => 'max-width:220px;height:auto;display:block;' ) )
        : '';

    $is_active  = dbcern_maintenance_is_active();
    $toggle_url = wp_nonce_url(
        add_query_arg( 'maintenance', $is_active ? 'deactivate' : 'activate', $form_action ),
        DBCERN_MAINTENANCE_TOGGLE_NONCE
    );
    ?>
    <h2>Maintenance page</h2>

    <p>
        Status:
        <strong style="color:<?php echo $is_active ? '#b32d2e' : '#00710a'; ?>;">
            <?php echo $is_active ? 'Visitors are seeing the maintenance page' : 'Site is live'; ?>
        </strong>
        &nbsp;<a class="button" href="<?php echo esc_url( $toggle_url ); ?>"><?php echo $is_active ? 'Turn off' : 'Turn on'; ?></a>
        <?php if ( $is_active ) : ?>
            &nbsp;<a href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener">Preview</a>
        <?php endif; ?>
    </p>

    <form method="post" action="<?php echo esc_url( $form_action ); ?>">
        <?php wp_nonce_field( DBCERN_MAINTENANCE_SETTINGS_NONCE ); ?>
        <input type="hidden" name="dbcern_save_settings" value="1">
        <input type="hidden" name="page_image" id="dbcern-logo-id" value="<?php echo esc_attr( $logo_id ? $logo_id : '' ); ?>">

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">Logo</th>
                <td>
                    <div id="dbcern-logo-preview" style="margin-bottom:10px;<?php echo '' === $logo_html ? 'display:none;' : ''; ?>">
                        <?php echo $logo_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built by wp_get_attachment_image(). ?>
                    </div>
                    <button type="button" class="button" id="dbcern-pick-logo">Choose image</button>
                    <button type="button" class="button-link" id="dbcern-remove-logo" style="<?php echo '' === $logo_html ? 'display:none;' : ''; ?>margin-left:8px;">Remove</button>
                    <p class="description">Shown at the top of the maintenance page. Leave empty to show the site name instead.</p>
                    <p style="margin-top:10px;">
                        <label>
                            <input type="checkbox" name="page_image_white" value="1" <?php checked( 1, absint( $options['page_image_white'] ) ); ?>>
                            Render the logo in white
                        </label>
                    </p>
                    <p class="description">The maintenance page has a dark background, so a dark logo disappears on it. Tick this to show a single-colour logo in white, or upload a light version instead.</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="dbcern-page-title">Heading</label></th>
                <td>
                    <input type="text" class="regular-text" id="dbcern-page-title" name="page_title"
                        value="<?php echo esc_attr( $options['page_title'] ); ?>"
                        placeholder="We will be back shortly">
                    <p class="description">The large line of text. Leave empty to use the default.</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="dbcern-page-content">Message</label></th>
                <td>
                    <textarea class="large-text" rows="4" id="dbcern-page-content" name="page_content"
                        placeholder="This site is briefly offline while we make a few updates. Thanks for your patience."><?php echo esc_textarea( $options['page_content'] ); ?></textarea>
                    <p class="description">The paragraph under the heading. Basic HTML and links are allowed.</p>
                </td>
            </tr>
        </table>

        <?php submit_button( 'Save maintenance page' ); ?>
    </form>

    <script>
    jQuery( function ( $ ) {
        var frame;

        $( '#dbcern-pick-logo' ).on( 'click', function () {
            if ( ! frame ) {
                frame = wp.media( {
                    title: 'Choose a logo',
                    button: { text: 'Use this image' },
                    library: { type: 'image' },
                    multiple: false
                } );

                frame.on( 'select', function () {
                    var attachment = frame.state().get( 'selection' ).first().toJSON(),
                        url = ( attachment.sizes && attachment.sizes.medium )
                            ? attachment.sizes.medium.url
                            : attachment.url;

                    $( '#dbcern-logo-id' ).val( attachment.id );
                    $( '#dbcern-logo-preview' )
                        .empty()
                        .append( $( '<img>' ).attr( 'src', url ).attr( 'alt', '' ).css( { maxWidth: '220px', height: 'auto', display: 'block' } ) )
                        .show();
                    $( '#dbcern-remove-logo' ).show();
                } );
            }

            frame.open();
        } );

        $( '#dbcern-remove-logo' ).on( 'click', function () {
            $( '#dbcern-logo-id' ).val( '' );
            $( '#dbcern-logo-preview' ).empty().hide();
            $( this ).hide();
        } );
    } );
    </script>

    <hr style="margin:30px 0;">
    <?php
}

/**
 * Builds a one-time password set/reset link for a freshly created user.
 *
 * Returns an empty string if the key could not be generated.
 */
function dbcern_maintenance_password_reset_url( WP_User $user ) {
    $key = get_password_reset_key( $user );

    if ( is_wp_error( $key ) ) {
        return '';
    }

    return network_site_url(
        'wp-login.php?action=rp&key=' . rawurlencode( $key ) . '&login=' . rawurlencode( $user->user_login ),
        'login'
    );
}

/**
 * Emails a new user a one-time link to set their own password.
 *
 * No password is ever put in the message body.
 */
function dbcern_maintenance_send_welcome_email( WP_User $user, $reset_url ) {
    if ( '' === $reset_url ) {
        return false;
    }

    $site_url = home_url();

    $subject = 'Login Details for Website: ' . $site_url;
    $message = sprintf(
        '<br> Welcome to our site.<br><br> Username: %1$s <br> Set your password: <a href="%2$s">Here</a><br> Login Link: <a href="%3$s">%3$s</a>',
        esc_html( $user->user_login ),
        esc_url( $reset_url ),
        esc_url( admin_url() )
    );

    $headers = array( 'Content-Type: text/html; charset=UTF-8' );

    return wp_mail( $user->user_email, $subject, $message, $headers );
}

/**
 * Creates a user with a random password and a single one-time password link.
 *
 * The reset key is generated exactly once, so the link that is emailed and the
 * link shown on screen are always the same working link.
 *
 * @param bool $send_email Whether to email the new user their set-password link.
 * @return array|WP_Error array with 'user' (WP_User) and 'reset_url' (string).
 */
function dbcern_maintenance_create_user( $username, $email, $role, $send_email = false ) {
    $allowed_roles = array( 'subscriber', 'administrator' );

    if ( ! in_array( $role, $allowed_roles, true ) ) {
        return new WP_Error( 'dbcern_invalid_role', 'Invalid role selected.' );
    }

    if ( 'administrator' === $role && ! current_user_can( 'promote_users' ) ) {
        return new WP_Error( 'dbcern_cannot_promote', 'You are not allowed to create administrators.' );
    }

    if ( '' === $username || ! validate_username( $username ) ) {
        return new WP_Error( 'dbcern_invalid_username', 'Invalid username.' );
    }

    if ( ! is_email( $email ) ) {
        return new WP_Error( 'dbcern_invalid_email', 'Invalid email address.' );
    }

    $user_id = wp_create_user( $username, wp_generate_password( 24, true, true ), $email );

    if ( is_wp_error( $user_id ) ) {
        return $user_id;
    }

    $user = new WP_User( $user_id );
    $user->set_role( $role );

    // Generate the one-time key once and reuse it everywhere.
    $reset_url = dbcern_maintenance_password_reset_url( $user );

    if ( $send_email ) {
        dbcern_maintenance_send_welcome_email( $user, $reset_url );
    }

    return array(
        'user'      => $user,
        'reset_url' => $reset_url,
    );
}

/**
 * Handles the "create user manually" form.
 */
function dbcern_maintenance_handle_manual_user() {
    if ( ! isset( $_POST['manualuser'] ) ) {
        return;
    }

    check_admin_referer( DBCERN_MAINTENANCE_USER_NONCE );

    if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'create_users' ) ) {
        wp_die( 'You do not have permission to create users.', '', array( 'response' => 403 ) );
    }

    $username = isset( $_POST['username'] ) ? sanitize_user( wp_unslash( $_POST['username'] ), true ) : '';
    $email    = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
    $role     = isset( $_POST['role'] ) ? sanitize_key( wp_unslash( $_POST['role'] ) ) : '';

    // Matches the original behaviour of this form: no email is sent, the
    // administrator hands the link over themselves.
    $result = dbcern_maintenance_create_user( $username, $email, $role, false );

    if ( is_wp_error( $result ) ) {
        dbcern_maintenance_notice( $result->get_error_message(), 'error' );
        return;
    }

    $user      = $result['user'];
    $reset_url = $result['reset_url'];

    dbcern_maintenance_notice(
        sprintf(
            'User created successfully. Username: %1$s - Email: %2$s - Role: %3$s.',
            $user->user_login,
            $user->user_email,
            $role
        )
    );

    if ( '' !== $reset_url ) {
        printf(
            '<div class="notice notice-info"><p>One-time password link (expires after use): <code>%1$s</code></p><p>Dashboard: <a href="%2$s">%2$s</a></p></div>',
            esc_html( $reset_url ),
            esc_url( admin_url() )
        );
    }
}

/**
 * Handles the "create automatic users for Lithos" form.
 */
function dbcern_maintenance_handle_lithos_users() {
    if ( ! isset( $_POST['autolithosusers'] ) ) {
        return;
    }

    check_admin_referer( DBCERN_MAINTENANCE_USER_NONCE );

    if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'create_users' ) ) {
        wp_die( 'You do not have permission to create users.', '', array( 'response' => 403 ) );
    }

    $lithos_users = array(
        'dbolanis'      => 'd.bolanis@lithosdigital.gr',
        'karakw'        => 'd.karakwttas@lithosdigital.gr',
        'Lithos1'       => 'a.meksis@lithosdigital.gr',
        'elpida'        => 'elpida@lithosdigital.gr',
        'LithosSupport' => 'support@lithosdigital.gr',
        'danai'         => 'danai@lithosdigital.com',
        'giorgos'       => 'giorgos@lithosdigital.com',
        'panagiota'     => 'p.xenitidou@lithosdigital.gr',
    );

    foreach ( $lithos_users as $username => $email ) {
        if ( ! isset( $_POST[ $username ] ) || 'on' !== $_POST[ $username ] ) {
            continue;
        }

        $result = dbcern_maintenance_create_user( $username, $email, 'administrator', true );

        if ( is_wp_error( $result ) ) {
            dbcern_maintenance_notice( $email . ': ' . $result->get_error_message(), 'error' );
            continue;
        }

        $user = $result['user'];

        dbcern_maintenance_notice(
            sprintf(
                'User created successfully. Username: %1$s - Email: %2$s - Role: administrator. A set-password email has been sent.',
                $user->user_login,
                $user->user_email
            )
        );
    }
}

// Callback function to display the content of the backend page
function dbcern_maintenance_admin_page_display() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'You do not have permission to access this page.', '', array( 'response' => 403 ) );
    }

    $form_action = menu_page_url( DBCERN_MAINTENANCE_PAGE_SLUG, false );
    ?>
    <div class="wrap">
        <h1>DB Maintenance</h1>

        <?php
        $request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : '';
        if ( 'POST' === $request_method ) {
            dbcern_maintenance_handle_settings();
            dbcern_maintenance_handle_manual_user();
            dbcern_maintenance_handle_lithos_users();
        }

        dbcern_maintenance_render_settings( $form_action );
        ?>

        <h2>Users</h2>
    <div class="custom-registration-form">

        <!-- HTML form -->
        <form method="post" action="<?php echo esc_url( $form_action ); ?>">

            <?php wp_nonce_field( DBCERN_MAINTENANCE_USER_NONCE ); ?>
            <input type="hidden" id="manualuser" name="manualuser" value="1">
            <table style="border:1px solid black;padding:20px;">
                <tr>
                    <td><h2>Create Users Manualy</h2></td>
                    <td></td>
                </tr>
                <tr>
                    <td><label for="username">Username:</label></td>
                    <td><input type="text" id="username" name="username" required></td>
                </tr>
                <tr>
                    <td><label for="email">Email:</label></td>
                    <td><input type="email" id="email" name="email" required></td>
                </tr>
                <tr>
                    <td><label for="role">Role:</label></td>
                    <td>
                        <select id="role" name="role" required>
                            <option value="subscriber">Subscriber</option>
                            <option value="administrator">Administrator</option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <td><input type="submit" value="Submit"></td>
                    <td></td>
                </tr>
            </table>
        </form>



        <form method="post" action="<?php echo esc_url( $form_action ); ?>">

            <?php wp_nonce_field( DBCERN_MAINTENANCE_USER_NONCE ); ?>
            <input type="hidden" id="autolithosusers" name="autolithosusers" value="1">
            <!--<table style="border:1px solid black;padding:20px;margin-top:30px;">-->
            <!--    <tr>-->
            <!--        <td><h2>Create Automatic Users for Lithos</h2></td>-->
            <!--        <td></td>-->

            <!--    </tr>-->
            <!--    <tr>-->
            <!--        <td>-->
            <!--            <input type="checkbox" name="dbolanis" > Dionisis <br>-->
            <!--            <input type="checkbox" name="karakw" > Dimitris<br>-->
            <!--            <input type="checkbox" name="Lithos1" > Andreas<br>-->
            <!--            <input type="checkbox" name="elpida" > Elpida<br>-->
            <!--            <input type="checkbox" name="danai" > Danai<br>-->
            <!--            <input type="checkbox" name="giorgos" > Giorgos<br>-->
            <!--            <input type="checkbox" name="panagiota" > Panagiwta<br>-->
            <!--            <input type="checkbox" name="LithosSupport" > Support<br>-->
            <!--        </td>-->
            <!--    </tr>-->
            <!--    <tr>-->
            <!--        <td><input type="submit" value="Submit"></td>-->
            <!--        <td></td>-->
            <!--    </tr>-->
            <!--</table>-->
        </form>
    </div>



    </div>
    <?php
}
