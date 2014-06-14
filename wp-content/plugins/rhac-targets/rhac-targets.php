<?php
/*
Plugin Name: RHAC Target Pictures
Description: Compare target pictures at different distances.
Version: 0.1
Author: Bill Hails
Author URI: http://www.billhails.net/
License: GPL
*/

define('RHAC_TARGETS_DIR', plugin_dir_path(__FILE__));
define('RHAC_TARGETS_URL', plugin_dir_url(__FILE__));
define('RHAC_TARGETS_ROOT', preg_replace('/[^\/]+\/$/', '', RHAC_TARGETS_DIR));

include_once RHAC_TARGETS_ROOT . 'rhac-3p-deps/rhac-3p-deps.php';

function rhac_load_targets_deps() {
    rhac_register_3p_scripts();
    rhac_register_3p_styles();
    wp_register_script('rhac_targets',
                       RHAC_TARGETS_URL . 'rhac-targets.js',
                       array('jcanvas', 'jquery-ui-button',
                                        'jquery-ui-slider'));
    wp_enqueue_script('rhac_targets');
}

add_action('wp_enqueue_scripts', 'rhac_load_targets_deps');

add_shortcode('target_pictures', 'rhac_target_pictures');

function rhac_target_pictures() {
    return <<<EOHTML
<canvas width='600' height='1000' class='targets' style='border: 1px solid black;'></canvas>
<div id="slider-range"></div>
<button id="reset">Reset</button>
EOHTML;
}
