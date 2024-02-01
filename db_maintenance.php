<?php 
/**
 * Plugin Name:       LithosDigital Maintenance Page 
 * Description:       Maintenance Page Plugin
 * Version:           1.1
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Dionisis Bolanis
 * Author URI:        https://lithosdigital.gr/
 * License:           GPL v2 or later
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function dbcern_maintenance_activation_plugin() {
    add_option( 'dbcern_maintenance_mode', array(
        'status'=> false,
        'page_image' => '',
        'page_title' => '',
        'page_content'=> '',
        'page_footer'=> '',
        'page_background'=> '',
        'page_timer' => array(
            'status' => '',
            'time_start' => '',
            'time_end' => ''
        ),
        'page_social' => array(
            'facebook'=> '',
            'tweeter' => '',
            'linked_in'=>'',
        )
    ));
}
register_activation_hook( __FILE__, 'dbcern_maintenance_activation_plugin' );

// Delete table when deactivate
function dbcern_maintenance_deactivation_plugin() {
    delete_option("dbcern_maintenance_mode");
}    
register_deactivation_hook( __FILE__, 'dbcern_maintenance_deactivation_plugin' );


add_action('init',function(){
    
});
function dbcern_maintenance_admin_bar_item( WP_Admin_Bar $wp_admin_bar ) {
    // Display Menu only for wp-admin area
    // if ( !is_admin() ) {
    //     return;
    // } 
    $dbcern_maintenance_options = get_option('dbcern_maintenance_mode',true);
 
    $menu_id = 'dbcern_maintenance_mode';
    if($dbcern_maintenance_options['status'] == 'activate'){
        $button_title =' <span style="width:5px;height:5px;background-color:red;border-radius:5px;padding:3px;">Maintenance Mode Activated</span>';
    }else{
        $button_title ='<span style="width:5px;height:5px;background-color:green;border-radius:5px;padding:3px;">Maintenance Mode Deactivated</span>';
    }
    $wp_admin_bar->add_menu(
        array(
            'id'     => $menu_id,
            'parent' => null , // use 'top-secondary' for toggle menu position.
            'href'   => '#',
            'title'  => $button_title,
        )
    );
    if($dbcern_maintenance_options['status'] == 'activate'){
        $wp_admin_bar->add_menu(
            array(
                'parent' => $menu_id,
                'title'  => 'Disable',
                'id'     => 'disable_maintenance',
                'href'   => '/wp-admin/?maintenance=deactivate',
            )
        );

    }else{
        $wp_admin_bar->add_menu(
            array(
                'parent' => $menu_id,
                'title'  => 'Enable',
                'id'     => 'enable_maintenance',
                'href'   => '/wp-admin/?maintenance=activate',
            )
        );
    }
    
    
 
}
add_action( 'admin_bar_menu', 'dbcern_maintenance_admin_bar_item', 100 );

function dbcern_maintenance_option_save(){
    if(current_user_can( 'manage_options' )){    
        if(isset($_GET['maintenance'])){
            $dbcern_maintenance_options = get_option('dbcern_maintenance_mode',true);
            $dbcern_maintenance_options['status'] = $_GET['maintenance'];
            update_option('dbcern_maintenance_mode',$dbcern_maintenance_options);
            // echo $_GET['maintenance'];
        }
    }
}
//backend
// add_action( 'admin_footer', 'dbcern_maintenance_option_save' );
add_action( 'admin_init', 'dbcern_maintenance_option_save' );
// FronEnd
// add_action( 'wp_footer', 'dbcern_maintenance_option_save' , 100);
add_action( 'init', 'dbcern_maintenance_option_save' , 100);

//===================================
//Set Our Maintenance Page Template
//===================================
function dbcern_maintenance_page_template_redirect(){

    global $wp;
    $uri = $_SERVER['REQUEST_URI'];
    $protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $url = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

    if(!str_contains( $url, home_url().'/wp-admin') && !str_contains( $url, home_url().'/wp-login.php')){
        $dbcern_maintenance_options = get_option('dbcern_maintenance_mode',true);
      if($dbcern_maintenance_options['status'] == 'activate'){
        if(! is_user_logged_in()){
          $page_template = plugin_dir_path(__FILE__) . '/template/maintenance-page-template.php';
          include $page_template;
          exit;
        }
      }
    }
  
  }
  add_action( 'init', 'dbcern_maintenance_page_template_redirect' );

//=====================================
//Set Our Maintenance Page Template END
//=====================================


//Remove admin Bar
function dbcern_remove_admin_bar() {
    if (!current_user_can('administrator') && !is_admin()) {
        show_admin_bar(false);
    }
}
add_action('after_setup_theme', 'dbcern_remove_admin_bar');

//Remove access to dashboard if not admin
function dbcern_redirect_backend_to_front(){
    if( is_admin() && !defined('DOING_AJAX') && ( current_user_can('subscriber') || current_user_can('contributor') ) ){
        wp_redirect(home_url());
        exit;
    }
}
add_action('init','dbcern_redirect_backend_to_front');



/*
*=========================================
* Custom admin login header link   /START
*=========================================
*/
function custom_login_url() {
    return home_url( '/' );
}
add_filter( 'login_headerurl', 'custom_login_url' );
/**
 * Custom admin login header link alt text
 */
function custom_login_title() {
   return get_option( 'blogname' );
}
add_filter( 'login_headertitle', 'custom_login_title' );

/**
 * Custom admin login header logo
 */
