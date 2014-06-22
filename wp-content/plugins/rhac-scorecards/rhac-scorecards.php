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
define('RHAC_PLUGINS_ROOT_DIR',
       preg_replace('/[^\/]+\/$/', '', RHAC_PLUGIN_DIR));

include_once RHAC_PLUGIN_DIR . 'RHAC_Handicap.php';
include_once RHAC_PLUGIN_DIR . 'toplevel.php';
include_once RHAC_PLUGINS_ROOT_DIR . 'rhac-3p-deps/rhac-3p-deps.php';

add_action('admin_menu', 'rhac_scorecards_hook');

add_action('admin_enqueue_scripts', 'rhac_admin_enqueue_scripts');

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

function rhac_admin_enqueue_scripts() {
    rhac_register_3p_scripts();
    rhac_register_3p_styles();
    wp_enqueue_script('rhac_scorecards',
                  plugins_url('scorecard.js', __FILE__),
              array('jquery',
                    'jquery-ui-datepicker',
                    'jquery-ui-tooltip',
                    'jquery-ui-accordion'));

    wp_enqueue_script('jquery-ui-accordion');
    wp_enqueue_style('rhac_scorecard_style',
                     plugins_url('scorecard.css', __FILE__));
    wp_enqueue_style('jquery_ui');
    wp_enqueue_style('jquery_ui_all');
    wp_enqueue_style('jquery_ui_core');
    wp_enqueue_style('jquery_ui_datepicker');
}
