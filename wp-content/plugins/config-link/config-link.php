<?php
/*
Plugin Name: Config Link
Description: Configurable Shortcode for links
Version: 0.1
Author: Bill Hails
Author URI: http://www.billhails.net/
License: GPL
*/

$billhails_shortcode_link_base = '';
$billhails_shortcode_link_target = '';
define('BILLHAILS_SRC_HTML', plugins_url('config-link.html', __FILE__));

// either:
// [link base="https://dl.dropbox.com/u/109796457/"]
// to set the base of the url or:
// [link file="Personal Bests.html" label="Personal Bests"]
// to produce the link.

function billhails_shortcode_link($atts) {
    global $billhails_shortcode_link_base;
    global $billhails_shortcode_link_target;
    extract( shortcode_atts( array(
        'base' => '',
        'file' => '',
        'label' => '',
        'target' => ''
    ), $atts ) );
    if ($base != '') {
        $billhails_shortcode_link_base = $base;
    }
    if ($target != '') {
        $billhails_shortcode_link_target = $target;
        $billhails_shortcode_link_target = '_blank';
    }
    if (   $file != ''
        && $label != ''
        && $billhails_shortcode_link_base != ''
        && $billhails_shortcode_link_target != '') {
        return '<a href="'
             . $billhails_shortcode_link_base
             . rawurlencode($file)
             . '" target="' . $billhails_shortcode_link_target . '">'
             . $label
             . '</a>';
    }
    else {
        return '';
    }
}

function billhails_shortcode_link_iframe() {
    global $billhails_shortcode_link_target;
    if (false && $billhails_shortcode_link_target != '') {
        return '<div class="gr-report">' 
                . "<iframe id='$billhails_shortcode_link_target' scrolling='no' class='gr-report' src='"
                . BILLHAILS_SRC_HTML . "'></iframe></div>\n";
    } else {
        return '';
    }
}

add_shortcode('link', 'billhails_shortcode_link');
add_shortcode('link_iframe', 'billhails_shortcode_link_iframe');