function custom_login_logo() {
    ?>
    <style type="text/css">
        
        h1 a { 
            background-image:url(<?php echo plugin_dir_url( __FILE__ ).'img/logo_white.png'; ?>) !important;
            background-size: 150px!important;
            width:150px!important;
            height:50px!important;
        }
        body{
            background-image: url(<?php echo plugin_dir_url( __FILE__ ).'img/backgrount.jpg'; ?>)!important;
            background-repeat: no-repeat!important;
            background-size: cover!important;
        }
        .login form{
            border: 0px!important;
            background:rgba(1,20,71,0)!important;
        }
        #login{
            float:right!important;
            background-color:rgba(1,20,71,0.3)!important;
            padding: 0!important;
            color:white;
            margin-top:10%;
        }
        #login a{
            color:white!important;
        }

        .login #login_error {
            border: 4px solid #dc3232!important;
        }

        .login .message, .login .success {
            border: 4px solid #00a0d2!important;
            padding: 12px;
            margin-left: 0;
            margin-bottom: 20px;
            background-color: #fff;
            box-shadow: 0 1px 1px 0 rgba(0,0,0,0.2);
            text-align:center!important;
            color:black;
        }
    
    </style>
    <?php

}
// add_action( 'login_head', 'custom_login_logo' );


/*
*=========================================
* Custom admin login header link   /START
*=========================================
*/


function dbcern_maintenance_admin_page_register() {
    add_menu_page(
        'Lithos Maintenance',   // Page title
        'Lithos Maintenance',               // Menu title
        'manage_options',             // Capability required to access
        'dbcern-maintenance-admin_page',   // Page slug
        'dbcern_maintenance_admin_page_display',   // Callback function to display the page content
        'dashicons-admin-users',      // Icon URL or class
        1                             // Position in the menu
    );
}
add_action('admin_menu', 'dbcern_maintenance_admin_page_register');

// Callback function to display the content of the backend page
function dbcern_maintenance_admin_page_display() {
    ?>
    <div class="wrap">
        <h1 style="font-weight:900;">This is a Dionisis Plugin</h1>
        <br><br>
        <?php 
        
        ?>
    <div class="custom-registration-form">
        <?php
        // Check if the form is submitted
        if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['manualuser'])) {
            // Retrieve form data
            $username = sanitize_user($_POST["username"]);
            $email = sanitize_email($_POST["email"]);
            $role = sanitize_text_field($_POST["role"]);

            // Validate inputs (you can add more validation as needed)

            // Generate a password
            $password = wp_generate_password();

            // Create a new user
            $user_id = wp_create_user($username, $password, $email);

            // Set user role
            if (!is_wp_error($user_id) && in_array($role, ['subscriber', 'administrator'])) {
                $user = new WP_User($user_id);
                $user->set_role($role);
                // Display the results
                echo "<p>User created successfully!</p>";
                echo "<p>Username: $username</p>";
                echo "<p>Email: $email</p>";
                // echo "<p>Role: $role</p>";
                echo "<p>Password: $password</p>";
                echo "<p>Link: <a href='$current_site_url/wp-admin'>$current_site_url/wp-admin</a></p>";

                // Send custom HTML email to the new user
                // $current_site_url = home_url();
                // $subject = 'Login Details for Website: '.$current_site_url;
                // $message = "<br> Welcome to our site.<br><br> Username: $username <br> Password: $password <br> Login Link: <a href='$current_site_url/wp-admin'>Here</a>";
                // $headers = array(
                //     'Content-Type: text/html; charset=UTF-8',
                // );
                // wp_mail($email, $subject, $message,$headers);
            }else{
                echo 'User Not Created, Email or Username already exist! <br>';
            }

            
        }

        if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['autolithosusers'])) {
            $lithos_users = array(
                'dbolanis' => 'd.bolanis@lithosdigital.gr',
                'karakw' => 'd.karakwttas@lithosdigital.gr',
                'Lithos1' => 'a.meksis@lithosdigital.gr',
                'elpida' => 'elpida@lithosdigital.gr',
                'LithosSupport' => 'support@lithosdigital.gr',
                'danai' => 'danai@lithosdigital.com',
                'giorgos' => 'giorgos@lithosdigital.com',
                'panagiota' => 'p.xenitidou@lithosdigital.gr',
            );

            foreach($lithos_users as $username => $email){

                if(isset($_POST[$username]) &&  $_POST[$username] == 'on'){
                    // Generate a password
                    $password = wp_generate_password();
                    $role = 'administrator';
                    // Create a new user
                    $user_id = wp_create_user($username, $password, $email);

                    // Set user role
                    if (!is_wp_error($user_id) && in_array($role, ['subscriber', 'administrator'])) {
                        $user = new WP_User($user_id);
                        $user->set_role($role);
                        // Display the results
                        echo "<p>User created successfully! ";
                        echo "Username: $username ";
                        echo "Email: $email ";
                        echo " Role: $role</p>";

                        // Send custom HTML email to the new user
                        $current_site_url = home_url();
                        $subject = 'Login Details for Website: '.$current_site_url;
                        $message = "<br> Welcome to our site.<br><br> Username: $username <br> Password: $password <br> Login Link: <a href='$current_site_url/wp-admin'>Here</a>";
                        $headers = array(
                            'Content-Type: text/html; charset=UTF-8',
                        );
                        wp_mail($email, $subject, $message,$headers);

                    }else{
                        echo $email.' User Not Created, Email or Username already exist! <br>';
                    }
                }
            }
        }
        ?>

        <!-- HTML form -->
        <form method="post" action="<?php echo esc_url($_SERVER["REQUEST_URI"]); ?>">
            
            <input type="hidden" id="manualuser" name="manualuser" >
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



        <form method="post" action="<?php echo esc_url($_SERVER["REQUEST_URI"]); ?>">
            
            <input type="hidden" id="autolithosusers" name="autolithosusers" >
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