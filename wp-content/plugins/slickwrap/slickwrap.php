<?php
/*
Plugin Name: Slick Wrap
Description: Wrap text arouns a circular or oval image
Version: 0.1
Author: Bill Hails
Author URI: http://www.billhails.net/
License: GPL
*/

function rhac_slickwrap($atts, $content = null) {
    extract( shortcode_atts( array(
        'image_id' => '',
        'image_width' => '200',
        'image_height' => '200',
        'radius' => '100',
        'padding' => '0',
        'centre_side' => '100',
        'centre_top' => '100',
        'oval' => "1.0",
        'visible' => '',
        'float' => 'left',
    ), $atts ) );
    if ($image_id == '') {
        return $content;
    }
    $img_data = wp_get_attachment_image_src($image_id, array($image_width, $image_height), false);
    $img_url = $img_data[0];
    $radius += $padding;
    $top = $centre_top - $radius;
    $bottom = $centre_top + $radius;
    $div_height = 3;
    $div_centre = $div_height / 2;
    if ($visible) {
        $visible = ' background-color: blue;';
    }
    $string = '';
    $string .= '<div style="position:relative;">';
    $string .= '<div style="position:absolute; top:0; ' . $float . ':0;">';
    $string .= '<img class="noshadow" src="' . $img_url . '" width="' . $image_width. '" height="' . $image_height . '" />';
    $string .= '</div>';
    for ($y = $div_centre; $y <= $bottom; $y += $div_height) {
        if ($y < $top) {
            $div_width = 0;
        } else {
            $ty = $centre_top - $y;
            $tx = sqrt($radius * $radius - $ty * $ty);
            $div_width = $centre_side + $tx * $oval;
        }
        $string .= sprintf("<div style='float: %s; clear: %s; width: %fpx; height: %fpx;%s'>&nbsp;</div>\n",
                           $float, $float, $div_width, $div_height, $visible);
    }
    $string .= $content;
    $string .= '<div style="clear:both"></div></div>';
    return $string;
}

add_shortcode('slickwrap', 'rhac_slickwrap');

