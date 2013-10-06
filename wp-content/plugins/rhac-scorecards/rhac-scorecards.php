<?php
/*
Plugin Name: RHAC Score Cards
Description: Edit, save and view archery scorecards.
Version: 0.1
Author: Bill Hails
Author URI: http://www.billhails.net/
License: GPL
*/

define('RHAC_PLUGIN_DIR', plugin_dir_path(__FILE__));

include_once RHAC_PLUGIN_DIR . 'toplevel.php';

wp_enqueue_script('rhac_scorecards',
                  plugins_url('scorecard.js', __FILE__),
                  array('jquery', 'jquery-ui-datepicker'));

add_action('admin_menu', 'rhac_scorecards_hook');

add_action('admin_enqueue_scripts', 'rhac_admin_css');

function rhac_scorecards_hook() {
    add_users_page('Score Cards',
                   'Score Cards',
                   'manage_options',
                   'scorecards',
                   'rhac_scorecards_toplevel');
}

function rhac_scorecards_toplevel() {
    if ( !current_user_can( 'manage_options' ) )  {
        wp_die(
            __('You do not have sufficient permissions to access this page.')
        );
    }
    RHAC_Scorecards::getInstance()->topLevel();
}

function rhac_admin_css() {
    wp_enqueue_style('rhac_scorecard_style',
                     plugins_url('scorecard.css', __FILE__));
    wp_enqueue_style('rhac_jquery_ui',
                     plugins_url('jquery-ui.css', __FILE__));
    wp_enqueue_style('rhac_jquery_ui_all',
                     plugins_url('jquery.ui.all.css', __FILE__));
    wp_enqueue_style('rhac_jquery_ui_core',
                     plugins_url('jquery.ui.core.css', __FILE__));
    wp_enqueue_style('rhac_jquery_ui_datepicker',
                     plugins_url('jquery.ui.datepicker.min.css', __FILE__));
}
