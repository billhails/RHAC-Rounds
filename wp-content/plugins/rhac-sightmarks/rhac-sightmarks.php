<?php
/*
Plugin Name: RHAC Sightmarks Calculator
Description: Calculate, save and edit archery sightmarks.
Version: 0.1
Author: Bill Hails
Author URI: http://www.billhails.net/
License: GPL
*/

define('RHAC_SIGHTMARKS_DIR', plugin_dir_path(__FILE__));
define('RHAC_SIGHTMARKS_URL', plugin_dir_url(__FILE__));
define('RHAC_SIGHTMARKS_ROOT', preg_replace('/[^\/]+\/$/', '', RHAC_SIGHTMARKS_DIR));

include_once RHAC_SIGHTMARKS_DIR . 'RHAC_Sightmarks.php';
include_once RHAC_SIGHTMARKS_ROOT . 'rhac-3p-deps/rhac-3p-deps.php';

function rhac_load_sightmark_deps() {
    rhac_register_3p_scripts();
    rhac_register_3p_styles();
    wp_register_script('car-cdr-cons', RHAC_SIGHTMARKS_URL . 'carcdrcons.js');
    wp_register_script('line-of-best-fit', RHAC_SIGHTMARKS_URL . 'line-of-best-fit.js', array('car-cdr-cons'));
    wp_register_script('rhac-sightmarks', RHAC_SIGHTMARKS_URL . 'rhac-sightmarks-mvc.js', array('line-of-best-fit', 'rhac_datatables_jquery', 'rhac_persist'));
    wp_enqueue_script('rhac-sightmarks');
    wp_enqueue_style('rhac_datatables_extra');
    wp_enqueue_script('rhac_datatables_tabletools');
    wp_register_style('rhac_sightmarks', RHAC_SIGHTMARKS_URL . 'rhac-sightmarks.css');
    wp_enqueue_style('rhac_sightmarks');
    wp_enqueue_style('rhac_datatables_tabletools');
}
 
add_action('wp_enqueue_scripts', 'rhac_load_sightmark_deps');

function rhac_sightmarks_calculator() {
    return RHAC_Sightmarks::getInstance()->display();
}

add_shortcode('sightmarks', 'rhac_sightmarks_calculator');
