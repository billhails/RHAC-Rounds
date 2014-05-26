<?php
/*
Plugin Name: RHAC Third Party Dependencies
Description: Register third party scripts and styles
Version: 0.1
Author: Bill Hails
Author URI: http://www.billhails.net/
License: GPL
*/

define('RHAC_3P_PLUGIN_URL_ROOT', plugin_dir_url(__FILE__));
add_action('init', 'rhac_register_3p_scripts');
add_action('init', 'rhac_register_3p_styles');

function rhac_register_3p_scripts() {
    wp_register_script('rhac_datatables', RHAC_3P_PLUGIN_URL_ROOT . 'jquery.dataTables.min.js', array('jquery-ui-core'));
    wp_register_script('rhac_datatables_jquery', RHAC_3P_PLUGIN_URL_ROOT . 'dataTables.jqueryui.js', array('rhac_datatables'));
    wp_register_script('rhac_datatable_jquery_colvis', RHAC_3P_PLUGIN_URL_ROOT . 'dataTable.colVis.js', array('rhac_datatables'));
    wp_register_script('rhac_datatables_jquery_colvis', RHAC_3P_PLUGIN_URL_ROOT . 'dataTables.colVis.js', array('rhac_datatables'));
    wp_register_script('chart_js', RHAC_3P_PLUGIN_URL_ROOT . 'Chart.min.js');
}

function rhac_register_3p_styles() {
    wp_register_style('rhac_datatables', RHAC_3P_PLUGIN_URL_ROOT . 'jquery.dataTables.min.css');
    wp_register_style('rhac_datatables_jquery', RHAC_3P_PLUGIN_URL_ROOT . 'dataTables.jqueryui.css');
    wp_register_style('jquery-ui-rhac', RHAC_3P_PLUGIN_URL_ROOT . 'jquery-ui-1.10.4.custom.min.css');
    wp_register_style('jquery-datatables-colvis', RHAC_3P_PLUGIN_URL_ROOT . 'dataTables.colVis.css');
    wp_register_style('jquery-datatables-colvis-ui', RHAC_3P_PLUGIN_URL_ROOT . 'dataTables.colvis.jqueryui.css');
    wp_register_style('jquery_ui',  RHAC_3P_PLUGIN_URL_ROOT . 'jquery-ui.css');
    wp_register_style('jquery_ui_all',  RHAC_3P_PLUGIN_URL_ROOT . 'jquery.ui.all.css');
    wp_register_style('jquery_ui_core',  RHAC_3P_PLUGIN_URL_ROOT . 'jquery.ui.core.css');
    wp_register_style('jquery_ui_datepicker',  RHAC_3P_PLUGIN_URL_ROOT . 'jquery.ui.datepicker.min.css');
}
