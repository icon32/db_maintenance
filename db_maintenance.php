<?php 
/**
 * Plugin Name:       DBCern Page Under Contraction 
 * Description:       Maintenance Page Plugin
 * Version:           1.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Dionisis Bolanis
 * Author URI:        https://bolanis.eu/
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
function remove_admin_bar() {
    if (!current_user_can('administrator') && !is_admin()) {
        show_admin_bar(false);
    }
}
add_action('after_setup_theme', 'remove_admin_bar');

//Remove access to dashboard if not admin
function wpse23007_redirect(){
    if( is_admin() && !defined('DOING_AJAX') && ( current_user_can('subscriber') || current_user_can('contributor') ) ){
        wp_redirect(home_url());
        exit;
    }
}
add_action('init','wpse23007_redirect');



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
add_action( 'login_head', 'custom_login_logo' );


/*
*=========================================
* Custom admin login header link   /START
*=========================================
*/


