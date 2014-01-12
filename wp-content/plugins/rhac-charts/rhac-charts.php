<?php
/*
Plugin Name: RHAC Charts
Description: Simple interface to Chart.js
Version: 0.1
Author: Bill Hails
Author URI: http://www.billhails.net/
License: GPL
*/

define('RHAC_CHARTS_PLUGIN_DIR', plugin_dir_path(__FILE__));

add_action( 'wp_enqueue_scripts', 'register_rhac_charts');

function register_rhac_charts() {
    wp_enqueue_script('chart_js', plugins_url('Chart.min.js', __FILE__), array());
}

$rhac_datasets = array();
$rhac_labels = array();

function rhac_chart($atts, $content = null) {
    extract( shortcode_atts( array(
		'width' => '640',
		'height' => '400',
                'id' => '',
	), $atts ) );
        if ($id) {
            global $rhac_labels;
            $rhac_labels = array();
            global $rhac_datasets;
            $rhac_datasets = array();
            do_shortcode($content);
            $json_labels = json_encode($rhac_labels);
            $json_datasets = json_encode($rhac_datasets);
            return <<<EOHTML
<canvas id="$id" width="$width" height="$height"></canvas>
<script type="text/javascript">
{
    var options = { bezierCurve: false, pointDot: false };
    var data = {
        labels : $json_labels,
        datasets : $json_datasets
    };
    var ctx = document.getElementById("$id").getContext("2d");
    new Chart(ctx).Line(data,options);
}
</script>

EOHTML;
        } else {
            return '';
        }
}

function rhac_labels($atts) {
    extract( shortcode_atts( array( 'labels' => '' ), $atts) );
    global $rhac_labels;
    $rhac_labels = explode(",", $labels);
}

function rhac_dataset($atts) {
    extract(
        shortcode_atts(
            array(
                'fill' => 'rgba(220,220,220,0.5)',
                'stroke' => 'rgba(220,220,220,1)',
                'point' => 'rgba(220,220,220,1)',
                'pointstroke' => 'rgba(220,220,220,1)',
                'data' => '',
            ),
            $atts
        )
    );
    if ($data) {
        $data = explode(",", $data);
        $numbers = array();
        foreach ($data as $datum) {
            sscanf($datum, "%d", &$val);
            $numbers []= $val;
        }

        global $rhac_datasets;
        $rhac_datasets []= array(
            "fillColor" => $fill,
            "strokeColor" => $stroke,
            "pointColor" => $point,
            "pointStrokeColor" => $pointstroke,
            "data" => $numbers
        );
    }
}

add_shortcode('graph', 'rhac_chart');
add_shortcode('labels', 'rhac_labels');
add_shortcode('dataset', 'rhac_dataset');
