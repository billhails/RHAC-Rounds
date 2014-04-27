<?php
/*
Plugin Name: RHAC Rounds Explorer
Description: read-only view of archery records, requires rhac-scorecards plugin
Version: 0.1
Author: Bill Hails
Author URI: http://www.billhails.net/
License: GPL
*/

define('RHAC_RE_DIR', plugin_dir_path(__FILE__));

define('RHAC_PLUGINS_ROOT',
       preg_replace('/[^\/]+\/$/', '', RHAC_RE_DIR));

define('RHAC_RE_SCORECARD_DIR', RHAC_PLUGINS_ROOT . 'rhac-scorecards/');

define('RHAC_RE_PLUGIN_URL_ROOT', plugin_dir_url(__FILE__));

define('RHAC_PLUGINS_URL_ROOT',
       preg_replace('/[^\/]+\/$/', '', RHAC_RE_PLUGIN_URL_ROOT));


include_once RHAC_RE_DIR . 'RHAC_RecordsViewer.php';

function rhac_re_load_deps() {
    global $wp_scripts;
 
    wp_enqueue_script('rhac_records_view',
                      plugins_url('rhac_records_view.js', __FILE__),
                      array('jquery-ui-datepicker', 'jquery-ui-tooltip'));

    wp_enqueue_script('rhac_datatables', RHAC_PLUGINS_URL_ROOT . 'gnas-archery-rounds/jquery.dataTables.min.js', array('jquery-ui-core'));
    wp_enqueue_style('rhac_records_view',
                     plugins_url('rhac_records_view.css', __FILE__));
 
    $ui = $wp_scripts->query('jquery-ui-core');
 
    $protocol = is_ssl() ? 'https' : 'http';
    $url = "$protocol://ajax.googleapis.com/ajax/libs/jqueryui/{$ui->ver}/themes/redmond/jquery-ui.min.css";
    wp_enqueue_style('jquery-ui-redmond', $url, false, null);
    wp_localize_script('rhac_records_view', 'rhacRoundExplorerData', rhac_get_data());
}

function rhac_get_data() {
    $data = array();
    $data['ajaxurl'] = admin_url('admin-ajax.php');
    return $data;
}

add_action('init', 'rhac_re_load_deps');

function rhac_ajax_display_results() {
    // error_log("rhac_ajax_display_results");
    $viewer = RHAC_RecordsViewer::getInstance();
    echo $viewer->display();
    exit;
}

add_action('wp_ajax_rhac_display_results', 'rhac_ajax_display_results');
add_action('wp_ajax_nopriv_rhac_display_results', 'rhac_ajax_display_results');

function rhac_records_viewer() {
    // error_log("rhac_records_viewer");
    $viewer = RHAC_RecordsViewer::getInstance();
    return $viewer->view();
}

add_shortcode('records_viewer', 'rhac_records_viewer');
