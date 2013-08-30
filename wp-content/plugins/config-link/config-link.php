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

// either:
// [link base="https://dl.dropbox.com/u/109796457/"]
// to set the base of the url or:
// [link file="Personal Bests.html" label="Personal Bests"]
// to produce the link.

function billhails_shortcode_link($atts) {
    global $billhails_shortcode_link_base;
    extract( shortcode_atts( array(
        'base' => '',
        'file' => '',
        'label' => '',
    ), $atts ) );
    if ($base != '') {
        $billhails_shortcode_link_base = $base;
    }
    if ($file != '' && $label != '' && $billhails_shortcode_link_base != '') {
        return '<a href="'
             . $billhails_shortcode_link_base
             . rawurlencode($file)
             . '" target="_blank">'
             . $label
             . '</a>';
    }
    else {
        return '';
    }
}

add_shortcode('link', 'billhails_shortcode_link');
